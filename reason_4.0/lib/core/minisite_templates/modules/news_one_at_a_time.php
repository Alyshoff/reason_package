<?php
	reason_include_once( 'minisite_templates/modules/news2.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'newsOneAtATimeModule';

class newsOneAtATimeModule extends News2Module
{
	var $num_per_page = 1;
	var $pagination_prev_next_texts = array('previous'=>'Previous','next'=>'Next');
	var $use_dates_in_list = false;
	var $style_string = 'newsOneAtATime';
	
	function show_list_item_pre( $item )
	{
		$es = new entity_selector( $this->parent->site_id );
		$es->description = 'Finding teaser image for news item';
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
		$es->set_num (1);
		$result = $es->run_one();
		if (!empty($result))
		{
			$image = current($result);
			echo '<div class="primaryImage">';
			echo '<img src="'.WEB_PHOTOSTOCK.$image->id().'.'.$image->get_value( 'image_type' ).'" width="'.$image->get_value( 'width' ).'" height="'.$image->get_value( 'height' ).'" alt="'.str_replace('"', "'", $image->get_value( 'description' )).'"/>';
			echo '</div>';
		}
	}
	
	function show_list_item_desc( $item )
	{
		if($item->get_value('description'))
			echo '<div class="desc">'.$item->get_value('description').'</div>'."\n";
			echo '<div class="more"><a href="'.$this->construct_link($item).'">Read more of "'.$item->get_value( 'release_title' ).'"</a></div>'."\n";
	}
}
?>
