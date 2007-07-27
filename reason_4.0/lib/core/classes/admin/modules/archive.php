<?php
	reason_include_once('classes/admin/modules/default.php');
	class ArchiveModule extends DefaultModule
	{
		var $content_transform = array(
			'form'=>array('thor_content'=>'specialchars'),
			'minisite_page'=>array('extra_head_content'=>'specialchars'),
		);
		// basic node functionality
		function ArchiveModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'archive.css');
			$this->current = new entity( $this->admin_page->id );

			$this->admin_page->title = 'History of "'.$this->current->get_value('name').'"';

			$this->ignore_fields = array( 'id', 'last_edited_by', 'last_modified', 'creation_date', 'type', 'created_by', 'new', 'state' );

			// get archive relationship id
			$q = 'SELECT id FROM allowable_relationship WHERE name LIKE "%archive%" AND relationship_a = '.$this->admin_page->type_id.' AND relationship_b = '.$this->admin_page->type_id;
			$r = db_query( $q, 'Unable to get archive relationship.' );
			$row = mysql_fetch_array( $r, MYSQL_ASSOC );
			mysql_free_result( $r );
			$this->rel_id = $row['id'];

			$es = new entity_selector();
			$es->add_type( $this->admin_page->type_id );
			$es->add_right_relationship( $this->admin_page->id, $this->rel_id );
			$es->set_order( 'last_modified DESC, entity.id DESC' );
			$archived = $es->run_one(false,'Archived');

			$history_top = array( $this->current->id() => $this->current );

			$this->history = $history_top + $archived;
		} // }}}
		function run() // {{{
		{
		/*
			echo '<a href="'.$this->admin_page->make_link( array( 'wfn' => 'edit','id' => $this->admin_page->id )).'">Back to Editing</a><br />';
			echo '<br />';

			echo '<a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id ) ).'">Main Archive</a> ';
			echo '| <a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'full_history' ) ).'">Full History</a> ';
			echo '| <a href="'.$this->admin_page->make_link( array( 'wfn' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'compare_all' ) ).'">Compare All</a>';
			echo '<br /><br />';
*/
			$archive_page = 'show_'. ( !empty( $this->admin_page->request['archive_page'] ) ? $this->admin_page->request['archive_page'] : 'compare' );

			$this->$archive_page();

		} // }}}

		// archive node internal pages

		function show_archive_main() // {{{
		{
			$current_entity = true;
			foreach( $this->history AS $id => $entity )
			{
				$user = new entity( $entity->get_value('last_edited_by') );
				if($user->get_values())
					$name = $user->get_value('name');
				else
					$name = 'user ID '.$user->id();
				echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_id' => $id, 'archive_page' => 'entity') ).'">'.$this->get_archive_name( $id ).'</a> - modified by '.$name.'<br /><br />';
			}
		} // }}}
		function show_full_history() // {{{
		{
			echo '<hr />';
			$current_entity = true;
			foreach( $this->history AS $id => $entity )
			{
				echo '<strong>'.$this->get_archive_name( $id ).'</strong><br />';
				$this->display_entity( $id );
				echo '<hr />';
			}
		} // }}}
		function show_compare_all() // {{{
		{
			$first = true;
			foreach( $this->history AS $id => $entity )
			{
				if( $first )
				{
					$prev = $id;
					$first = false;
				}
				else
				{
					$this->diff_entities( $prev, $id );
					echo '<hr />';
					$prev = $id;
				}
			}
		} // }}}
		function show_compare_most_recent() // {{{
		{
			reset( $this->history );
			list( $a, ) = each( $this->history );
			list( $b, ) = each( $this->history );

			$this->diff_entities( $a, $b );
		} // }}}
		function show_entity() // {{{
		{
			$id = $this->admin_page->request[ 'archive_id' ];

			echo '<table><tr><td valign="top" align="left">';

			$this->display_entity( $id );

			echo '</td><td valign="top" align="left">';


			foreach( $this->history AS $eid => $entity )
			{
				if( $eid != $id )
				{
					echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'compare', 'archive_a' => $id, 'archive_b' => $eid ) ).'">Compare with '.$this->get_archive_name( $eid ).'</a><br />';
				}
			}

			
			echo '</td></tr></table>';
			echo '<br />';
			echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'archive', 'id' => $this->admin_page->id, 'archive_page' => 'confirm_reinstate', 'archive_id' => $id) ).'">Make this version current</a> - this will make this version live.  The current version will be archived.<br /><br />';
		} // }}}
		function show_confirm_reinstate() // {{{
		{
			?>
				Reinstating this version (<?php echo $this->get_archive_name( $this->admin_page->request['archive_id'] ); ?>) will change the current item.  Changes made to the most current will be archived and accessible through this very Archive Manager.  So don't worry.<br />
				<br />
				<a href="<?php echo $this->admin_page->make_link( array( 'id' => $this->admin_page->id, 'archive_page' => 'reinstate', 'archive_id' => $this->admin_page->request[ 'archive_id' ]) ) ?>">Confirm</a> | <a href="<?php echo $this->admin_page->make_link( array() ); ?>">Cancel</a><br />
			<?php
		} // }}}
		function show_reinstate() // {{{
		{
			$id = $this->admin_page->request[ 'archive_id' ];

			$e = new entity( $id );
			$values = $e->get_values();
			$values[ 'state' ] = 'Live';

			update_entity( $this->admin_page->id, $this->admin_page->user_id, values_to_tables( get_entity_tables_by_id( $id ), $values, array('id','last_modified') ) );

			header( 'Location: '.unhtmlentities($this->admin_page->make_link( array( 'id' => $this->admin_page->id ) ) ) );
			die();
		} // }}}
		function show_compare() // {{{
		{
			$a = !empty( $this->admin_page->request[ 'archive_a' ] ) ? $this->admin_page->request[ 'archive_a' ] : $this->admin_page->id;
			$b = !empty( $this->admin_page->request[ 'archive_b' ] ) ? $this->admin_page->request[ 'archive_b' ] : '';
			$this->diff_entities( $a, $b );
		} // }}}

		// support methods

		function get_archive_name( $id ) // {{{
		{
			$user = new entity( $this->history[ $id ]->get_value( 'last_edited_by' ) );
			if($user->get_values())
				$name = $user->get_value('name');
			else
				$name = 'user id '.$user->id();
			if( $id == $this->current->id() )
				return 'Current Version - '.$name;
			else
				return prettify_mysql_timestamp($this->history[ $id ]->get_value('last_modified'), 'n/j/y, g:i a') . ' Version - '.$name;
		} // }}}
		function display_entity( $id, $use_ignore_fields = true ) // {{{
		{
			$entity =& $this->history[ $id ];
			$entity_values = $entity->get_values();
			echo '<table border="1" cellpadding="4">';
			foreach( $entity_values AS $key => $val )
			{
				if( !$use_ignore_fields OR !in_array( $key, $this->ignore_fields ) )
				{
					echo '<tr>';
					echo '<td>'.prettify_string( $key ).'</td>';
					echo '<td>'.$val.'</td>';
					echo '</tr>';
				}
			}
			echo '</table>';
		} // }}}
		function diff_entities( $a_id, $b_id, $use_ignore_fields = true ) // {{{
		{
			$a =& $this->history[ $a_id ];
			if( !empty( $b_id ) )
				$b =& $this->history[ $b_id ];
			
			if( $use_ignore_fields )
				$keys = array_diff( array_keys( $a->get_values() ), $this->ignore_fields );
			else
				$keys = array_keys( $a->get_values() );

			if( empty( $b_id ) )
				$compare_or_comparing = 'Compare';
			else
				$compare_or_comparing = 'Comparing';

			$select_form_a = '<form name="form2">'.$compare_or_comparing.'
				<select name="menu2" onChange="MM_jumpMenu(\'parent\',this,0)" class="siteMenu">
					';
			$select_form_b = '<form name="form3">with
				<select name="menu3" onChange="MM_jumpMenu(\'parent\',this,0)" class="siteMenu">
					<option value="'.$this->admin_page->make_link( array( 'archive_b' => '' ), true ).'"'.(empty( $b_id ) ? ' selected="selected"' : '' ).'>--</option>';
			foreach( $this->history AS $h )
			{
				$select_form_a .= '<option value="' . $this->admin_page->make_link( array( 'archive_a' => $h->id() ) , true );
				if( $a->id() == $h->id() )
					$select_form_a .= '" selected="selected';
				$select_form_a .= '">'. $this->get_archive_name( $h->id() ) . "</option>\n";
				$select_form_b .= '<option value="' . $this->admin_page->make_link( array( 'archive_b' => $h->id() ) , true );
				if( !empty( $b_id ) AND $b->id() == $h->id() )
					$select_form_b .= '" selected="selected';
				$select_form_b .= '">'. $this->get_archive_name( $h->id() ) . "</option>\n";
			}
			$select_form_a .=
				'</select>
				</form>';
			$select_form_b .=
				'</select>
				</form>';

			echo '<table border="0 cellspacing="0" cellpadding="4">';
			echo '<tr>';
			echo '<th class="listHead" align="left">Field</th>';
			echo '<th class="listHead">'.$select_form_a .'</th>';
			echo '<th class="listHead">'.$select_form_b.'</th>';
			echo '</tr>';
			
			$type = new entity($this->admin_page->type_id);
			if(!empty($this->content_transform[$type->get_value('unique_name')]))
			{
				$transformers = $this->content_transform[$type->get_value('unique_name')];
			}
			
			foreach( $keys AS $key )
			{
				$diff = false;
				if( !empty( $b_id ) )
					if( $a->get_value( $key ) != $b->get_value( $key ) )
						$diff = true;

				if( $diff )
					$class = 'highlightRow';
				else
					$class = 'listRow1';
				
				echo '<tr>';
				echo '<td class="'.$class.'" valign="top"><strong>'.prettify_string($key).':</strong></td>';
				if(!empty($transformers[$key]))
				{
					$method = $transformers[$key];
					$a_val = $this->$method($a->get_value( $key ));
					if( !empty( $b_id ) )
						$b_val = $this->$method($b->get_value( $key ));
				}
				else
				{
					$a_val = $a->get_value( $key );
					if( !empty( $b_id ) )
						$b_val = $b->get_value( $key );
				}
				echo '<td class="'.$class.'" valign="top"'.(empty( $b_id ) ? ' colspan="2"' : '').'>'.$a_val.'</td>';
				if( !empty( $b_id ) )
					echo '<td class="'.$class.'" valign="top">'.$b_val.'</td>';
				echo '</tr>';
			}
			// only show the make current link if one of the edits is not the current edit
			if( ($a_id != $this->admin_page->id) OR (!empty( $b_id ) AND $b_id != $this->admin_page->id ) )
			{
				echo '<tr>';
				echo '<td class="listRow1">&nbsp</td>';
				if( $a_id != $this->admin_page->id )
				{
					echo '<td class="listRow1"'.(empty($b_id) ? ' colspan="2"' : '').'>';
					echo '<a href="'.$this->admin_page->make_link( array( 'archive_page' => 'confirm_reinstate', 'archive_id' => $a_id) ).'">Make this version current</a>';
					echo '</td>';
				}
				else
					echo '<td class="listRow1">&nbsp;</td>';
				if( !empty( $b_id ) )
				{
					if( $b_id != $this->admin_page->id )
					{
						echo '<td class="listRow1">';
						echo '<a href="'.$this->admin_page->make_link( array( 'archive_page' => 'confirm_reinstate', 'archive_id' => $b_id) ).'">Make this version current</a>';
						echo '</td>';
					}
					else
					{
						echo '<td class="listRow1">&nbsp;</td>';
					}
				}
				echo '</tr>';
			}
			echo '</table>';
		} // }}}
		function specialchars($value)
		{
			return nl2br(htmlspecialchars($value,ENT_QUOTES,'UTF-8'));
		}
	}
	
?>
