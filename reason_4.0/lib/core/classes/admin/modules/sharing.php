<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/associator.php');
	
	/**
	 * An administrative module that provides an interface to borrow entities from another site
	 *
	 * You'd think this would be called BorrowModule, but you would be incorrect.
	 */
	class SharingModule extends AssociatorModule // {{{
	{
		function SharingModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function should_run()
		{
			return true;
		}
		function init() // {{{
		{
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'sharing.css');
			reason_include_once( 'classes/sharing_filter.php' );
			reason_include_once( 'content_listers/sharing.php' );
			$this->set_session_vars();

			$type = new entity( $this->admin_page->type_id );
			// save the type entity in an object scope
			$this->rel_type = $type;
			$this->get_views( $type->id() );
			if( empty( $this->views ) )//add generic lister if not already present
				$this->views = array();
			else
			{
				reset( $this->views );
				$c = current( $this->views );
				if( $c )
				{
					$lister = $c->id();
					$this->admin_page->request[ 'lister' ] = $lister;
				}
				else
					$lister = '';
			}	
			$this->admin_page->title = 'Borrowing ' . $type->get_value('name');
			if( $this->admin_page->is_second_level() )
				$this->admin_page->set_show( 'leftbar' , false );

			$this->viewer = new sharing_viewer;
			$this->viewer->set_page( $this->admin_page );
			if( !isset( $lister ) ) $lister = '';
			$this->viewer->init( $this->admin_page->site_id, $type->id(), $lister ); 
			
			$this->filter = new sharing_filter;
			$this->filter->set_page( $this->admin_page );
			$this->filter->grab_fields( $this->viewer->filters );

		} // }}}
		function set_session_vars() // {{{
		{
			//if (isset($this->admin_page->request['__old_cur_module']) && $this->admin_page->request['__old_cur_module'] == 'Associator') 
			if( $this->admin_page->is_second_level() )
			$_SESSION[ 'sharing' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] = $this->admin_page->make_link( array( 'cur_module' => 'Sharing' , 'PHPSESSID' => '') , true );
			else $_SESSION[ 'sharing_main' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] = $this->admin_page->make_link( array( 'cur_module' => 'Sharing' , 'PHPSESSID' => '') , true );
		} // }}}
		function show_next_nodes() // {{{
		{
			$finish_link = $this->admin_page->make_link( array( 'cur_module' => 'Lister' ) );
			
			echo '<a href="'.$finish_link.'">Back to Lister</a><br />';
		} // }}}
	} // }}}
?>