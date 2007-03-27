<?php
	/* This lists out just a few news items and links to the appropriate page.  It's useful in sidebars and the like. --MR, 9/8/03 */

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MiniNewsMinisiteModule';
	reason_include_once( 'minisite_templates/modules/news.php' );

	class mini_no_issue_news_viewer extends no_issue_news_viewer
	{
		function show_item( $item ) // {{{
		{
			mini_news_viewer::show_item( $item );
		} // }}}
		
		function display() // {{{
		{
			$this->show_all_items();
		} // }}}
	}
	class mini_news_viewer extends issue_news_viewer
	{
		function show_item( $item ) // {{{
		{
			$content = $item->get_value( 'content' );
			$desc = strip_tags( $item->get_value( 'description' ), "<strong><b><em><i><a><span>" );
			echo "\n<p class='newsItem'>\n";
			echo '<div class="smallText newsItemDate">' . prettify_mysql_datetime( $item->get_value( 'datetime' ), "F jS, Y" ) . "</div>\n";
			echo '<div class="newsItemName">';
			/* if ( !empty( $content ) )
			{ */
				echo '<a href="'.$this->news_page_link.'?';
				if (!empty($this->current_issue))
					echo 'issue_id='.$this->current_issue->id().'&amp;';
				echo 'story_id=' . $item->id();
				if ( !empty( $this->request[ 'page' ] ) )
					echo '&amp;page=' . $this->request[ 'page' ];
				if (!empty($this->textonly))
					echo '&amp;textonly=1';
				echo '" class="newsItemLink">';
			// }
			echo $item->get_value( 'release_title' );
			/* if ( !empty( $content ) ) */
				echo "</a>";
			echo "</div>\n";
			/* if ( !empty( $desc ) )
				echo "<div class='newsItemDesc'>" . $desc . "</div>\n"; */
			echo "</p>\n";
		} // }}}
		function show_all_items() // {{{
		{
			//this function is meant for overloading
			$row = 0;
			reset( $this->values );
			while( list( $id, $item ) = each( $this->values ) )
			{
				if( ($row % $this->rows_per_sorting) == 0 )
					$this->show_sorting();
				$this->show_item( $item );
				$row++;
			}
		} // }}}
		function display() // {{{
		{
			$this->show_all_items();
		} // }}}
	}

	class MiniNewsMinisiteModule extends NewsMinisiteModule
	{
		var $num_per_page = 4;
		var $add_breadcrumbs = false;
		
		function get_news_page_link() // {{{
		{
			$es = new entity_selector($this->parent->pages->site_info->get_value('id'));
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_relation( '(page_node.custom_page = "news" or page_node.custom_page = "news_doc")' );
			$es->set_num( 1 );
			$newsPage = $es->run_one();
			if ($newsPage) $item = current($newsPage);
			else $item = false;
			if ($item)
			{
				$link = $this->parent->pages->get_full_url($item->id());
				$this->news_page_title = $item->get_value('name');
			}
			else $link = false;
			return $link;
		}

		function init( $args ) // {{{
		{
			parent::init( $args );
			$this->issues = $this->get_issues();
			if( $this->has_issues() )
			{
				$this->get_issue_id();
			}
			$this->news_page_link = $this->get_news_page_link();
			
		} // }}}
	
		function run() // {{{
		{
			if (!empty($this->news_page_link))
			{
				echo '<div id="miniNews">'."\n";
				echo '<h4>'.$this->news_page_title.'</h4>'."\n";
				$this->list_news();
				echo '<p class="moreNews"><a href="'.$this->news_page_link.'">More ';
				if (preg_match('/^The/', $this->news_page_title) || preg_match('/^the/', $this->news_page_title))
					echo 'of ';
				echo $this->news_page_title.'</a></p>'."\n";
				echo '</div>'."\n";
			}
		} // }}}
		function has_content()
		{
			if (!empty($this->news_page_link))
				return true;
			else
				return false;
		}
		function list_news() // {{{
		{
			
			if( $this->has_issues() )
				$this->list_news_issue();
			else
				$this->list_news_no_issue();
		} // }}}
		function list_news_issue() // {{{
		{
			$v = new mini_news_viewer;
			$v->current_issue =& $this->current_issue;
			$v->num_per_page = $this->num_per_page;
			$v->request = &$this->request;
			$v->news_page_link = &$this->news_page_link;
			$v->init( $this->parent->site_id , id_of( 'news' ) );
			$v->textonly = $this->parent->textonly;
			$v->do_display();
		} // }}}
		function list_news_no_issue() // {{{
		{
			$v = new mini_no_issue_news_viewer;
			$v->num_per_page = $this->num_per_page;
			$v->request = &$this->request;
			$v->news_page_link = &$this->news_page_link;
			$v->init( $this->parent->site_id , id_of( 'news' ) );;
			$v->textonly = $this->parent->textonly;
			$v->do_display();
		} // }}}
		function show_feed_link()
		{
		}
	}
?>
