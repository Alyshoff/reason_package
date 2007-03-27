<?php
	reason_include_once('classes/admin/modules/default.php');
	class DoBorrowModule extends DefaultModule // {{{
	{
		function DoBorrowModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$mysession = false;
			$this->set_borrowship_first_level();
			if( $this->admin_page->is_second_level() )
			{
				$this->add_relationship_second_level();
				$session_check = isset ($_SESSION[ 'sharing' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ]);
				if ($session_check) $mysession = $_SESSION[ 'sharing' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ];
			}
			else 
			{
				$session_check = isset ($_SESSION[ 'sharing_main' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ]);
				if ($session_check) $mysession = $_SESSION[ 'sharing_main' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ];
			}

			if( $mysession )
				$link = unhtmlentities( $mysession );
			else
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Sharing' , 'id' => '' ) ) );
			header( 'Location: ' . $link );
			die();
		} // }}}
		function set_borrowship_first_level() // {{{
		{
			 //get relationship
			 $q = 'Select * from allowable_relationship where name = "borrows" AND relationship_a ='
				  . id_of( 'site' ) . ' AND relationship_b = ' . $this->admin_page->type_id;
			 $r = db_query( $q , 'Error selecting allowable relationship in borrow_form::finish()' );
			 $row = mysql_fetch_array( $r , MYSQL_ASSOC );
			 if( !empty( $this->admin_page->request[ 'unborrow' ] ) )
			 {
				 //do query removing borrowship
				 delete_borrowed_relationship( $this->admin_page->site_id , $this->admin_page->id , $row[ 'id' ] );
			 }
			 else
			 {
				 //do query creating borrowship
				create_relationship( $this->admin_page->site_id ,
							$this->admin_page->id,
 							$row[ 'id' ] );
			 }
		} // }}}
		function add_relationship_second_level() //{{{
		{
			if(empty( $this->admin_page->request[ 'unborrow' ] ) )
			{
				create_relationship( $this->admin_page->request[ CM_VAR_PREFIX . 'id' ],
							$this->admin_page->id,
 							$this->admin_page->request[ CM_VAR_PREFIX . 'rel_id' ] );
			}
		} // }}}
		function run() // {{{
		{
		} // }}}
	} // }}}
?>
