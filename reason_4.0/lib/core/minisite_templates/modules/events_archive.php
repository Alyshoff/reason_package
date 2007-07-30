<?php 
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsArchiveModule';


class EventsArchiveModule extends EventsModule
{
	var $show_calendar_grid = false;
	var $show_views = false;
	
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$array = parent::make_reason_calendar_init_array($start_date, $end_date, $view);
		$array['start_date'] = '1970-01-01';
		$array['view'] = 'all';
		return $array;
	}
	function show_navigation()
	{
	}
	function show_focus()
	{
	}
	function get_today_link()
	{
	}
	function get_archive_toggler()
	{
	}
	function show_date_picker()
	{
	}
}
?>
