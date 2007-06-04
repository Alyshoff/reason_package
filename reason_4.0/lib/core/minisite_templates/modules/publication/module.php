<?php
$GLOBALS[ '_module_class_names' ][ 'publication' ] = 'PublicationModule';
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PublicationModule';

include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/generic3.php' );
reason_include_once( 'minisite_templates/modules/publication/submodules/blog_post_submission_form.php' );

/**
* A minisite module to handle publications, including blogs, issued newsletters, and newsletters.
* 
* This module attempts to separate logic and markup as much as possible in order to maximize flexibility in markup;
* the logic is handled by this class, while the markup is created by easily extensible markup generator classes.  
*
* @package reason
* @subpackage minisite_modules
*
* @author Meg Gibbs
* @author Matt Ryan
* @author Nathan White
*
* @todo Move any remaining markup in the publication module that could possibly be removed to the appropriate markup generator
* @todo Add functionality to email author of posts when comments are made, owner of blog when posts are made, etc.
* @todo Alter language from being blog-oriented to being publication-oriented.
* @todo Limit list of categories to categories that are associated with items FOR THIS PUBLICATION
*/	
class PublicationModule extends Generic3Module
{
////////
// VARIABLES
////////	

	//generic 3 variable overrides
	var $query_string_frag = 'story';
	var $pagination_prev_next_texts = array('previous'=>'Newer','next'=>'Older');
	var $use_dates_in_list = true;
	var $show_list_with_details = false;
	var $has_feed = true;
	var $jump_to_item_if_only_one_result = false;
	var $make_current_page_link_in_nav_when_on_item = true;
	var $back_link_text = 'Return to ';
	var $feed_url;
	
	var $style_string = 'blog';
	var $use_pagination = true;
	var $no_items_text = 'This publication does not have any news items yet.';
	var $date_format = 'F j, Y \a\t g:i a';		//will be replaced if 'date_format' field for publication is set
	var $num_per_page = 12;	
	var $max_num_items = '';
	
	// Filter settings
	var $use_filters = true;
	var $filter_types = array(	'category'=>array(	'type'=>'category_type',
													'relationship'=>'news_to_category',
												 ),
							);
	var $search_fields = array('entity.name','chunk.content','meta.keywords','meta.description','chunk.author');
	var $search_field_size = 10;
	
	
	//variables original to this module
	var $publication;	//entity of the current publication
	var $item;			//entity of the news item being viewed 
	var $user_netID; 	//current user's net_ID
	var $session;		//reason session
	var $additional_query_string_frags = array ('comment_posted', 'issue', 'section');	
	var $issue_id;							// id of the issue being viewed - this is a class var since the most recent issue won't necessarily be in $_REQUEST
	var $issues = array();					// $issue_id => $issue_entity
	var $sections = array();				// $section_id => $section_entity
	var $no_section_key = 'no_section';		//key to be used in the items_by_section array when there are no sections.
	var $group_by_section = true;			//whether or not items should be grouped by section when displayed
	var $show_module_title = false; // page title module generally handles this
	
	// related mode variables - page type configurable
	var $related_mode = false;      // in related_mode, related publication items are aggregated
	var $related_order = ''; 		// allows for keywords for custom order and special considerations for related items
	var $related_title; // page type can provide specific title or keyword which will be used instead of the default
	var $limit_by_page_categories = false; // by default page to category relationship is ignored - can be enabled in page type
	
	var $related_publications;
	var $related_publications_links = array();
	var $related_categories;
	
	var $class_vars_pass_to_submodules = array('publication');	//needed by the item markup generator

	var $show_login_link = true;
	var $show_featured_items = true;
		
	/** 
	* Stores the default class names and file names of the markup generator classes used by the module.  
	* Format:  $markup_generator_type => array($classname, $filename)
	* @var array
	*/		
	var $markup_generator_info = array( 'item' => array ('classname' => 'PublicationItemMarkupGenerator', 
														 'filename' => 'minisite_templates/modules/publication/item_markup_generators/default.php',
														 //'settings' => array()
														 ),
										'list_item' => array ('classname' => 'PublicationListItemMarkupGenerator', 
										                      'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/default.php',
										                      //'settings' => array()
										                      ),
										'list' => array ('classname' => 'PublicationListMarkupGenerator', 
										                 'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/default.php',
										                 //'settings' => array()
										                 ),
										'featured_item' => array ('classname' => 'PublicationListItemMarkupGenerator', 
										                          'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/default.php',
										                          //'settings' => array()
										                          ),
								   	   );
								   	   
	var $related_markup_generator_info = array( 'list_item' => array ('classname' => 'RelatedListItemMarkupGenerator', 
										                  'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php',
										         ),
												'list' => array ('classname' => 'RelatedListMarkupGenerator', 
										                 'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/related_list.php',
										                 ),
								   	   			);								   

	/** 
	* Maps the names of variables needed by the markup generator classes to the name of the method that generates them.
	* Same as {@link $item_specific_variables_to_pass}, but these methods cannot take any parameters.
	* @var array
	*/		    									
	var $variables_to_pass = array (   'site' => 'get_site_entity', 
									   'list_item_markup_strings' => 'get_list_item_markup_strings',
									   'featured_item_markup_strings' => 'get_featured_item_markup_strings',
									  //links
	 								   'back_link' => 'construct_back_link',
									   'back_to_section_link' => 'construct_back_to_section_link',
									   //comments
									   'comment_group' => 'get_comment_group',
									   'comment_group_helper' => 'get_comment_group_helper',
									   'comment_moderation_state' => 'get_comment_moderation_state',
									   //issues
									   'current_issue' => 'get_current_issue', 
									   'issues_by_date' => 'get_issues',
									   'links_to_issues' => 'get_links_to_issues',
									   //sections
									   'current_section' => 'get_current_section',
									   'sections' => 'get_sections',
									   'group_by_section' => 'use_group_by_section_view',
									   'items_by_section' => 'get_items_by_section', 
									   'links_to_sections' => 'get_links_to_sections',
									   'view_all_items_in_section_link' => 'get_all_items_in_section_link',
									   'links_to_current_publications' => 'get_links_to_current_publications',
									);
	
	/**
	* Maps the names of variables needed by the markup generator classes to the name of the method that generates them.
	* Same as {@link variables_to_pass}, but these methods require a news item entity as a parameter.
	* @var array
	*/
	var $item_specific_variables_to_pass = array (	 'item_comment_count' => 'count_comments', 
													 'link_to_full_item' => 'get_link_to_full_item',
													 'link_to_related_item' => 'get_link_to_related_item',
													 'permalink' => 'construct_permalink',
													 'teaser_image' => 'get_teaser_image',
													 'section_links' => 'get_links_to_sections_for_this_item',
													 'item_number' => 'get_item_number',
												);
											   

	//var $acceptable_params
	
////////
// INIT-RELATED METHODS
////////	

	/**
	*	Extended from generic3 so that the generic3 init function is called on ONLY if there is actually 
	*   a publication associated with the page.  We don't want any orphaned news items showing up.  
	*/
	function init( $args ) 
	{
		$this->set_defaults_from_parameters($this->params);
		if ($this->related_mode) $this->init_related( $args );
		elseif (!empty($this->publication)) parent::init( $args );
		else
		{
			//make sure that there's a publication associated with this page before we do anything else.  
			$pub_es = new entity_selector( $this->site_id );
			$pub_es->description = 'Selecting publications for this page';
			$pub_es->add_type( id_of('publication_type') );
			$pub_es->add_right_relationship( $this->page_id, relationship_id_of('page_to_publication') );
			$pub_es->set_num( 1 );
			$publications = $pub_es->run_one();
			if(!empty($publications))
			{
				//defining variables and such should usually go in the additional_init_actions(), so we only define the publication here
				$this->publication = current($publications);
				parent::init( $args );
			}
			else
			{
				trigger_error('No publications are associated with this publication page');
			}
		}
	}
	
	/**
	 * Init when publication is in related_mode
	 * @author Nathan White
	 */
	function init_related( $args )
	{
		// init defaults
		$this->use_filters = false;
		$this->show_login_link = false;
		$this->use_pagination = false;
		$this->style_string = 'relatedPub';
		unset ($this->request[ $this->query_string_frag.'_id' ] );
		if (empty($this->max_num_items)) $this->max_num_items = $this->num_per_page;
		
		$publication_ids = (!empty($this->params['related_publication_unique_names'])) 
						   ? $this->build_ids_from_unique_names($this->params['related_publication_unique_names'])
						   : array();
		$pub_es = new entity_selector( $this->site_id );
		$pub_es->description = 'Selecting publications for this page';
		$pub_es->add_type( id_of('publication_type') );
		$pub_es->enable_multivalue_results();
		$pub_es->limit_tables();
		$pub_es->limit_fields();
		if (!empty($publication_ids)) $pub_es->add_relation('entity.id IN (' . implode(",", array_keys($publication_ids)) . ')');
		else $pub_es->add_right_relationship( $this->page_id, relationship_id_of('page_to_related_publication') );
		$pub_es->add_right_relationship_field('page_to_publication', 'entity', 'id', 'page_id');
		$publications = $pub_es->run_one();
		if (empty($publications))
		{
			$s = get_microtime();
			$pub_es = new entity_selector( $this->site_id );
			$pub_es->description = 'Selecting publications for this page';
			$pub_es->add_type( id_of('publication_type') );
			$pub_es->enable_multivalue_results();
			$pub_es->limit_tables();
			$pub_es->limit_fields();
			$pub_es->add_right_relationship_field('page_to_publication', 'entity', 'id', 'page_id');	
			$publications = $pub_es->run_one();
		}	
		if (!empty($publications))
		{
			$this->related_publications = $publications;
			
			if ($this->limit_by_page_categories)
			{
				$category_ids = (!empty($this->params['related_category_unique_names'])) 
								? $this->build_ids_from_unique_names($this->params['related_category_unique_names'])
								: array();
				// grab categories in which to limit related news items
				$cat_es = new entity_selector( $this->site_id );
				$cat_es->description = 'Selecting categories for this page';
				$cat_es->add_type( id_of('category_type'));
				$cat_es->limit_tables();
				$cat_es->limit_fields();
				if (!empty($category_ids)) $cat_es->add_relation('entity.id IN (' . implode(",", array_keys($category_ids)) . ')');
				else $cat_es->add_right_relationship($this->page_id, relationship_id_of('page_to_category') );
				$categories = $cat_es->run_one();
				if (!empty($categories))
				{
					$this->related_categories = $categories;
				}
			}
			parent::init( $args );
		}
		else
		{
			trigger_error('No publication are placed on a page on this site');
		}
	}

	/**
	 * Build array of entities from an array of unique_names (or a string with one unique name)
	 */
	function build_ids_from_unique_names($unique_names)
	{
		$unique_names = (is_array($unique_names)) ? $unique_names : array($unique_names);	
		foreach($unique_names as $unique_name)
		{
			$id = id_of($unique_name);
			if (!empty($id)) $ids[$id] = $unique_name;
		}
		return (isset($ids)) ? $ids : array();
	}
	
	/**
	 * Crumb for publication should use the release title and not the name of the item
	 * @author Nathan White
	 */
	function add_crumb()
	{
		foreach( $this->items AS $item )
        {
	       	if( $item->id() == $this->request[ $this->query_string_frag.'_id' ] )
           	{
           		$this->parent->add_crumb( $item->get_value( 'release_title' ) );
          	}
        }
	}

	/**
	 * Modifies the params array to dramatically expand what can be passed in via page type parameters for a publication
	 * @author Nathan White
	 */
	function handle_params( $params )
	{
		// all params that could be provided in page_types
		$potential_params = array('use_filters', 'use_pagination', 'num_per_page', 'max_num_items', 'show_login_link', 
		      					  'show_module_title', 'related_mode', 'related_order', 'date_format', 'related_title',
		      					  'limit_by_page_categories', 'related_publication_unique_names', 'related_category_unique_names');
		$markup_params = 	array('markup_generator_info' => $this->markup_generator_info, 
							      'item_specific_variables_to_pass' => $this->item_specific_variables_to_pass,
							      'variables_to_pass' => $this->variables_to_pass);
		
		$params_to_add = array_diff_assoc_recursive($params, $markup_params + $potential_params);
		if (!empty($params_to_add))
		{
			foreach ($params_to_add as $k=>$v)
			{
				$this->acceptable_params[$k] = $v;
			}
		}
		parent::handle_params( $params );
	}

	/**
	 * @author Nathan White
	 */
	function set_defaults_from_parameters($param_array, $key = '')
	{
		if (!empty($this->params['related_mode']) && $this->params['related_mode'] == true)
		{
			$this->markup_generator_info = $this->related_markup_generator_info;
		}
		foreach ($param_array as $k=>$v)
		{
			if (isset($this->$k))
			{
				if (is_array($this->$k))
				{
					$this->$k = array_merge_recursive2($this->$k, $v);
				}
				else $this->$k = $v;
			}
		}
	}
	
	//extended generic3 hook
	function pre_es_additional_init_actions() 
	{
		if ($this->related_mode) $this->related_pre_es_additional_init_actions();
		else
		{
			$this->module_title = ($this->show_module_title) ? $this->publication->get_value('name') : '';
			
			// allow parameter override
			if (!empty($this->params['num_per_page']))
			{
				$this->num_per_page = $this->params['num_per_page'];
			}
			elseif($this->publication->get_value('posts_per_page'))
			{
				$this->num_per_page = $this->publication->get_value('posts_per_page');
			}
			
			$date_format = $this->publication->get_value('date_format');
			if(!empty($date_format))
				$this->date_format = $this->publication->get_value('date_format');
					
			$publication_type = $this->publication->get_value('publication_type');
			$publication_descriptor = 'publication';
			$news_item_descriptor = 'news items';
			if($publication_type == 'Blog')
			{
				$publication_descriptor = 'blog';
				$news_item_descriptor = 'posts';
			}
			elseif($publication_type == 'Newsletter')
			{
				$publication_descriptor = 'newsletter';
				$news_item_descriptor = 'articles';
			}
			
			if($this->has_issues()) // means publication is set to use issues and the publication has related issues
			{
				$publication_descriptor = 'issue';
				$this->init_issue();
			}
		
			$this->no_items_text = 'This '.$publication_descriptor.' does not have any '.$news_item_descriptor.'.';
				
			$this->back_link_text = $this->back_link_text.$this->publication->get_value('name');
			
			if($this->make_current_page_link_in_nav_when_on_item 
				&&	(!empty($this->request[$this->query_string_frag.'_id']) || !empty($this->request['section_id']) || !empty($this->request['issue_id']) ) )
			{
				$this->parent->pages->make_current_page_a_link();
			}
		}
	}

	/**
	 * init_issue_for_item checks the item and any issue id it was passed - if an issue does not exist or is
	 * invalid, the user is redirected to a url with the most recent valid issue for the item
	 */
	function init_issue()
	{
		$issue_keys = false;
		$requested_issue = (!empty($this->request['issue_id'])) ? $this->request['issue_id'] : false;
		$requested_section = (!empty($this->request['section_id'])) ? $this->request['section_id'] : false;
		if ($this->current_item_id)
		{
			$issues =& $this->get_issues_for_item();
			$issue_keys = (!empty($issues)) ? array_keys($issues) : false;
		}
		else
		{
			if ($requested_issue) 
			{
				$issues =& $this->get_issues();
				$issue_keys = array_keys($issues);
			}
			elseif (!$requested_section) // if no section requested set an issue_id
			{
				$most_recent_issue = $this->get_most_recent_issue();
				$this->issue_id = $most_recent_issue->id();
				return true;
			}
		}	
		if ($issue_keys) // item is in an issue
		{
			if (in_array($requested_issue, $issue_keys))
			{
				$this->issue_id = $requested_issue; // requested issue verified
				return true;
			}
			else
			{
				$redirect = make_redirect(array('issue_id' => array_shift($issue_keys)));
				header('Location: '.$redirect);
				exit;
			}
		}
	}
	
	/**
	 * pre_es_additional_init_actions when module is in related mode
	 * @author Nathan White
	 */
	function related_pre_es_additional_init_actions()
	{
		if (!empty($this->related_title))
		{
			//if ($this->related_title = 'keywords')  // rules can be defined to handle title keywords
			//elseif
			//else 
			$this->module_title = $this->related_title;
		}
		elseif(count($this->related_publications) == 1)
		{
			$pub = current($this->related_publications);
			$this->module_title = $pub->get_value('name');
		}
		else $this->module_title = 'Related posts';
	}
	
	//overloaded from generic3 so that pagination is turned off if we're grouping items by section
	// also disables pagination if max_num_items is set, and is less than num_per_page
	function do_pagination()
	{
		$this->total_count = $this->es->get_one_count();
		if($this->use_group_by_section_view() || (!empty($this->max_num_items) && ($this->max_num_items) < $this->num_per_page))
		{
			$this->use_pagination = false;
		}
		else
		{
			parent::do_pagination();
		}
	}
	
	/**	
	* Adds any query string fragments from the publication module to the cleanup_rules.
	* @return array $cleanup_rules
	*/
	function get_cleanup_rules()
	{
		$this->cleanup_rules = parent::get_cleanup_rules();
		foreach($this->additional_query_string_frags  as $fragment)
		{
			$this->cleanup_rules[$fragment . '_id'] = array('function' => 'turn_into_int');
		}
		return $this->cleanup_rules;
	}

	//overloaded generic3 function -- sets what entity type "items" is
	function set_type()
	{
		$this->type = id_of('news');
	}
	
	//overloaded generic3 hook ... we've added the news_to_blog relation to the es,  issue_id & section_id relations when appropriate
	function alter_es() // {{{
	{
		if ($this->related_mode) $this->related_alter_es();
		else
		{
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_left_relationship( $this->publication->id(), relationship_id_of('news_to_publication') );
			if(!empty($this->issue_id))
			{
				$this->es->add_left_relationship( $this->issue_id, relationship_id_of('news_to_issue') );
			}
			if(!empty($this->request['section_id']))
			{
				$this->es->add_left_relationship( $this->request['section_id'], relationship_id_of('news_to_news_section') );
			}
		}
		$this->es->add_relation( 'status.status = "published"' );	
		$this->further_alter_es();
		if(!empty($this->max_num_items))
		{
			$this->es->set_num($this->max_num_items);
		}
	} // }}}
	
	function related_alter_es()
	{
		$this->es->set_env('site', $this->site_id);
		$this->es->optimize('distinct');
		$this->es->add_left_relationship( array_keys($this->related_publications), relationship_id_of('news_to_publication') );
		// add category limitations
		if (!empty($this->related_categories)) // if no categories do not limit;
		{
			$this->es->add_left_relationship( array_keys($this->related_categories), relationship_id_of('news_to_category'));
		}
		$this->related_order_and_limit($this->es, array('status'));
	}

	/**
	 * applies the ordering scheme specified in the related_order keyword - currently only random and the default dated.datetime DESC
	 * ordering are supported.
	 * @param object $es by reference, the entity selector for which we will specify order and limits
	 * @param array $table_limit_array optional array of tables which should be included in the limit_tables array
	 * @param array $field_limit_array optional array of fields which should be included in the limit_fields array
	 * @return void
	 * @author Nathan White
	 */
	function related_order_and_limit(&$es, $table_limit_array = '', $field_limit_array = '')
	{
		if ($this->related_order == 'random')
		{
			$order_string = 'rand()';
		}
		else
		{
			$table_limit_array[] = 'dated';
			$order_string = 'dated.datetime DESC';
		}
		$es->limit_tables($table_limit_array);
		$es->limit_fields($field_limit_array);
		$es->set_order($order_string);
	}
	
	function further_alter_es()
	{
	}
	
	function post_es_additional_init_actions()
	{
		if ($this->related_mode) $this->related_post_es_additional_init_actions();
	}
	
	/**
	 * take the set of items selected, and replaces it with a set that includes the multivalue publication and category ids that
	 * match the limitations of the page
	 * @author Nathan White
	 */
	function related_post_es_additional_init_actions()
	{
		if ($this->items)
		{
			$es = new entity_selector();
			$es->add_type($this->type);
			$es->enable_multivalue_results();
			$es->add_relation('entity.id IN ('.implode(",", array_keys($this->items)).')');
			$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'publication_id', array_keys($this->related_publications));
			if ($this->related_categories)
			{
				$es->add_left_relationship_field('news_to_category', 'entity', 'id', 'cat_id', array_keys($this->related_categories));
			}
			$this->related_order_and_limit($es);
			$this->items = $es->run_one();
		}
	}

	//overloaded generic3 function	
	function has_content() // {{{
	{
		if ($this->related_mode) return $this->has_content_related();
		elseif(empty($this->publication))
			return false;
		else
			return true;
	} // }}}
	
	/**
	 * has content function for module when running in related mode
	 * @author Nathan White
	 */
	function has_content_related()
	{
		if (empty($this->items)) return false;
		else 
		{
			return true;
		}
	}

////////
// DISPLAY INDIVIDUAL ITEM METHODS
////////	

	//overloaded generic3 function
	/**
	*	Displays the full view of a news item.
	*   @param $item the news item entity
	*/
	function show_item_content( $item ) // {{{
	{
		$this->item = $item;
		
		//if this is an issued publication, we want to say what issue we're viewing
		$current_issue = $this->get_current_issue();
		if(!empty($current_issue) )
		{
			$list_markup_generator = $this->set_up_generator_of_type('list');
			echo $list_markup_generator->get_current_issue_markup($current_issue);
		}
		
		$item_markup_generator = $this->set_up_generator_of_type('item', $item);
		echo $item_markup_generator->get_markup();	
	} // }}}

	//this is the function used to generate the variable needed by the list_markup_generator
	function get_site_entity()
	{
		return new entity($this->site_id);
	}
	
////////
// DISPLAY LIST METHODS
////////	

	//overloaded from generic3 so that the links to other issues will still appear even when there are no items for that issue.
	function list_should_be_displayed()
	{
		if(!empty($this->items) || $this->has_issues() )
			return true;
		else
			return false;
	}
	
	// overloaded generic3 function
	/** 
	* Gets the markup for the list from the list markup generator.
	* If there are no items in the list, displays links to other issues if appropriate
	*/ 
	function do_list()
	{	
		$list_markup_generator = $this->set_up_generator_of_type('list');
		echo $list_markup_generator->get_markup();	
		
		if(empty($this->items))	//this should only appear if we have issues ... otherwise would be echoed list_items()
			echo $this->no_items_text;
	}

	/**
	*  Instantiates a new markup generator and passes it the correct variables.
	*  @param string $type Type of markup generator to instantiate (this needs to be a key in {@link markup_generator_info})
	*  @param object $item New item entity (optional - pass if this is a markup generator to display an individual item).
	*  @return the new markup generator
	*/
	function set_up_generator_of_type($type, $item = false)
	{
		reason_include_once( $this->markup_generator_info[$type]['filename'] );
		$markup_generator = new $this->markup_generator_info[$type]['classname']();
		$markup_generator_settings = (!empty($this->markup_generator_info[$type]['settings'])) 
								     ? $this->markup_generator_info[$type]['settings'] 
								     : '';
		if (!empty($markup_generator_settings)) $markup_generator->set_passed_variables($markup_generator_settings);
		$markup_generator->set_passed_variables($this->get_values_to_pass($markup_generator, $item));
		return $markup_generator;
	}
	
	/**
	*  Helper function to set_up_generator_of_type; passes appropriate variables from the module to the 
	*  markup generator
	*  @param object $markup_generator The markup generator object
	*  @return An array of values to pass, formatted $variable_name => value
	*/
	function get_values_to_pass($markup_generator, $item)
	{
		$values_to_pass = array();
		foreach($markup_generator->get_variables_needed() as $var_name)
		{
			if(isset($this->variables_to_pass[$var_name]) && !empty($this->variables_to_pass[$var_name]) )
			{
				$method = $this->variables_to_pass[$var_name];
				if(method_exists($this, $method))
					$values_to_pass[$var_name] = $this->$method();
				else
					trigger_error('Method "'.$method.'" is not defined', WARNING);
			}
			elseif(isset($this->item_specific_variables_to_pass[$var_name])&& !empty($this->item_specific_variables_to_pass[$var_name]) )
			{
				$method = $this->item_specific_variables_to_pass[$var_name];
				if(method_exists($this, $method))
					$values_to_pass[$var_name] = $this->$method($item);
				else
					trigger_error('Method "'.$method.'" is not defined', WARNING);
			}
			elseif($var_name == 'item' && !empty($item))
			{
				$values_to_pass[$var_name] = $item;
			}
			//elseif( isset($this->markup_generator_info
			elseif( isset($this->$var_name))
				$values_to_pass[$var_name] = $this->$var_name;
		}
		return $values_to_pass;
	}
	
//////////
///  METHODS TO ADD NEW ITEMS
//////////

		/**	
		* Returns the text for the "add post" link.
		* Overloads the Generic3 hook.
		* @return string text for the "add post" link.
		*/	
		function get_add_item_link()
		{
			if ($this->related_mode) return false;
			if(empty($this->user_netID))
			{
				$this->user_netID = reason_check_authentication();
			}
			
			$ph = $this->get_post_group_helper();
			if($ph->group_has_members())
			{
				if($ph->requires_login()) // login required to post
				{
					if(empty($this->user_netID)) // not logged in
					{
						return '';
					}
					else // logged in
					{
						if($ph->has_authorization($this->user_netID)) // has authorization to post
						{
							return $this->make_add_item_link();
						}
						else // does not have authorization to post
						{
							return '';
						}
					}
				}
				else // No login required to post
				{ 
					return $this->make_add_item_link();
				}
			}
			else 
				return '';
		}
		
		/**
		*  Helper function to get_add_item_link() - returns the markup for the add item link.
		*  @return string the add item link
		*/
		function make_add_item_link()
		{
			if ($this->related_mode) return false;
			$link = array('add_item=true');
			if(!empty($this->textonly))
			{
				$link[] = 'textonly=1';
			}
			//if we've been looking at a particular issue, we want to be able to automatically set the issue value in the form
			if(!empty($this->issue_id))
			{
				$link[] = 'issue_id='.$this->issue_id;
			}
			//ditto if we've been looking at a particular section
			if(!empty($this->request['section_id']))
			{
				$link[] = 'section_id='.$this->request['section_id'];
			}

			//not using construct_link because we don't want to include a page value
			return '<div class="addItemLink"><a href ="?'.implode('&amp;',$link).'">Post to '.$this->publication->get_value('name').'</a></div>'."\n";
		}
		
		/**
		*  Checks to make sure that the given news item really belongs to this publication.
		*  @param entity $entity News item entity
		*  @return boolean True if the item belongs to this publication.
		*/
		function further_checks_on_entity( $entity )
		{
			// make sure the blog being requested actually belongs to the blog;
			// we don't want people mucking with the query strings with the effect that
			// something one person said is attributed to someone else
			// This  should return true if the entity looks OK to be shown and false if it does not.
			if(empty($this->items[$entity->id()]))
			{
				if($entity->has_left_relation_with_entity($this->publication, 'news_to_publication'))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		
		/**	
		* Displays the Blog Post Submission Disco form if a user is authorized to post to the blog. 
		* Overloads the Generic3 hook.
		*/	
		function add_item()
		{	
			$posting_group_helper = $this->get_post_group_helper();
						
			if($posting_group_helper->group_has_members())
			{
				if($posting_group_helper->requires_login())
				{
					if(empty($this->user_netID))
					{
						//$this->user_netID = $this->get_authentication();
						$this->user_netID = reason_check_authentication();
					}
					
					if(!empty($this->user_netID))
					{
						if($posting_group_helper->has_authorization($this->user_netID))
						{
							$this->build_post_form($this->user_netID);
						}
						else
						{
							echo 'You are not authorized to post on this publication.'."\n";
						}
					}
					else
						echo 'Please <a href="'.REASON_LOGIN_URL.'"> login </a> to post.'."\n";
				}
				else
				{
					$this->build_post_form('');
				}
			}
		}

		/**	
		* Helper function to add_item() - initializes & runs a BlogPostSubmissionForm object 
		* @param string user's netID
		*/	
		function build_post_form($net_id)
		{
			$form = new BlogPostSubmissionForm($this->site_id, $this->publication, $net_id);
			if(!empty($this->issue_id))
				$form->set_issue_id($this->issue_id);
			if(!empty($this->request['section_id']))
				$form->set_section_id($this->request['section_id']);
			$form->run();
		}

///////////////
//  ISSUE FUNCTIONS
///////////////	
		/**
		* Returns true if the publication can have issues related to it and has issues related to it.
		* This function only checks to see if a publication has issues if it SHOULD be able to have issues.
		* If an publication shouldn't have issues -- for example, a blog -- but has issues related to it anyway, this
		* function will return false.
		* @return boolean true if the publication has issues.
		*/
		function has_issues()
		{
			if($this->publication->get_value('has_issues') == "yes")
			{
				$issues =& $this->get_issues();
				if(!empty($issues)) return true;
			}
			return false;
		}
		
		/**
		* Returns an array of the issues associated with this publication.
		* Format: $issue_id => $issue_entity
		* @return array array of the issues for this publication
		*/
		function &get_issues()
		{
			if(empty($this->issues))
			{
				if($this->publication->get_value('has_issues') == "yes")
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting issues for this publication';
					$es->add_type( id_of('issue_type') );
					$es->limit_tables('dated');
					$es->limit_fields('dated.datetime');
					$es->set_order('dated.datetime DESC');
					$es->add_left_relationship( $this->publication->id(), relationship_id_of('issue_to_publication') );
					$this->issues = $es->run_one();
				}
			}
			return $this->issues;
		}
		
		/**
		* Returns an array of the issues associated with this publication.
		* Format: $issue_id => $issue_entity
		* @return array array of the issues for this publication
		*/
		function &get_issues_for_item()
		{
			static $issues;
			if (!isset($issues[$this->current_item_id]))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting issues for this news item';
				$es->limit_tables('dated');
				$es->limit_fields('dated.datetime');
				$es->add_type( id_of('issue_type') );
				$es->add_right_relationship( $this->current_item_id, relationship_id_of('news_to_issue') );
				$es->add_relation('entity.id IN ('.implode(", ", array_keys($this->get_issues())).')');
				$es->set_order('dated.datetime DESC');
				$issues = $es->run_one();
				$issues[$this->current_item_id] = $issues;
			}
			return $issues[$this->current_item_id];
		}
		
//		/**
//		*  Creates an issues array keyed by date instead of by id
//		*  @return array array of issues, formatted $datetime => $issue_entity
//		*/
//		function get_issues_by_date()
//		{
//			$issues_by_date = array();
//			if($this->has_issues())
//			{
//				return $this->issues;
//			}
//			return array();
//		}
		
		/**
		* Returns an array of links to each issue for this publication.
		* Format $issue_id => $issue_entity
		* @return array Array of links to each issue of this publication.
		*/
		function get_links_to_issues()
		{
			$links = array();
			if($this->has_issues())
			{
				$issues =& $this->get_issues();
				foreach($issues as $issue_id => $issue_entity)
				{
					$links[$issue_entity->id()] = $this->construct_link(NULL, array( 'issue_id'=>$issue_id, 'page'=> '1'  ) );
				}
			}
			return $links;
		}
		
		/**
		* Returns a copy of the issue entity that's currently being viewed.
		* If you want the issue with the most recent datetime, use {@link get_most_recent_issue()}.
		* @return object the issue entity that's currently being viewed.
		*/
		function get_current_issue()
		{
			if($this->has_issues() && !empty($this->issue_id))
			{
				$issues =& $this->get_issues();
				return $issues[$this->issue_id];
			}
			else
				return false;
		}
	
		/**
		* Returns a copy of the issue entity with the most recent datetime.
		* @return object the most recent issue entity
		*/
		function get_most_recent_issue()
		{
			$issues =& $this->get_issues();
			reset($issues); // make sure pointer is at first element		
			return current($issues);
		}

///////
///  NEWS SECTION FUNCTIONS
///////
		/**
		* Returns true if the publication has news sections related to it.
		* @return boolean true if the publication has sections.
		*/
		function has_sections()
		{
			if(!$this->related_mode && $this->publication->get_value('has_sections') == "yes")
			{
				$sections = $this->get_sections();
				if(!empty($sections))
				{
					return true;
				}
			}
			return false;
		}
		
		/**
		*  Returns an array of the news sections associated with this publication.
		*  Format: $section_id => $section_entity
		*  @return array Array of the news sections for the publication
		*/
		function get_sections()
		{
			if(empty($this->sections))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting news sections for this publication';
				$es->add_type( id_of('news_section_type'));
				$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_section_to_publication') );
				$es->set_order('sortable.sort_order ASC');
				$this->sections=$es->run_one();
			}
			return $this->sections;
		}
		
		/**
		*  Returns an array of all the items in the publication organized by section id.
		*  Format: [$section_id][$item_id] = $item_entity
		*  @return array All items of the publication organized by section id
		*/
		function get_items_by_section()
		{
			$items_by_section = array();
			foreach($this->items as $item)
			{
				if($this->has_sections() && $this->use_group_by_section_view())
				{
					$related_sections = $this->find_sections_for_this_item($item);
					$current_section = $this->get_current_section();
					if(!empty($related_sections))
					{
						foreach($related_sections as $section)
						{
							if(empty($current_section) || $section->id() == $current_section->id())
							{
								$items_by_section[$section->id()][$item->id()] = $item;
							}
						}
					}	
				}
				else
					 $items_by_section[$this->no_section_key][$item->id()] = $item;
			}
			
			if (!empty($items_by_section) && $this->has_sections() && $this->use_group_by_section_view())
			{
				$section_order = array_keys($this->get_sections());
				foreach ($section_order as $section_id)
				{
					if (array_key_exists($section_id, $items_by_section))
					{
						$items_by_section_ordered[$section_id] = $items_by_section[$section_id];
					}
				}
				$items_by_section = $items_by_section_ordered;
			}
			return $items_by_section;
		}
		
		
		/**
		*  Returns an array of the links to every section of this publication.
		*  Format: $section_id => $link_to_section
		*  Links in this case refers just to the url; does not include the <a> tag or the name of the link.
		*  @return array Links to every section of this publication.
		*/
		function get_links_to_sections()
		{
			$sections = $this->get_sections();
			$links = array();
			if(!empty($sections))
			{
				foreach($sections as $section_id => $section)
				{
					$link_args = $this->get_query_string_values(array('issue_id', 'page'=> 1));
					$link_args['section_id'] = $section->id();
					$links[$section->id()] = $this->construct_link(NULL, $link_args );
				}
			}
			return $links;
		}
		
		/**
		*  Return an array of the links to the sections that this news item is associated with.
		*  Format $section_id => ($section_name, $url)
		*  @param object $item The item entity.
		*  @return array Links to the sections that this item is related to.
		*/
		function get_links_to_sections_for_this_item($item)
		{
			$section_links = array();
			$related_sections = $this->find_sections_for_this_item($item);
			foreach($related_sections as $section)
			{
				$link_args = $this->get_query_string_values(array('issue_id', 'page'=> 1));
				$link_args['section_id'] = $section->id();
				$section_links[$section->id()]['url'] = $this->construct_link(NULL, $link_args );
				$section_links[$section->id()]['section_name'] = $section->get_value('name');
				#$section_links[$section->id()] = '<a href = "'.$url.'">'.$section->get_value('name').'</a>';
			}
			return $section_links;
		}
		
		/**
		* Returns an array of the sections of this publication that this news item is associated with.
		* Format: $section_id => $section_entity
		* @param object $item A news item entity
		* @return array The news sections of this publication that $item is associated with.
		*/
		function find_sections_for_this_item($item)
		{
			$related_sections_for_this_pub = array();
			
			$all_related_sections = $item->get_left_relationship( 'news_to_news_section' );
			if(!empty($all_related_sections))
			{
				foreach($all_related_sections as $section)
				{
					$sections = $this->get_sections();
					
					//check to make sure that this section is associated with this publication
					if(array_key_exists($section->id(), $sections))	
					{
						$related_sections_for_this_pub[$section->id()] = $section;
					}
				}
			}
			return $related_sections_for_this_pub;
		}
		
		/**
		* Returns array containing the issues of this publication that this news item is associated with.
		* @param object $item A news item entity
		* @return array issue entitities indexed by id
		*/
		function find_issues_for_this_item($item)
		{
			$es = new entity_selector($this->site_id);
			
			$all_related_sections = $item->get_left_relationship( 'news_to_news_section' );
			if(!empty($all_related_sections))
			{
				foreach($all_related_sections as $section)
				{
					$sections = $this->get_sections();
					
					//check to make sure that this section is associated with this publication
					if(array_key_exists($section->id(), $sections))	
					{
						$related_sections_for_this_pub[$section->id()] = $section;
					}
				}
			}
			return $related_sections_for_this_pub;
		}

		
		/**
		* Returns a copy of the news section entity that's currently being viewed.
		* @return object the news section entity that's currently being viewed.
		*/
		function get_current_section()
		{	
			$sections = $this->get_sections();
			if(!empty($sections) && !empty($this->request['section_id']))
			{
				return $sections[$this->request['section_id']];
			}
			else
				return false;
		}

		/**
		* If a section is specified in the request {@link request}, this constructs a link to view all all of the news 
		* items in this publication that are in the specified section regardless of what issue they're in.
		* "Link" in this case just means the url; no <a> tag, no name.
		* @todo Change this so that it has a section id as a parameter and looks for that rather than checking for a 
		*        section id in the {@link request}.
		* @return string Link to all news items in this section, regardless of issue.
		*/
		function get_all_items_in_section_link()
		{
			if(!empty($this->request['section_id']))
			{
				return $this->construct_link(NULL, array('section_id' => $this->request['section_id'], 'page'=> 1) );
			}
			else
				return false;
		}
		
		/**
		*  Determines whether or not news items should be grouped by section on the main list.  
		*  @return boolean True if news items should be grouped by section.
		*/
		function use_group_by_section_view()
		{
			if($this->group_by_section && $this->has_sections() && !$this->get_current_section())
				return true;
			else
				return false;
		}

///////////
// PERMISSION-RELATED FUNCTIONS
//////////

		/**
		*  Returns the group helper object for the group of users who can comment in this publication.
		*  @return object The group helper for users who can comment in this publication.
		*/
		function get_comment_group_helper()
		{
			$group = $this->get_comment_group();
			if (!empty($group))
			{
				$comment_group_helper = new group_helper();
				$comment_group_helper->set_group_by_entity($group);
				return $comment_group_helper;
			}
			return false;
		}
		
		/**
		*  Returns the group entity that represents the users authorized to comment in this publication.
		*  Helper function to {@link get_comment_group_helper()}.
		*  @return object The group of users who can comment.
		*/
		function get_comment_group()
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Getting groups for this publication';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_commenting_group') );
			$es->set_num(1);
			$groups = $es->run_one();	
			if(!empty($groups))
			{
				$comment_group = current($groups);
				return $comment_group;
			}
			else
			{
				trigger_error('No comment group assigned to publication id '.$this->publication->id().' - will return nobody group');
				return new entity(id_of('nobody_group'));
			}
		}
		
		function get_comment_moderation_state()
		{
			if($this->publication->get_value('hold_comments_for_review') == 'yes')
			{
				return true;
			}
			else
				return false;
		}
		
		/**
		*  Finds the group that represents users who can post news items to this publication.
		*  @return entity the group that represents users who can post news items to this publication.
		*/
		function get_post_group()
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Getting groups for this publication';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_posting_group') );
			$groups = $es->run_one();
			if(!empty($groups))
			{
				$post_group = current($groups);
				return $post_group;
			}
			else
			{
				trigger_error('No posting group assigned to publication id '.$this->publication->id().' - will return nobody group');
				return new entity(id_of('nobody_group'));
			}
		}
		
		/**
		*  Instantiates a group helper for the group that represents users who can post news items to this publication.
		*  @return entity the group helper for users who can post to this publication
		*/
		function get_post_group_helper()
		{
			reason_include_once( 'classes/group_helper.php' );
			
			$group = $this->get_post_group();
			$post_group_helper = new group_helper();
			$post_group_helper->set_group_by_entity($group);
			return $post_group_helper;
		}

////////////
/// MISC.
////////////
		function get_feed_url()
		{
			if ($this->related_mode) return false; // hmmm not sure what to do when publication is in related mode for feed_url
			if(empty($this->feed_url))
			{
				$blog_type = new entity(id_of('publication_type'));
				if($blog_type->get_value('feed_url_string'))
				{
					$site = new entity($this->site_id);
					$base_url = $site->get_value('base_url');
					$this->feed_url = $base_url.MINISITE_FEED_DIRECTORY_NAME.'/'.$blog_type->get_value('feed_url_string').'/'.$this->publication->get_value('blog_feed_string');
				}
			}
			if(!empty($this->feed_url))
			{
				return $this->feed_url;
			}
			else
			{
				return false;
			}
		}
		
		function get_login_logout_link()
		{
			if ($this->show_login_link)
			{
				$sess_auth = reason_check_authentication('session');
				$auth = reason_check_authentication('server');
				$ret = '<div class="loginlogout">';
				if(!empty($sess_auth))
				{
					$ret .= '<div class="loginlogout">Logged in: '.$sess_auth.' <a href="'.REASON_LOGIN_URL.'?logout=true">Log Out</a></div>';
				}
				elseif(!empty($auth))
				{
					$ret .= 'Logged in as '.$auth;
				}
				else
				{
					$ret .= '<a href="'.REASON_LOGIN_URL.'">Log In</a>';
				}
				$ret .= '</div>';
				return $ret;
			}
			else parent::get_login_logout_link();
		}

		/**
		*  Uses a list item markup generator to get the markup for each item of the list.
		*  @return array Array of markup strings, formatted $item_id => markup string of that item 
		*/
		function get_list_item_markup_strings()
		{
			$list_item_markup_strings = array();
			if($this->use_group_by_section_view())
			{
				//if we're grouping by section, we need to make sure that we limit the number of items per section
				$items_by_section = $this->get_items_by_section();
				foreach($items_by_section as $section_id => $items)
				{
					$num_per_section = $this->sections[$section_id]->get_value('posts_per_section_on_front_page');
					for($i=0; $i < $num_per_section; $i++)
					{
						$item = current($items);
						if(empty($item))
							break;
						next($items);
						$this->item = $item;
						$list_item_markup_generator = $this->set_up_generator_of_type('list_item', $item);
						$list_item_markup_strings[$item->id()] = $list_item_markup_generator->get_markup();
					}
				}
			}
			else
			{
				foreach($this->items as $item)
				{
					$this->item = $item;
					$list_item_markup_generator = $this->set_up_generator_of_type('list_item', $item);
					$list_item_markup_strings[$item->id()] = $list_item_markup_generator->get_markup();
				}
			}
			return $list_item_markup_strings;
		}
		
		/**
		*  Uses a featured item markup generator to get the markup for each featured item of the publication.
		*  @return array Array of markup strings, formatted $item_id => markup string of that item 
		*/		
		function get_featured_item_markup_strings()
		{
			$featured_item_markup_strings = array();
			$featured_items = $this->get_featured_items();

			//$es = new entity_selector( $this->site_id );
			//$es->description = 'Selecting featured news items for this publication';
			//$es->add_type( id_of('news') );
			//$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_featured_post') );
			//$temp = $es->run();
			//$featured_items = current($temp);
			
			if (!empty($featured_items))
			{
				foreach($featured_items as $id => $entity)
				{
					$featured_item_markup_generator = $this->set_up_generator_of_type('featured_item', $entity);
					$featured_item_markup_strings[$id] = $featured_item_markup_generator->get_markup();
				}
			}

			return $featured_item_markup_strings;
		}
		
		/**
		* Returns entity selector with featured items for the current publication - stored in a static variable
		* so that the entity selector will not be run multiple times by different modules or the same publication
		*/
		function get_featured_items()
		{
			if ($this->related_mode) return $this->get_related_featured_items();
			else
				{
				if ($this->show_featured_items == false) return array();
				static $featured_items;
				if (!isset($featured_items[$this->publication->id()]))
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting featured news items for this publication';
					$es->add_type( id_of('news') );
					$es->set_env('site', $this->site_id);
					$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_featured_post') );
					$es->add_rel_sort_field($this->publication->id(), relationship_id_of('publication_to_featured_post') );
					$es->set_order('rel_sort_order ASC');
					$temp = $es->run();
					$featured_items[$this->publication->id()] = current($temp);
				}
				return $featured_items[$this->publication->id()];
			}
		}
		
		function get_related_featured_items()
		{
			return array();
		}
		
		/**
		* Checks to see if there are existing values in $REQUEST for the given query strings.
		* @param $query_strings Array of variables whose values should be preserved in the new query string
		* @return array Query string variables with corresponding values
		*/	
		function get_query_string_values($query_strings)
		{
			$link_args = array();
			foreach($query_strings as $key=>$value)
			{
				if(is_int($key))
				{
					$query = $value;
					if(!empty($this->request[$query]))
						$link_args[$query] = $this->request[$query];
				}
				else
					$link_args[$key] = $value;
			}
			//If we're looking at the most recent issue, the issue id might not be set in the request array
			if(in_array('issue_id', $query_strings) && empty($this->request['issue_id']) && !empty($this->issue_id))
			{
				$link_args['issue_id'] = $this->issue_id;
			}
			return $link_args;
		}
		
		
		//overloaded from generic 3 so that we can preserve issue and section values in the links
		function get_pages_for_pagination_markup()
		{
			$pages = array();
			for($i = 1; $i <= $this->total_pages; $i++)
			{
				$args = $this->get_query_string_values(array('issue_id', 'section_id'));
				$args['page'] = $i;
				$pages[$i] = array('url' => $this->construct_link(NULL, $args) );
			}
			return $pages;
		}
		
		
		/**	
		* Returns the number of comments associated with a news item.
		* @param entity news item
		* @return int number of comments associated with news item
		*/	
		function count_comments($item)
		{
			
			$es = new entity_selector( $this->site_id );
			$es->description = 'Counting comments for this news item';
			$es->add_type( id_of('comment_type') );
			$es->add_relation('show_hide.show_hide = "show"');
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_comment') );
			return $es->get_one_count();
		}
	
		
		/**
		*  Returns the permalink to an item.
		*  @return string the url of the permalink
		*/
		function construct_permalink($item)
		{
			$link_frags = array();
			$link_frags[ $this->query_string_frag.'_id' ] = $item->id();
			$query_frags = array();
			foreach($link_frags as $key=>$value)		//hmm.  why do we have a foreach loop when there's only one thing in the array?
			{
				$query_frags[] = $key.'='.$value;
			}
			$link = '?'.implode('&amp;',$query_frags);
			return $link;
		}
		
		function get_link_to_full_item($item)
		{
			if($this->related_mode)
			{
				return $this->get_link_to_related_item($item);
			}
			else
			{
				$link_args = $this->get_query_string_values(array('issue_id', 'section_id'));
				return $this->construct_link($item, $link_args);
			}
		}
		
		function get_link_to_full_item_other_pub($item)
		{
			$link_args = $this->get_query_string_values(array('issue_id', 'section_id'));
			return $this->construct_link($item, $link_args);
		}
	
		// THIS STUFF SHOULD ALL BE IN MARKUP GENERATORS
		
		//overloaded from generic3
		function show_style_string()
		{
			echo '<div id="'.$this->style_string.'" class="publication">'."\n";
		}
		
		//overloaded from generic3
		function show_back_link()
		{
			echo '<div class="back">';
			
			$main_list_name = $this->publication->get_value('name');
			$current_issue = $this->get_current_issue();
			if(!empty($current_issue))
				$main_list_name .= ', '.$current_issue->get_value('name').' Issue';
			echo '<div><a href="'.$this->construct_back_link().'">Return to '.$main_list_name.'</a></div>';
			
			
			$current_section = $this->get_current_section();
			if(!empty($current_section))
			{
				$section_name = $current_section->get_value('name').' ('.$main_list_name.')';
				echo '<div><a href="'.$this->construct_back_to_section_link().'">Return to '.$section_name.'</a></div>';
			}
			
			echo '</div>';
		}
		
		function show_item_name( $item ) // {{{
		{
			echo '<h3>' . $item->get_value( 'release_title' ) . '</h3>'."\n";
		} // }}}
		
		function construct_back_link()
		{
			//need to go back to page 1, since we could have a page value wherever we are now
			$args = array('page' => 1);
			if(!empty($this->issue_id))
			{
				$args[] = 'issue_id';
			}
			return $this->construct_link(NULL, $this->get_query_string_values($args));	
		}
		
		function construct_back_to_section_link()
		{
			if(!empty($this->request['section_id']) && !empty($this->request[$this->query_string_frag.'_id']) )
			{
				$args = array('section_id');
				
				//if we're looking at a section from an issue, we want to still just be looking at items from that issue
				if(!empty($this->issue_id))
				{
					$args[] = 'issue_id';
				}	
				
				return $this->construct_link(NULL, $this->get_query_string_values($args));
			}
			else 
				return false;
		}
	
		function get_teaser_image($item)
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Finding teaser image for news item';
			$es->add_type( id_of('image') );
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
			$es->set_num (1);
			$result = $es->run_one();
			return $result;
		}
		
		function get_item_number($item)
		{
			$item_keys = array_keys($this->items);
			$item_order_by_key = array_flip($item_keys);
			return (in_array($item->id(), $item_keys)) ? $item_order_by_key[$item->id()] : false;
		}
	
		function get_link_to_related_item(&$item)
		{
			$pub_id_field = $item->get_value('publication_id');
			$pub_id = (is_array($pub_id_field)) ? array_shift($pub_id_field) : $pub_id_field;
			$links = $this->get_basic_links_to_current_publications();
			if(isset($links[$pub_id]))
			{
				return construct_link(array( $this->query_string_frag.'_id' => $item->id()), array('textonly'), $links[$pub_id]);
			}
			else
				return '';
		}
		function get_basic_links_to_current_publications()
		{
			if(empty($this->related_publications_links))
			{
				foreach($this->related_publications as $pub)
				{
					$page_id_field = $pub->get_value('page_id');
					$page_id = (is_array($page_id_field)) ? array_shift($page_id_field) : $page_id_field;
					if($page_id)
					{
						$this->related_publications_links[$pub->id()] = build_URL($page_id);
					}
				}
			}
			return $this->related_publications_links;
		}
		function get_links_to_current_publications()
		{
			$links = $this->get_basic_links_to_current_publications();
			if($this->textonly)
			{
				foreach($links as $id=>$link)
				{
					$links[$id] = $link.'?textonly=1';
				}
			}
			return $links;
		}
	}
?>
