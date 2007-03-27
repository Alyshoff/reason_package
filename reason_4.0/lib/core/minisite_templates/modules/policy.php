<?php
	// get the name of the file, without all the path information
	// and without the .php suffix and set the name of the class
	// to that index of this global array

	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'content_listers/multiple_root_tree.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PolicyModule';


	class PolicyNavigation extends multiple_root_tree_viewer
	{
		var $ns_to_class = array(
							'Uppercase Roman' => 'upperRoman',
							'Lowercase Roman' => 'lowerRoman',
							'Uppercase Alpha' => 'upperAlpha',
							'Lowercase Alpha' => 'lowerAlpha',
							'Decimal' => 'decimal',
						   );
		var $default_ns_to_class = 'decimal';
		var $li_class = 'stuff';
		function show_all_items() // {{{
		{
			$root = $this->cur_page_root();
			echo '<div class="policy">'."\n";
			$this->make_tree( $root , $root , 0);
			$item = new entity( $root );
			$policy_author = $item->get_value( 'author' );
			$policy_date = prettify_mysql_datetime($item->get_value( 'datetime' ), "F j, Y");
			if (!empty($policy_author) || (!empty($policy_date)))
			{
				echo '<p class="policyAdopted">Adopted ';
				if (!empty($policy_author))
				{
					echo " by " . $policy_author;
				}
				if (!empty($policy_date))
				{
					echo " on " . $policy_date;
				}
				echo ".</p>\n";
			}
			echo '</div>'."\n";
		} // }}}
		function show_item( $item , $options = false) // {{{
		{
			$policy_name = $item->get_value('name');
			echo '<a name="'.$item->id().'" id="'.$item->id().'"></a>';
			if ( !in_array( $item->id(), $this->root_node() ) )
			{
				echo '<li class="'.$this->li_class.'">'."\n";
				$header_type = "h4";
			}
			else $header_type = "h3";
			echo "<" . $header_type . " class='policyName'>" . $policy_name . "</" . $header_type . ">\n";
			echo '<div class="policyContent">'.$item->get_value( 'content' ) . '</div>';
			if ( !in_array( $item->id(), $this->root_node() ) ) echo '</li>';
		} //  }}}
		function make_tree( $item , $root , $depth ) // {{{
		{
			if($this->has_filters() AND !empty( $this->filter_values[ $item ] ) )
			{
				$this->options = array( 'color' => true , 'depth' => $depth );
				$this->show_item( $this->values[ $item  ] );
			}
			else
			{
				$this->options = array( 'depth' => $depth );
				$this->show_item( $this->values[ $item  ] );
			}
			$children = $this->children( $item );
			if( $children )
			{
				$style = isset( $this->ns_to_class[ $this->values[ $item ]->get_value( 'numbering_scheme' ) ] ) ?
				$this->ns_to_class[ $this->values[ $item ]->get_value( 'numbering_scheme' ) ] :
				$this->default_ns_to_class;
				echo '<ol class="'.$style.'">';
				foreach( $children AS $child )
				{
					$ent = $this->values[ $item ];
					$this->make_tree( $child , $root, $depth +1);
				}
				echo '</ol>';
			}
		} // }}}
		function cur_page_root( $page = '' ) // {{{
		{
			if( !$page )
				$page = isset( $this->request[ 'policy_id' ] ) ? $this->request[ 'policy_id' ] : '';
			if( $page )
			{
				foreach( $this->values AS $v )
				{
					if( $v->id() == $page )
					{
						if( $v->get_value( 'parent_id' ) == $v->id() )
							return $v->id();
						else
							return $this->cur_page_root( $v->get_value( 'parent_id' ) );
					}
				}
			}
		} // }}}
	}

	class PolicyModule extends DefaultMinisiteModule
	{
		var $cleanup_rules = array(
			'policy_id' => array('function' => 'turn_into_int')
		);
		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$es = new entity_selector( $this->parent->site_id );
			$es->add_type( id_of( 'policy_type' ) );
			//$es->set_order( 'sortable.sort_order ASC' );
			$es->set_order( 'entity.name ASC' );
			$es->add_relation( 'show_hide.show_hide != "hide"' );
			$es->add_left_relationship_field( 'parent' , 'entity' , 'id' , 'parent_id' );

			$this->values = $es->run_one();
			$this->pages = new PolicyNavigation;
			$this->pages->request =& $this->request;
			// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
			if ( !empty ( $this->site_info ) )
				$this->pages->site_info = $this->site_info;
			$this->pages->order_by = 'sortable.sort_order ASC';
			$this->pages->init( $this->parent->site_id, id_of('policy_type') );
			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(array_key_exists($this->request[ 'policy_id' ], $this->values))
				{
					$this->policy = new entity( $this->request[ 'policy_id' ] );
					$this->parent->add_crumb( $this->policy->get_value( 'name' ) , '?policy_id=' . $this->request[ 'policy_id' ] );
				}
				else
				{
					$this->policy = NULL;
				}
			}
		} // }}}
		function run() // {{{
		{
			$this->get_root_nodes();
			//pray ($this->roots);
			if ( empty( $this->request[ 'policy_id' ] ) && count( $this->roots ) == 1)
			{
				foreach ( $this->roots as $k=>$v )
				{
					$this->request[ 'policy_id' ] = $v->get_value( 'id' );
				}
			}
			if ( !empty( $this->request[ 'policy_id' ] ) )
			{
				if ( count( $this->roots ) != 1 )
					$this->show_root_option_menu();
				$this->display_navigation();
				if ( count( $this->roots ) != 1 )
					$this->display_back_link();
			}
			else
			{
				$this->show_root_list();
			}
		} // }}}
		function get_root_nodes() // {{{
		{
			$r = array();
			foreach( $this->values AS $v )
			{
				if( $v->id() == $v->get_value( 'parent_id' ) )
					$r[] = $v;
			}
			$this->roots = $r;
		} // }}}
		function show_root_list() // {{{
		{
			echo "<ul class='rootPolicyList'>\n";
			foreach( $this->roots AS $root )
			{
				echo '<li class="rootPolicyItem"><a href="'.$this->page_link( $root ).'" class="rootPolicyLink">'.strip_tags( $root->get_value( 'name' ), "em,i" ).'</a> '.$root->get_value( 'description' ).'</li>';
			}
			echo "</ul>\n";
		} // }}}
		function show_root_option_menu() // {{{
		{
		?>
			<script language="JavaScript">
			<!--
			function MM_jumpMenu(targ,selObj,restore){ //v3.0
				eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
				if (restore) selObj.selectedIndex=0;
			}
			//-->
			</script>
		<?php
			$main_link = '?';
			if (!empty($this->parent->textonly))
				$main_link .= '&amp;textonly=1';

			echo '<form name="policy_form" class="policyForm">' .
					'<select name="policy_select" onChange="MM_jumpMenu(\'parent\',this,0)" class="rootMenu">'.
					'<option value="'.$main_link.'">' . $this->parent->title . "</option>\n";
			foreach( $this->roots AS $root )
			{
				echo '<option value="'.$this->page_link( $root ).'"';
				if ( $root->id() == $this->request[ 'policy_id' ] ) echo " selected='selected' ";
				echo '>'.prettify_string( $root->get_value( 'name' ) ).'</option>'."\n";
			}
			echo '</select><noscript><input type="submit" value="submit"></noscript></form>';

		} // }}}
		function display_navigation() // {{{
		{
			if( !empty( $this->request[ 'policy_id' ] ) )
			{
				if(!empty($this->policy))
				{
					$this->pages->do_display();
				}
				else
				{
					header('HTTP/1.0 404 Not Found');
					echo '<h3>Policy not found</h3>'."\n";
					echo '<p>This policy is not available.  It is possible that it has been removed from the site.</p>'."\n";
					echo '<p>Please contact the maintainer of this site if you have any questions.</p>'."\n";
				}
			}
		} // }}}
		function page_link( $page ) // {{{
		{
			if( !is_object( $page ) )
				$page = new entity( $page );

			$link = '?policy_id=' . $page->id();
			if (!empty($this->parent->textonly))
				$link .= '&amp;textonly=1';

			return $link;
		} // }}}
		function display_content() // {{{
		{
			if( $this->policy )
			{
				echo '<div class="policyContent">';
				echo $this->policy->get_value( 'content' );
				echo '</div>';
			}
		} // }}}
		function display_back_link() // {{{
		{
			$list_link = '?';
			if (!empty($this->parent->textonly))
				$list_link .= '&amp;textonly=1';
			echo "<p><a href='".$list_link."' class='rootPolicyListLink'>List of policies</a></p>\n";
		} // }}}
	}
?>
