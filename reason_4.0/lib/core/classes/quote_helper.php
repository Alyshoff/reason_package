<?
include_once('reason_header.php');
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * Quote helper
 *
 * Retrives unused quotes according to site_id and page_id
 *
 * Can be setup to exclude unavailable_quotes from the set of quotes returned and to use a cache.
 *
 * Utilized by the quote module and the quote_retrieval script.
 *
 * Sample usage example:
 *
 *  <code>
 *  	$qh = new QuoteHelper();
 *		$qh->set_site_id($site_id);
 *		$qh->set_page_id($page_id);
 *		$qh->init();
 *		$quote =& $qh->get_random_quote();	
 *  </code>
 *
 * @package reason
 * @subpackage classes
 *
 * @author Nathan white
 */

 class QuoteHelper
 {
	var $site_id;
	var $page_id;
	
	var $unavailable_quote_ids = array();
	var $page_category_mode = false;
	var $cache_lifespan = 0;
	
	var $quote;
	var $quote_pool;
	
 	function QuoteHelper($site_id = NULL, $page_id = NULL, $unavailable_quote_ids = NULL, $page_category_mode = NULL, $cache_lifespan = NULL)
 	{
 		if (isset($site_id)) $this->set_site_id($site_id);
 		if (isset($page_id)) $this->set_page_id($page_id);
 		if (isset($unavailable_quote_ids)) $this->set_unavailable_quote_ids($unavailable_quote_ids);
 		if (isset($page_category_mode)) $this->set_page_category_mode($page_category_mode);
 		if (isset($cache_lifespan)) $this->set_cache_lifespan($cache_lifespan);
 	}
 	
 	function init()
 	{
 		$this->init_from_cache();
		if (!isset($this->quote_pool)) $this->init_from_database(); // there is no cache
 	}
 	
 	function init_from_cache()
	{
		$cache_lifespan = $this->get_cache_lifespan();
		if ($cache_lifespan > 0)
		{
			$cache = new ReasonObjectCache($this->get_cache_id(), $this->get_cache_lifespan());
			$this->quote_pool =& $cache->fetch();
		}
	}
	
	function init_from_database()
	{
		if (!empty($this->site_id) && !empty($this->page_id))
 		{
 			$es = new entity_selector($this->site_id);
 			$es->add_type( id_of('quote_type') );
 			$es->limit_tables(array('meta'));
 			$es->limit_fields('meta.description');
 			$es->add_right_relationship( $this->page_id, relationship_id_of('page_to_quote') );
 			$es->add_rel_sort_field( $this->page_id, relationship_id_of('page_to_quote'), 'rel_sort_order');
 			$es->set_order( 'rel_sort_order ASC' );
 			$result = $es->run_one();
 			$result_array = ($result) ? $result : array();
 			$extra_results = $this->init_from_categories($result);
 			$this->quote_pool = $result + $extra_results;
 			$this->set_cache();
 		}
 		else
 		{
 			trigger_error('The page_id and site_id must be available to find quotes');
 		}
 	}
 	
 	function init_from_categories(&$already_selected)
	{
		if ($this->page_category_mode)
		{
			$cat_es = new entity_selector($this->site_id);
			$cat_es->add_type( id_of('category_type') );
			$cat_es->limit_tables();
			$cat_es->limit_fields();
			$cat_es->add_right_relationship ($this->page_id, relationship_id_of( 'page_to_category' ) );
			$cat_result = $cat_es->run_one();
			if (!empty($cat_result))
			{
				$es = new entity_selector($this->site_id);
				$es->add_type( id_of('quote_type') );
				$es->set_env('site', $this->site_id);
				$es->add_left_relationship_field( 'quote_to_category', 'entity', 'id', 'cat_id', array_keys($cat_result));
				if (!empty($already_selected)) $es->add_relation('entity.id NOT IN ('.implode(array_keys($already_selected)).')');
				$result = $es->run_one();
			}
		}
		return (!empty($result)) ? $result : array();
	}
 	
 	function &get_quote_pool()
 	{
 		if (isset($this->quote_pool))
		{
			return $this->quote_pool;
		}
 		else
 		{
 			trigger_error('You must initialize the helper using the init() method before accessing quotes.', FATAL);
 		}
 	}
 	
 	function get_unavailable_quotes()
 	{
 		$quotes =& $this->get_quote_pool();
 		foreach ($this->unavailable_quote_ids as $id)
 		{
 			$unavailable[$id] =& $quotes[$id];
 		}
 		return (!empty($unavailable)) ? $unavailable : false;
 	}
 	
 	function get_available_quotes()
 	{
 		$quotes =& $this->get_quote_pool();
 		$available_ids = array_diff(array_keys($quotes), $this->unavailable_quote_ids);
 		foreach ($available_ids as $id)
 		{
 			$available[$id] =& $quotes[$id];
 		}
 		return (!empty($available)) ? $available : false;
 	}
 	
 	function &get_quotes($num, $rand_flag = false)
 	{
 		if ($rand_flag)
 		{
 			return $this->get_random_quotes($num);
 		}
 		else
 		{
 			$index = 0;
 			$quotes =& $this->get_quote_pool();
 			foreach ($quotes as $k=>$v)
 			{
 				if ($index == $num) break;
 				$quote_set[$k] =& $quotes[$k];
 				$index++;
 			}
 			return $quote_set;
 		}
 	}
 	function &get_random_quotes($num)
 	{
 		for ($i=0; $i<$num; $i++)
 		{
 			$quote =& $this->get_random_quote();
 			$id = $quote->id();
 			$this->set_unavailable_quote_id($id);
 			$quotes[$id] =& $quote;
 		}
 		return $quotes;
 	}
 	
 	function &get_random_quote()
 	{
 		$quotes =& $this->get_quote_pool();
 		if (!empty($quotes))
 		{
 			$available_quotes = $this->get_available_quotes();
 			if ($available_quotes)
 			{
 				$id = array_rand($available_quotes);
 			}
 			else
 			{
 				$unavailable_quotes = $this->get_unavailable_quotes();
 				if (count($unavailable_quotes) > 1)
 				{
 					array_pop($unavailable_quotes);
 					$id = array_rand($unavailable_quotes);
 				}
 				else $id = array_rand($quotes);
 			}
 			$quote =& $quotes[$id];
 		}
 		else $quote = false;
 		return $quote;
 	}
  	
 	function set_site_id($site_id)
 	{
 		$this->site_id = $site_id;
 	}
 	
 	function set_page_id($page_id)
 	{
 		$this->page_id = $page_id;
 	}
 	
 	function set_page_category_mode($val)
 	{
 		$this->page_category_mode = ($val);
 	}

 	function set_unavailable_quote_ids($unavailable_quote_ids)
 	{
 		$this->unavailable_quote_ids = $unavailable_quote_ids;
 	}
 	
 	function set_unavailable_quote_id($unavailable_quote_id)
 	{
 		$this->unavailable_quote_ids[] = $unavailable_quote_id;
 	}
		
	function set_cache()
	{
		if ($this->get_cache_lifespan() > 0)
		{
			$cache = new ReasonObjectCache($this->get_cache_id());
			$cache->set($this->quote_pool);
		}
	}
	
	function set_cache_lifespan($seconds)
	{
		$ls = turn_into_int($seconds);
		$this->cache_lifespan = $seconds;
	}
		
	function get_cache_id()
	{
		return md5('quote_cache_site_' . $this->site_id . '_page_' . $this->page_id);
	}
	
	function get_cache_lifespan()
	{
		return $this->cache_lifespan;
	}
	
	function clear_cache()
	{
		$cache = new ReasonObjectCache($this->get_cache_id());
		$cache->clear();
	}
 }
 
?>