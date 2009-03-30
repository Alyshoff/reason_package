<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Register the module with Reason and include the parent class
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FAQModule';
	reason_include_once( 'minisite_templates/modules/generic3.php' );

	/**
	 * A minisite module that lists FAQs
	 *
	 * By default, shows all FAQs on the current site
	 */
	class FAQModule extends Generic3Module
	{
		var $type_unique_name = 'faq_type';
		var $style_string = 'faq';
		var $other_items = 'Other FAQs';
		var $query_string_frag = 'faq';
		var $use_filters = true;
		var $filter_types = array(	'category'=>array(	'type'=>'category_type',
														'relationship'=>'faq_to_category',
													),
								);
		var $search_fields = array('entity.name','meta.description','meta.keywords','chunk.content','chunk.author');
		var $acceptable_params = array(
			'audiences'=>array(),
			'limit_to_current_site'=>true,
		);
		var $has_feed = true;
		var $feed_link_title = 'Subscribe to this feed for updates to this FAQ';
		var $make_current_page_link_in_nav_when_on_item = true;
		var $jump_to_item_if_only_one_result = false;
		
		function show_item_name( $item ) // {{{
		{
			echo '<h3 class="faqName">' . $item->get_value( 'description' ) . '</h3>'."\n";
		} // }}}
		function alter_es() // {{{
		{
			$this->es->set_order( 'entity.last_modified DESC' );
			if(!empty($this->params['audiences']))
			{
				$aud_ids = array();
				foreach($this->params['audiences'] as $audience)
				{
					$aud_id = id_of($audience);
					if($aud_id)
					{
						$aud_ids[] = $aud_id;
					}
					else
					{
						trigger_error($audience.' is not a unique name; skipping this audience');
					}
				}
				if(!empty($aud_ids))
				{
					$this->es->add_left_relationship($aud_ids, relationship_id_of('faq_to_audience'));
				}
			}
		} // }}}
		
		function show_list_item_name( $item )
		{
			echo $item->get_value( 'description' );
		}
		
		function show_list_item_desc( $item )
		{
			if($item->get_value('content'))
			{
				$desc_array = explode(' ',strip_tags($item->get_value('content')));
				echo '<div>'.implode(' ', array_slice( $desc_array, 0, 15)).'...</div>';
			}
		}
		function show_item_content( $item ) // {{{
		{
			echo '<div class="answer">';
			echo $item->get_value( 'content' );
			$datetime = false;
			if($item->get_value( 'datetime' ) && $item->get_value( 'datetime' ) != '0000-00-00 00:00:00')
				$datetime = $item->get_value( 'datetime' );

			$owner = $item->get_owner();
			if($item->get_value('author') || $datetime || $item->get_value( 'keywords' ) || $owner->id() != $this->site_id)
			{
				echo '<ul class="meta">';
				if($item->get_value('author') || $datetime)
				{
					echo '<li>';
					if($item->get_value('author'))
					{
						echo $item->get_value('author');
						if($datetime)
							echo ', ';
					}
					if($datetime)
						echo prettify_mysql_datetime( $datetime, "j F Y" );
					echo '</li>'."\n";
				}
				if($owner->id() != $this->site_id)
				{
					$url = $owner->get_value('base_url');
					if($this->textonly)
						$url .= '?textonly=1';
					echo '<li>FAQ courtesy of <a href="'.$url.'">'.$owner->get_value('name').'</a></li>'."\n";
				}
				if($item->get_value( 'keywords' ))
					echo '<li class="hide">Keywords: '.strip_tags($item->get_value( 'keywords' )).'</li>'."\n";
				echo '</ul>'."\n";
			}
			echo '</div>'."\n";
		} // }}}
	}
?>
