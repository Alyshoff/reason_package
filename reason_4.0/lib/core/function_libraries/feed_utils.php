<?php

function make_feed_link( $url, $title = 'Link to feed', $text = 'xml' )
{
	$ret = '<div class="feedInfo"><a href="'.$url.'" title="'.$title.'">'.$text.'</a></div>';
	if(defined('REASON_URL_FOR_GENERAL_FEED_HELP'))
	{
		$ret .= '<div class="feedHelp"><a href="'.REASON_URL_FOR_GENERAL_FEED_HELP.'" title="More information about feeds">What is this?</a></div>';
	}
	return $ret;
}

function get_feed_as_text( $params )
{
	if(!empty($params['type_id']))
	{
		$type = new entity( $params['type_id'] );
		
		if(!empty($params['feed'])) //use requested feed script if given
		{
			$feed_file = $params['feed'];
		}
		elseif($type->get_value('custom_feed')) // otherwise use the type's custom feed script
		{
			$feed_file = str_replace('.php', '', $type->get_value('custom_feed') );
		}
		else
		{
			$feed_file = 'default'; // otherwise use default feed script
		}
			
		reason_include_once( 'feeds/'.$feed_file.'.php' );
		
		$feed_class = $GLOBALS[ '_feed_class_names' ][ $feed_file ];
		
		if(!empty($params['site_id']))
		{
			$site = new entity($params['site_id']);
			$feed = new $feed_class( $type, $site );
		}
		else
		{
			$feed = new $feed_class( $type );
		}
		
		$feed->set_request_vars($params);
		ob_start();
		$feed->run(false);
		$feed_text = ob_get_contents();
		ob_end_clean();
		if(!empty($feed_text))
		{
			return $feed_text;
		}
	}
}

function get_links_from_rss_string( $rss )
{
	include_once('XML/Unserializer.php');
	$unserializer = &new XML_Unserializer();
	$unserializer->unserialize($rss);
	$type_feed_data = $unserializer->getUnserializedData();
	$links = array();
	if (isset($type_feed_data['channel']['item']))
	{
 		foreach($type_feed_data['channel']['item'] as $k=>$v)
 		{
 			if (is_array($v) && isset($v['link'])) 
 			{
 				$links[] = trim(str_replace('&amp;','&',$v['link']));
 			}
 			else if ($k == 'link' && (!empty($v))) $links[] = trim(str_replace('&amp;','&',$v));
 		}
 	}
 	return $links;
}
?>
