<?php
	/**
	 * ContentManager is derived from Disco - its purpose to show and manage the content type editing screens for reason.
	 *
	 * Other content handlers are derived from this class to show specific form elements or any other necessary specific work.
	 *
	 * Other content handlers MUST SET $content_handler to the name of the class
	 * @author Brendon Stanton and Dave Hendler 2002 - 2003
	 * @package reason
	 * @subpackage content_managers
	 */

	/**
 	 * this line is important - make sure any content handlers have this variable set in their include files!!!!
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ContentManager';
	
	/**
	 * Necessary Includes
	 */
	reason_include_once( 'classes/disco.php' );
	reason_include_once( 'classes/entity.php');
	reason_include_once( 'function_libraries/admin_actions.php' );

	//Form comment function
	//moved to disco.php3

	/**
	 * Default Content Manager Class
	 *
	 * This is the default content manager class for reason.  It handles getting
	 * the basic fields, and setting them up appropriately.  Basically, this should
	 * work fine for any form as long as you don't need to do anything special. 
	 */	
	class ContentManager extends DiscoReason2
	{
		/**
	   	 * @access private
	 	 */
		var $_required_relationships = array();

		var $left_assoc_display_names = array();
		var $left_assoc_omit_relationship = array();
		var $left_assoc_omit_link = array();
		
		var $right_assoc_display_names = array();
		var $right_assoc_omit_relationship = array();
		var $right_assoc_omit_link = array();

		var $actions = array( 'stay_here' => 'Save and Continue Editing' , 'assoc' => 'Save and Associate' , 'finish' => 'Save and Finish' );

		function init( $externally_set_up = false)
		{
			$this->init_head_items();
			parent::init();
		}
		
		/**
		 * Add head items to the head_items object if head_items need to be added by the content manager
		 */
		function init_head_items()
		{
		}
		
		/**
		 * Monster function that sets up all the basics.
		 *
		 * Basically, there are a lot of fields we don't want to show, so we
		 * cut them out.  Also, it deals with a lot of the sharing stuff.
		 * This should probably never be overloaded.  If there's more stuff you 
		 * want to do on load, you should find some other place to do it.  If you 
		 * do need to overload it, you probably want to have:
		 * <code>
		 * parent::prep_for_run( $site_id , $type_id , $id , $user_id );
		 * </code>
		 * at the top of the function.
		 * @param int $site_id id of the site
		 * @param int $type_id id of the current type
		 * @param int $id id of the entity we're editing
		 * @param int $user_id id of the current user...this could bet the actual user or it could be the user the actual user is pretending to be if they're admin
		 * @return void
		 */
		function prep_for_run( $site_id, $type_id, $id, $user_id ) // {{{
		{
			$this->load_by_type( $type_id, $id, $user_id );
			$this->load_associations();

			if( !empty( $id ) )
			{
				$this->entity = new entity( $id, false );
				$this->entity->get_values();
				$this->entity->get_relationships();
			}
			
			// make sure to let MySQL auto handle the last_modified field - don't let the user see it
			$this->remove_element( 'last_modified');
			$this->remove_element( 'creation_date');
	
			// also hide the "new" field
			$this->remove_element( 'new' );

			// also hide the "created_by" field
			$this->remove_element( 'created_by' );

			// we now have sorting handled in its own place, so we don't need to show sort order
			if($this->_is_element('sort_order'))
				$this->remove_element( 'sort_order' );
	
			// maintain variables for site management navigation
			$this->add_element( 'type_id', 'hidden' );
			$this->set_value( 'type_id', $type_id );
			$this->add_element( 'site_id', 'hidden' );
			$this->set_value( 'site_id', $site_id );
			$this->add_required( 'name' );
			$this->change_element_type( 'state' , 'hidden' );

			if( site_shares_type($this->get_value( 'site_id' ), $this->get_value( 'type_id' )) )
			{
				$this->change_element_type( 'no_share', 'select', array( 'options' => array( 'Shared', 'Private' ) ) );
				$this->set_display_name( 'no_share', 'Sharing' );
				$new_order = $this->get_order();
				unset($new_order['no_share']);
				/*$new_order = array();			
				foreach( $this->_elements AS $k => $v )
				{
					if( $k != 'no_share' )
						$new_order[] = $k;
				} 
				$new_order[] = $k; */
				$this->set_order( $new_order );
				$d = new DBSelector;
				$d->add_table( 'ar' , 'allowable_relationship' );
				$d->add_table( 'r' , 'relationship' );
				$d->add_table( 'entity' , 'entity' );

				$d->add_field( 'entity' , '*' );

				$d->add_relation( 'ar.name = "borrows"' );
				$d->add_relation( 'ar.id = r.type' );
				$d->add_relation( 'r.entity_b = ' . $this->admin_page->id );
				$d->add_relation( 'r.entity_a = entity.id' );

				$x = $d->run();
				
				$comments = 'Your site is currently sharing this type.  Select private to prevent other sites from borrowing this item. ';

				if( $x )
				{
					$comments .= 'This item is currently being borrowed by the following site';
					if( count( $x > 1 ) )
						$comments .= 's';
					$comments .= ': ';
					$first = true;
					foreach( $x AS $ent )
					{
						if( !$first )
							$comments .= ', ';
						else
							$first = false;

						$comments .=  $ent[ 'name' ];
					}
					$comments .= '.';
				}
				else
					$comments .= 'No other site is currently borrowing this item.';
				$this->set_comments( 'no_share' , form_comment( $comments ) );
			}
			else
				$this->change_element_type( 'no_share', 'hidden' );

			//$this->set_assoc( $id , $type_id , $site_id );
			$this->alter_data();
			$this->alter_display_names();
			$this->alter_comments();
			
			if( !empty( $this->admin_page->request ) )
				$this->grab_all_page_requests();
			// if no relationships, don't show the relationship button
			$rels = $this->admin_page->get_rels();
			if( empty( $rels ) )
				unset( $this->actions[ 'assoc' ] );

			// if the state of the entity is pending, show the queue review actions
			// instead of the regular actions
			if( !$this->is_new_entity() AND $this->entity->get_value( 'state' ) == 'Pending' AND $this->admin_page->type_id == id_of( 'image' ) )
			{
				unset( $this->actions );
				// check for user role.  If contributor, change the name of the button.  The FinishModule is smart
				// enough to not set this item as live if the user is a contributor.
				if( !user_is_a( $this->admin_page->user_id, id_of( 'contribute_only_role' ) ) )
				{
					$this->actions[ 'publish_and_next' ] = 'Publish and go to Next';
				}
				else
				{
					$this->actions[ 'publish_and_next' ] = 'Save and go to Next';
				}
				$this->actions[ 'delete_and_next' ] = 'Delete and go to Next';
				$this->actions[ 'next' ] = 'Do Nothing and go to Next';
				$this->actions[ 'cancel' ] = 'Do Nothing and Return to the List';



				// grab this for the chosen actions section
				$this->next_entity = $this->admin_page->get_oldest_pending_entity( $this->admin_page->site_id,
																				   $this->admin_page->type_id,
																				   $this->entity->id(),
																				   $this->entity->get_value( 'last_modified' ) );
				// at the end of the queue.  go back to beginning.
				if( empty( $this->next_entity ) )
				{
					$this->next_entity = $this->admin_page->get_oldest_pending_entity( $this->admin_page->site_id,
																					   $this->admin_page->type_id );
					// if still nothing in the queue, we're done with all items
					if( empty( $this->next_entity ) )
					{
						// umm.  back to the list.

					}
				}
			}
		} // }}}
		
		/**
		 * Accept a reference to the head items so that content managers can interact with head items directly
		 * @author Nathan White
		 */
		function set_head_items(&$head_items)
		{
			$this->head_items =& $head_items;
		}
		
		/**
		 * This function is used when you're editting an entity within editing another entity
		 * @return void
		 * @todo get rid of references to global variables and make them local 
		 */
		function load_associations() // {{{
		{
			global $rel_id , $rel_entity_a , $rel_entity_b;		

			if( $rel_id AND ( $rel_entity_a OR $rel_entity_b ) )
			{
				$this->add_element( 'rel_id' , 'hidden' );
				$this->set_value( 'rel_id' , $rel_id );
				
				if( $rel_entity_a )
				{
					$this->add_element( 'rel_entity_a' , 'hidden' );
					$this->set_value( 'rel_entity_a' , $rel_entity_a );
					$e = new entity( $rel_entity_a );
				}
				else
				{
					$this->add_element( 'rel_entity_b' , 'hidden' );
					$this->set_value( 'rel_entity_b' , $rel_entity_b );
					$e = new entity( $rel_entity_b );
				}
				
				$t = 'Save and return to editing ' . $e->get_value( 'name' );
				
				$this->actions = array( 'return' => $t );
			}
		} // }}}
		/**
		 * grabs request fields and pops them into the forms values if it finds them
		 * @return void
		 */
		function grab_all_page_requests() // {{{
		{
			foreach( $this->admin_page->request AS $request => $value )
			{
#				if( !isset( $this->_elements[ $request ] ) AND !in_array( $request, $this->_ignored_fields ) )
				if( !$this->_is_element($request) AND !in_array( $request, $this->_ignored_fields ) )
				{
					$this->add_element( $request , 'hidden' );
					$this->set_value( $request , $value );
				}
			}
		} // }}}
		
		/**#@+
		 * Overloadable function for classes that extend this
		 */
		function alter_data() // {{{
		{
			//overloadable function
		} // }}}
		function alter_display_names() // {{{
		{
			//overloadable function 
		} // }}}
		function alter_comments() // {{{
		{
			//overloadable function
		} // }}}
		/**#@-*/

		// site management page display functions

		/**
		 * Called before the form is drawn
		 *
		 * This can be overloaded in some cases, but is generally not used.
		 *
		 * This function was more important for non-reason uses of Disco
		 * where the form was responsible for creating a lot more of the page
		 * @return void
		 */
		function pre_show_form() // {{{
		{
			if( empty( $_SESSION[ 'listers' ][ $this->get_value( 'site_id' ) ][ $this->get_value( 'type_id' ) ] ) )
				$_SESSION[ 'listers' ][ $this->get_value( 'site_id' ) ][ $this->get_value( 'type_id' ) ] =
						'?site_id=' . $this->get_value( 'site_id' ) . '&amp;type_id=' . $this->get_value( 'type_id' );
			if($this->get_value('entity_saved') && !$this->_has_errors())
			{
				$e = new entity($this->get_value('id'));
				echo '<h4 class="saved">Saved at ' . prettify_mysql_timestamp( $e->get_value( 'last_modified' ), 'g:i A' ) . '</h4>';
			}
		
		} // }}}
	
		/**#@+
		 * Called right before the form finishes
		 *
		 * In old Disco, finish would either return a link or return true.
		 * If it returned true, it would redirect to the current page, otherwise
		 * it would redirect to wherever the link said.  However, this was a bit of 
		 * a strain on the finish function.  To fix this problem, the where_to()
		 * function was created.  The Content_Manager still pays attention (I think)
		 * to what this returns, but where_to() takes priority.  CMfinish was 
		 * built as an extension to finish specifically for reason.
		 * @return void
		 */
		function CMfinish() // {{{
		{
			return true;
		} // }}}
		function finish() // {{{
		{
			return $this->CMfinish();
		} // }}}
		/**#@-*/
		
		/**
		 * This is called by the Admin Finish Module.
		 * Overload it if you want something special to happen when the entity is finished.
		 * @return void
		 */
		function run_custom_finish_actions() // {{{
		{
		} // }}}
		/**
		 * Deletes relationship info
		 * @param int $rid actual id in ar table
		 * @param int $ent_id entity's id, used to make sure current entity is actually part of that relationship so we don't erase something by accident
		 * @param string $side either 'right' or 'left' depending on what side the entity is supposed to be on
		 * @return void
		 */
		function delete_relationship_info( $r_id , $ent_id , $side ) // {{{
		{
			if( $side == 'left' )
			{
				$q = 'DELETE FROM relationship WHERE type = ' . $r_id . ' AND entity_a = ' . $ent_id;
				db_query( $q , 'Error deleting existing relationships' );
			}
			elseif( $side == 'right' )
			{
				$q = 'DELETE FROM relationship WHERE type = ' . $r_id . ' AND entity_b = ' . $ent_id;
				db_query( $q , 'Error deleting existing relationships' );
			}
		} // }}}
		/**
		 * pending queue has some actions that need to fire before error 
		 * checks are run to avoid coming back to the form when there is
		 * no reason to come back to the form
		 * @return void
		 */
		function pre_error_check_actions() //{{{
		{
			$link = '';
			// in pending queue, skip chosen
			if( $this->chosen_action == 'next' )
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Editor', 'id' => $this->next_entity->id() ) ) );
			}
			elseif( $this->chosen_action == 'cancel' )
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Lister', 'state' => 'pending', 'id' => '') ) );
			}
			// in pending queue, delete chosen
			elseif( $this->chosen_action == 'delete_and_next' )
			{
				// get id of next object 
				$q = 'UPDATE entity SET state = "Deleted" where id = ' . $this->entity->id();
				db_query( $q , 'Error setting state as deleted in deleteDisco::finish()' );
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Editor', 'id' => $this->next_entity->id() ) ) );
			}
			if( !empty( $link ) )
			{
				header( 'Location: '.$link );
				die();
			}
		} // }}}
		/**
		 * determine button pressed and route accordingly
		 * 
		 * see finish() and CMfinish()
		 * @return string link of where to go when form is done
		 */
		function where_to() // {{{
		{
			if( $this->chosen_action == 'finish' )
			{
				$link = unhtmlentities( $this->admin_page->make_link(  array( 'cur_module' => 'Finish' )/*,true*/ ) );
			}
			elseif( $this->chosen_action == 'assoc' )
			{
				$links = $this->admin_page->get_main_links();
				$found = false;
				foreach( $links AS $ass )
				{
					if( isset( $ass[ 'rel_info' ] ) && !$found )
					{
						$found = true;
						$link = unhtmlentities( $ass[ 'link' ] ); 
					}
				}
			}
			// in pending queue, publish chosen
			elseif( $this->chosen_action == 'publish_and_next' )
			{
				// transition to finish and make sure finish knows we're in queue mode
				// so that it can hand off the control to the next editor
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Finish', 'next_entity' => $this->next_entity->id() ) ) );
			}
			else
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'id' => $this->_id , 'cur_module' => 'Editor' , 'submitted' => false , 'entity_saved' => true ) ) );
			}
			return $link; 
		} // }}}
	
		/**	
		 * returns true if entity has associtation
		 * @return boolean
		 */
		function has_associations() // {{{
		{
			$d = new DBSelector;

			$d->add_table('ar','allowable_relationship' );
			$d->add_table('type','entity');
		
			$d->add_table('allowable_relationship');
			$d->add_table('relationship');
			
			$d->add_relation( 'allowable_relationship.name = "site_to_type"' );
			$d->add_relation( 'allowable_relationship.id = relationship.type' );
			$d->add_relation( 'relationship.entity_a = '.$this->get_value( 'site_id' ));
			$d->add_relation( 'relationship.entity_b = type.id' );
		
			$d->add_field('type','id');
			$d->add_field('type','name');
			
			$d->add_field('ar','name','rel_name');
			$d->add_field('ar','display_name');
			$d->add_field('ar','id','rel_id');
	
			$d->add_relation('ar.relationship_a = '.$this->get_value('type_id') );
			$d->add_relation('ar.relationship_b = type.id');
			$d->add_relation('ar.name != "owns"');

			return ($d->run() ? true : false);
		} // }}}
		/**
		 * Does some stuff and returns an array of info about the 
		 * entity's ARs
		 * @return array
		 */
		function allowable_relationship_object() // {{{
		{
			$d = new DBSelector;

			$d->add_table('ar','allowable_relationship' );
			$d->add_table('type','entity');
		
			$d->add_table('allowable_relationship');
			$d->add_table('relationship');
			
			$d->add_relation( 'allowable_relationship.name = "site_to_type"' );
			$d->add_relation( 'allowable_relationship.id = relationship.type' );
			$d->add_relation( 'relationship.entity_a = '.$this->get_value( 'site_id' ));
			$d->add_relation( 'relationship.entity_b = type.id' );
		
			$d->add_field('type','id');
			$d->add_field('type','name');
			
			$d->add_field('ar','name','rel_name');
			$d->add_field('ar','display_name');
			$d->add_field('ar','id','rel_id');
	
			$d->add_relation('ar.relationship_a = '.$this->get_value('type_id') );
			$d->add_relation('ar.relationship_b = type.id');
			$d->add_relation('ar.name != "owns"');
			$d->add_relation('ar.name not like "%archive%"');

			return $d;
		} // }}}

	}
?>
