<?php
reason_include_once( 'minisite_templates/modules/publication/list_item_markup_generators/default.php' );


/**
*  Simple extension of the default list item markup generator to show full image instead of thumbnails for each item
*  The markup generator also does not display the permalink.
*
*  @package reason
*  @subpackage minisite_modules
*  @author Nathan White
*
*/
class FullImageListItemMarkupGenerator extends PublicationListItemMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 	'use_dates_in_list', 
									'date_format', 
									'item',
									'item_comment_count', 
									'link_to_full_item', 
									'section_links',
									'teaser_image'
									);

	function FullImageListItemMarkupGenerator ()
	{
	}
	
/////
// show_list_item methods
/////
	
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = current($this->passed_vars['teaser_image']);
		if (!empty($image))
		{
			$markup_string .= '<div class="primaryImage">';
			$markup_string .= '<img src="'.WEB_PHOTOSTOCK.$image->id().'.'.$image->get_value( 'image_type' ).'" width="'.$image->get_value( 'width' ).'" height="'.$image->get_value( 'height' ).'" alt="'.str_replace('"', "'", $image->get_value( 'description' )).'"/>';
			$markup_string .= '</div>';
		} 
		return $markup_string;
	}
}
?>
