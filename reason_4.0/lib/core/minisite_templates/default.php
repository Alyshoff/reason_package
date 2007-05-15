<?php
	/* 
		dave hendler, brendon stanton, matt ryan 2003
		
		the default minisite_template object
	*/


$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'MinisiteTemplate';
	
reason_include_once( 'function_libraries/images.php' );
reason_include_once( 'function_libraries/file_finders.php' );
reason_include_once( 'content_listers/tree.php3' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
include_once( CARL_UTIL_INC . 'dev/timer.php' );

class MinisiteTemplate
{
	var $site_id;
	var $page_id;
	var $title;
	
	// These two vars are deprecated and will not do anything any more.
	// Now use $this->head_items.
	var $css_files;
	var $meta;
	
	var $pages;
	var $theme;
	var $nav_class = 'MinisiteNavigation';
	var $additional_crumbs = array();
	var $last_modified;
	var $logged_in = false;
	var $editing = false;
	var $section_to_module = array();
	var $_modules = array();
	
	/* This is an array that allows modules to add head items. 
	It is also used to replace the css_files and meta class arrays on the template.
	
	It looks like this:
	array(	1=>array(
					'element'=>'link',
					'attributes'=>array(
											'rel'=>'Stylesheet',
											'href'=>'foo.css',
											'type'=>'text/css',
										),
					),
			2=>array(
					'element'=>'title',
					'content'=>'This is the page\'s title',
					),
		); */
	var $head_items;
	var $use_default_org_name_in_page_title = false;
	var $use_tables = false;
	var $sections = array('content'=>'show_main_content','related'=>'show_sidebar','navigation'=>'show_navbar');
	//var $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
	var $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	var $use_navigation_cache = false;
	var $mode = 'default'; // possible values: 'default','documentation','samples'
	
	function initialize( $site_id, $page_id = '' ) // {{{
	{
		$this->site_id = $site_id;
		$this->page_id = $page_id;
		$this->site_info = new entity( $site_id );
		$this->page_info = new entity( $page_id );

		// make sure that the page exists or that the page's state is Live
		// if not, redirect to the 404
		if( !$this->page_info->get_values() OR $this->page_info->get_value( 'state' ) != 'Live' )
		{
			//trigger_error( 'page does not exist', WARNING );
			header( 'Location: '.ERROR_404_PAGE );
			die();
		}
		
		$this->get_css_files();
		
		if ($this->use_navigation_cache)
		{
			$cache = new ReasonObjectCache($this->site_id . $this->nav_class, 900); // lifetime of 15 minutes
			$max_last_modified = filemtime(WEB_PATH.trim_slashes($this->site_info->get_value('base_url')).'/.htaccess');
			if ($max_last_modified) $cache->set_max_last_modified($max_last_modified);
			$this->pages =& $cache->fetch();
		}
		// lets check the persistent cache
		
		if (empty($this->pages))
		{
			// lets setup $this->pages and place in the persistent cache
			$this->pages = new $this->nav_class;
			// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
			$this->pages->site_info =& $this->site_info;
			$this->pages->order_by = 'sortable.sort_order';
			$this->pages->init( $this->site_id, id_of('minisite_page') );
			if ($this->use_navigation_cache) 
			{
				$cache->set($this->pages);
			}
		}
		else // if pages came from cache refresh the request variables and set site_info and order_by
		{
			$this->pages->grab_request();
			$this->pages->site_info =& $this->site_info;
			$this->pages->order_by = 'sortable.sort_order'; // in case it was changed in the request
		}
		
		$this->textonly = '';
		if (!empty($this->pages->request['textonly']))
			$this->textonly = 1;
			$this->pages->textonly = $this->textonly;
		if (!empty($this->textonly))
		{
			$this->add_stylesheet(REASON_HTTP_BASE_PATH.'/css/textonly_styles.css');
			$this->add_stylesheet(REASON_HTTP_BASE_PATH.'/css/print_styles.css','print');
		}
		
		if( $this->pages->values  )
		{
			if( !$this->page_id )
				$this->page_id = $this->pages->root_node();

			$this->pages->cur_page_id = $this->page_id;

			$this->pages->force_open( $this->page_id );

			$this->cur_page = new entity($this->page_id);
			
			$this->title = $this->cur_page->get_value('name');

			$this->get_meta_information();
			
			$this->sess =& get_reason_session();
			if( $this->sess->exists() )
			{
				// if a session exists and we're on a secure page and this site has site users, pop over to the secure
				// site so we have access to the secure session information
				force_secure_if_available();
				$this->sess->start();
				if( !empty( $this->pages->request[ 'editing' ] ) )
				{
					if( $this->pages->request[ 'editing' ] == 'off' )
					{
						$this->sess->set( 'editing', 'off' );
					}
					else
					{
						$this->sess->set( 'editing', 'on' );
					}
				}
				
				if (USE_JS_LOGOUT_TIMER)
				{
					$this->add_stylesheet(REASON_HTTP_BASE_PATH.'css/timer.css');
					$this->add_head_item('script',array( 'language' => 'JavaScript', 'type' => 'text/javaScript',  'src' => WEB_JAVASCRIPT_PATH.'timer/timer.js.php'));
				}
				
				// we know that someone is logged in if the session exists
				$this->logged_in = true;
				$this->editing = true;
				
				//if( $this->sess->get( 'editing' ) != 'off' )
				//	$this->editing = true;
			}

			// hook for any actions to take prior to loading modules
			$this->pre_load_modules();

			// load the modules
			$this->load_modules();
		}
		else
			die( 'no pages on this site' );
	} // }}}
	
	// hook
	function pre_load_modules()
	{
	}
	
	function set_theme( $t ) //{{{
	{
		$this->theme = $t;
	} // }}}
	function get_css_files()
	{
		$css_files = array();

		// get css assoc with template
		$es = new entity_selector();
		$es->description = 'Get CSS associated with template';
		$es->add_type( id_of('css') );
		$es->add_right_relationship( $this->template_id, relationship_id_of('minisite_template_to_external_css') );
		$es->set_order( 'sortable.sort_order' );
		$css_files += $es->run_one();
		
		// Get css assoc with theme
		$es = new entity_selector();
		$es->description = 'Get CSS associated with theme';
		$es->add_type( id_of('css') );
		$es->add_right_relationship( $this->theme->id(), relationship_id_of('theme_to_external_css_url') );
		$es->set_order( 'sortable.sort_order' );
		$css_files += $es->run_one();

		$this->add_stylesheet(REASON_HTTP_BASE_PATH.'css/modules.css');
		if( $css_files )
		{
			foreach( $css_files AS $css )
			{
				if($css->get_value( 'css_relative_to_reason_http_base' ) == 'true')
				{
					$url = REASON_HTTP_BASE_PATH.$css->get_value( 'url' );
				}
				else
				{
					$url = $css->get_value( 'url' );
				}
				$this->add_stylesheet( $url );
			}
		}
	}
	function get_meta_information()
	{
		// add the charset information
		$this->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8' ) );
		
		// array of meta tags to search for in the page entity
		// key: entity field
		// value: meta tag to use
		$meta_tags = array(
			'description' => 'description',
			'author' => 'author',
			'keywords' => 'keywords'
		);

		// load meta elements from current page

		foreach( $meta_tags as $entity_field => $meta_name )
		{
			if( $this->cur_page->get_value( $entity_field ) )
			{
				$content = htmlspecialchars( $this->cur_page->get_value( $entity_field ), ENT_COMPAT, 'UTF-8' );
				$this->add_head_item('meta',array('name'=>$meta_name,'content'=>$content) );
			}
		}
		if (!empty ($this->textonly) || !empty( $this->pages->request['no_search'] ) || $this->site_info->get_value('site_state') != 'Live')
		{
			$this->add_head_item('meta',array('name'=>'robots','content'=>'none' ) );
			$this->add_head_item('meta',array('name'=>'htdig-noindex','content'=>'true' ) );
		}
	}
	function run() // {{{
	{
		$this->start_page();
		$this->show_body();
		$this->end_page();
	} // }}}

	function change_module( $page_type, $section, $new_module ) // {{{
	// allows runtime modification of module to use for a given
	// type-section pair.
	{
		if( $page_type == $this->cur_page->get_value( 'custom_page' ) )
			$this->section_to_module[ $section ] = $new_module;
	} // }}}
	function change_module_global( $section, $new_module ) // {{{
	// allows runtime modification of module regardless of page type
	// useful for changing the navigation section globally.
	{
		$this->section_to_module[ $section ] = $new_module;
	} // }}}
	function alter_modules() // {{{
	{
	} // }}}
	function additional_args( &$args ) // {{{
	//if a module needs additional args
	{
	} // }}}
	function load_modules() // {{{
	{
		//for page_types variables, defines the setup of the page
		reason_include_once( 'minisite_templates/page_types.php' );
		
		if( $this->cur_page->get_value( 'custom_page' ) )
			$type = $this->cur_page->get_value( 'custom_page' );
		else
			$type = 'default';
		
		// get the section to module relationships
		// note:: i merge the default set with the chosen set to simplify
		// changing defaults.  So, in the page_types file included above,
		// you only have to change what you want to change.  All other
		// settings are maintained.
		
		// this code is a little muddled because of the structure of the page_types array.  as of 11/04, the page_types
		// array can either be a simple list of section to module name or it can be more complex with extra arguments
		// for the module itself.  hence the checks to see if the $module variable is an array or not.  if it is an
		// array, the page_type MUST have a key within that second array with the name 'module' and a value which
		// corresponds to the name of the module.
		// $page_type = array_merge( $GLOBALS['_reason_page_types'][ 'default' ], $GLOBALS['_reason_page_types'][ $type ] );
		// We used the code below instead of array_merge to allow the page_type definition to control the initialization
		// order of the modules.
		$page_type = $GLOBALS['_reason_page_types'][ 'default' ];
		if (isset( $GLOBALS['_reason_page_types'][ $type ] ) && is_array( $GLOBALS['_reason_page_types'][ $type ] ) )
		{
			foreach ( $GLOBALS['_reason_page_types'][ $type ] as $key => $value )
			{
				if (isset( $page_type[$key] ) )
					unset($page_type[$key]);
				$page_type[$key] = $value;
			}
		}
		
		foreach( $page_type AS $sec => $module )
		{
			if( is_array( $module ) )
				$module_name = $module[ 'module' ];
			else
				$module_name = $module;
			$this->section_to_module[ $sec ] = $module_name;
		}
		
		$this->alter_modules();
		
		$prepped_request = conditional_stripslashes($_REQUEST);
		
		foreach( $this->section_to_module AS $sec => $module )
		{
			if( !empty( $module ) )
			{
				$module_name = $module;
				
				// collect params from page types
				if( is_array( $page_type[ $sec ] ) )
				{
					$params = $page_type[ $sec ];
					unset( $params[ 'module' ] );
				}
				else
					$params = array();
					
				$module_class = '';
				
				// this is where the template automatically loads up the PHP files with the module classes.  The 'name'
				// of a module determines the location of the file in the modules/ directory.  To make sure we're not
				// doing something insane, we make sure the file exists first before include()ing it.  Additionally, if
				// a specific file is not found, it looks in a directory of the module name to see if there is a
				// module.php file in that directory.  This serves to collect a group of files that a module might use
				// into one directory.

				if (reason_file_exists( 'minisite_templates/modules/'.$module_name.'.php' ))
				{
					reason_include_once( 'minisite_templates/modules/'.$module_name.'.php' );
				}
				elseif (reason_file_exists( 'minisite_templates/modules/'.$module_name.'/module.php' ))
				{
					reason_include_once( 'minisite_templates/modules/'.$module_name.'/module.php' );
				}
				else
				{
					trigger_error('The minisite module class file for "'.$module_name.'" cannot be found',WARNING);
				}

				// grab the class name as defined by the include file
				$module_class = $GLOBALS[ '_module_class_names' ][ $module_name ];
				
				if( !empty( $module_class ) )
				{
					$this->_modules[ $sec ] = new $module_class;
					$args = array();
					// set up a reference instead of a copy
					// dh - I really want to get rid of this.  For now, it stays.  However, I'm adding a number
					// of other parameters that a module will take by default so that we can rely on some important
					// data coming in.  9/15/04
					$args[ 'parent' ] =& $this; // pass this object to the module
					$args[ 'page_id' ] = $this->page_id;
					$args[ 'site_id' ] = $this->site_id;
					$args[ 'cur_page' ] = $this->cur_page;
					$args[ 'nav_pages' ] =& $this->pages;
					$args[ 'textonly' ] = $this->textonly;
					
					// this is used by a few templates to add some arguments.  leaving it in for backwards
					// compatibility.  i believe that any usage of this can be done with page type parameteres now.
					$this->additional_args( $args );
					
					// localizes the args array inside the module class.  this is basically another layer of backwards
					// compatibility with old modules.
					$this->_modules[ $sec ]->prep_args( $args );
					
					// send and check parameters gathered above from the page_types
					$this->_modules[ $sec ]->handle_params( $params );
					
					// hook to run code before grabbing and sanitizing the _REQUEST.  this is important for something
					// that might not know what variables will be coming through until a Disco class or some such thing
					// has been loaded.
					$this->_modules[ $sec ]->pre_request_cleanup_init();
					
					// it's a little ugly, but i'm setting the request variable directly here.  other method used to
					// do this, but i wanted to have a few more hooks above that would allow a module to do some work
					// before get_cleanup_rules was called.  obviously, the request variables are unavailable to those
					// modules.
					$this->_modules[ $sec ]->request = $this->clean_vars( $prepped_request, $this->_modules[$sec]->get_cleanup_rules() );
					
					// init takes $args as a backwards compatibility feature.  otherwise, everything should be handled
					// in prep_args
					$this->_modules[ $sec ]->init( $args );
					
					// editing is defined in the templates init() method.  if it is true, we want to also run the front
					// end editing specific module initialization code.
					if( $this->editing )
					{
						$this->_modules[ $sec ]->init_editable( $this->sess );
					}
				}
				else
					trigger_error( 'Badly formatted module ('.$module_name.') - $module_class not set ' );
			}
		}
	} // }}}
	function & _get_module( $sec ) // {{{
	{
		if( !empty( $this->_modules[ $sec ] ) && is_object( $this->_modules[ $sec ] ) )
		{
			return $this->_modules[ $sec ];
		}
		$false = false;
		return $false;
		
	} // }}}
	function clean_vars( &$vars, $rules ) // {{{
	// Returns an array which takes the values of the keys in Vars of
	// the keys set in Settings, and runs the cleaning function
	// specified in the value of settings
	{
		return clean_vars( $vars, $rules );
	} // }}}

	function run_section( $sec ) // {{{
	{
		$module =& $this->_get_module( $sec );
		if( $module )
		{
			echo "\n\n";
			if($this->in_documentation_mode())
			{
				$this->run_documentation($sec);
			}
			elseif( $this->editing AND $module->can_edit() )
			{
				$module->run_editable();
			}
			else
			{
				$module->run();
			}
			echo "\n\n";
		}
	} // }}}
	function run_documentation($sec)
	{
		$module =& $this->_get_module( $sec );
		if( $module )
		{
			$doc =  $module->get_documentation();
			if($doc !== false)
			{
				$module_name = $this->section_to_module[$sec];
				echo '<div class="documentation">'."\n";
				echo '<h4>'.prettify_string($module_name).'</h4>'."\n";
				echo $doc;
				echo '</div>'."\n";
			}
		}
	}
	function has_content( $sec ) // {{{
	{
		$module =& $this->_get_module( $sec );
		if( $module )
		{
			if($this->in_documentation_mode())
				return true;
			return $module->has_content();
		}
		else
			return false;
	} // }}}
	
	function start_page() // {{{
	{
	
		$this->get_title();
		
		// start page
		echo $this->doctype."\n";
		echo '<html>'."\n";
		echo '<head>'."\n";
		//echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
		
		$this->do_org_head_items();
		
		echo $this->get_head_item_markup();
		
		if($this->cur_page->get_value('extra_head_content'))
		{
			echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
		}
			
		echo '</head>'."\n";

		echo $this->create_body_tag();
		echo '<div class="hide"><a href="#content" class="hide">Skip Navigation</a></div>'."\n";
		if ($this->has_content( 'pre_bluebar' ))
			$this->run_section( 'pre_bluebar' );
		//$this->textonly_toggle( 'hide_link' );
		if (empty($this->textonly))  // This includes the Blue Bar
		{
			$this->do_org_navigation_textonly();
		// You are here bar
			$this->you_are_here();
		}
		else
		{
			$this->do_org_navigation();
		}	
	} // }}}
	function create_body_tag()
	{
		return '<body>'."\n";
	}
	
	function get_title()
	{
		$ret = '';
		if($this->use_default_org_name_in_page_title)
		{
			$ret .= FULL_ORGANIZATION_NAME.': ';
		}
		$ret .= $this->site_info->get_value('name');
		
		if($this->site_info->get_value('name') != $this->title)
		{
			$ret .= ": " . $this->title;
		}
			
		// Take the last-added crumb and add it to the page title
		if(!empty( $this->additional_crumbs ))
		{
			$last_crumb = end( $this->additional_crumbs);
			reset( $this->additional_crumbs );
			$ret .= ': '.strip_tags($last_crumb['page_name']);
		}
		if (!empty ($this->textonly) )
		{
			$ret .= ' (Text Only)';
		}
		$ret = strip_tags($ret);
		$this->add_head_item('title',array(),$ret, true);
		//return $ret;
	}

	function you_are_here() // {{{
	/* [7/17/03 footeb] update: use the link name for a page if it exist; otherwise, use the page's name */
	{
		// set up the table, etc.
		//echo ("\n".'<table border="0" cellpadding="4" cellspacing="0" width="100%" summary="You are here">'."\n");
		//echo ('<tr>'."\n");
		//echo ('<td align="left" valign="middle" width="100%" class="locationBarText">&nbsp;You are here:&nbsp;');
		echo '<div id="breadcrumbs" class="locationBarText">';
		echo 'You are here: ';

		// show breadcrumb base
		if($this->site_info->get_value('base_breadcrumbs'))
			echo $this->site_info->get_value('base_breadcrumbs').'&nbsp;&gt;&nbsp;';

		// Get the trail of pages back to the root page in the site
		$cur_node = $this->cur_page->id();
		while( $cur_node != $this->pages->root_node() )
		{
			$crumbs[] = $cur_node;
			$cur_node = $this->pages->parent( $cur_node );
		}
		$crumbs[] = $cur_node;

		// Iterate through and print out a link for each page in the trail
		for( $i = count( $crumbs ) - 1; $i >= 0; $i-- )
		{
			$page = $this->pages->values[ $crumbs[ $i ] ];
			
			// set page name.  if page is root node, use the name of the site intead of the name of the page
			if( $crumbs[ $i ] == $this->pages->root_node() )
				$page_name = $this->site_info->get_value('name');
			else
				$page_name = $page->get_value('link_name')? $page->get_value( 'link_name' ) : $page->get_value ( 'name' );
			
			// make all crumbs links except for the last one
			if( ( $i > 0 ) || !empty( $this->additional_crumbs ) )
				echo '<a href="'.$this->pages->get_full_url( $page->id() ).'" class="locationBarLinks">'.$page_name.'</a> &gt; ';
			else
				echo $page_name;
		}
		$num_crumbs = count( $this->additional_crumbs );
		for( $i=0; $i < $num_crumbs; $i++ )
		{
			$crumb = $this->additional_crumbs[ $i ];
			if( $i < ( $num_crumbs - 1) )	
				echo '<a href="'.$crumb[ 'link' ].'" class="locationBarLinks">'.$crumb[ 'page_name' ].'</a> &gt; ';
			else
				echo $crumb[ 'page_name' ];
			
		}
	
	
		// Finish you are here bar
		
		echo '</div>';
		//echo ('</td>'."\n".'</tr>'."\n".'</table>'."\n");

	} // }}}
	function show_body()
	{
		if($this->use_tables)
		{
			$this->show_body_tabled();
		}
		else
		{
			$this->show_body_tableless();
		}
	}
	function show_body_tableless() // {{{
	{
		if (!empty($this->textonly))
		{
			$class = 'textOnlyView';
		}
		else
		{
			$class = 'fullGraphicsView';
		}
		echo '<div id="wrapper" class="'.$class.'">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
		$this->show_banner();
		$this->show_meat();
		echo '</div>'."\n";
		$this->show_footer();
		echo '</div>'."\n";
	} // }}}
	function show_body_tabled() // {{{
	{
		$this->show_banner();
		$this->show_meat();
		$this->show_footer();
	} // }}}
	function end_page() // {{{
	{
		// finish body and html
		$this->do_org_foot();
		echo '</body>'."\n";
		echo '</html>'."\n";
	} // }}}
	
	function show_banner()
	{
		if($this->use_tables)
		{
			$this->show_banner_tabled();
		}
		else
		{
			$this->show_banner_tableless();
		}
	}
	function show_banner_tableless() // {{{
	{
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div id="banner">'."\n";
		echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
		$this->show_banner_xtra();
		echo '</div>'."\n";
		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
	} // }}}
	function show_banner_tabled() // {{{
	{
		if(!empty($this->textonly))
		{
			$add_class = ' textOnly';
		}
		else
		{
			$add_class = ' fullGraphics';
		}
		echo '<div class="bannerAndMeat'.$add_class.'">'."\n";
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div class="banner">'."\n";
		if (empty($this->textonly))
		{
			echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="bannerTable" summary="The Site Name">'."\n";
			echo '<tr>'."\n";
			echo '<td class="bannerCol1">'."\n";
		}
		/*
		if( $this->sess->get('username') )
		{
			echo '<p>You are logged in as '.$this->sess->get('username').'</p>';
			echo '<p>';
			if( $this->editing )
			{
				echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?editing=off">See this site as a normal user</a>';
			}
			else
			{
				echo '<a href="">Edit this site</a>';
			}
			echo '</p>';
		}
		*/
		echo '<div class="bannerInfo">'."\n";
		echo '<h1 class="siteName"><a href="';
		echo $this->site_info->get_value('base_url');
		if (!empty ($this->textonly) )
			echo '?textonly=1';
		echo '" class="siteLink"><span>';
		echo $this->site_info->get_value('name');
		echo '</span></a></h1>'."\n";
		echo '</div>'."\n";
		if (empty($this->textonly)) 
		{
			echo '</td>'."\n";
			echo '<td class="bannerCol2">'."\n";
		}
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div class="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
		if (empty($this->textonly))
		{
			echo '</td>'."\n".'</tr>'."\n".'</table>'."\n";
		}
		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_meat()
	{
		if($this->use_tables)
		{
			$this->show_meat_tabled();
		}
		else
		{
			$this->show_meat_tableless();
		}
	}
	function show_meat_tableless() // {{{
	{
		$hasSections = array();
		foreach($this->sections as $section=>$show_function)
		{
			$has_function = 'has_'.$section.'_section';
			if($this->$has_function())
			{
				$hasSections[$section] = $show_function;
				$classes[] = 'contains'.ucfirst($section);
			}
		}
		echo '<div id="meat" class="'.implode(' ',$classes).'">'."\n";
		foreach($hasSections as $section=>$show_function)
		{
			echo '<div id="'.$section.'">'."\n";
			$this->$show_function();
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
	} // }}}
	function show_meat_tabled() // {{{
	{
		echo '<div class="layout">'."\n";
		if (empty($this->textonly))
		{
			echo '<table border="0" cellspacing="0" cellpadding="0" class="layoutTable" summary="The Main Content of Page">'."\n";
			echo '<tr>'."\n";
			$this->show_navbar();
		}
		$this->show_main_content();
		$this->show_sidebar();
		if (empty($this->textonly))  
			echo '</tr>'."\n".'</table>'."\n";
		else
		{
			$this->show_nav_foot();
		}
		echo '</div>'."\n";
		echo '</div>'."\n";
	} // }}}
	function show_navbar()
	{
		if($this->use_tables)
		{
			$this->show_navbar_tabled();
		}
		else
		{
			$this->show_navbar_tableless();
		}
	}	
	function show_navbar_tableless() // {{{
	{
		if ($this->has_content( 'navigation' )) 
		{ 
			$this->run_section( 'navigation' );
		}
		if ($this->has_content( 'sub_nav' )) 
		{ 
			echo '<div id="subNav">'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'sub_nav_2' ))
		{
			$this->run_section( 'sub_nav_2' );
		}
	} // }}}
	function show_navbar_tabled() // {{{
	{
		if ($this->has_content( 'navigation' ) || $this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) ) 
		{
			echo '<td valign="top" class="navigationTD">'."\n";
			if ($this->has_content( 'navigation' )) 
			{ 
				//$_nav_timing_start = getmicrotime();
				echo '<div class="navigation">'."\n";
				$this->run_section( 'navigation' );
				echo '</div>'."\n";
				//$_nav_timing_end = getmicrotime();
				//echo '<!-- nav start time: '.$_nav_timing_start.'   nav end time: '.$_nav_timing_end.'   total nav time: '.round(1000*($_nav_timing_end - $_nav_timing_start), 1).' ms -->'."\n";
						
			}
			if ($this->has_content( 'sub_nav' )) 
			{ 
				echo '<div class="subNav">'."\n";
				echo '<hr class="hideFromModern" />'."\n";
				$this->run_section( 'sub_nav' );
				echo '</div>'."\n";
			}
			if ($this->has_content( 'sub_nav_2' ))
				$this->run_section( 'sub_nav_2' );
			echo '<div class="navigationSpacer"><img src="'.REASON_HTTP_BASE_PATH.'ui_images/stp.gif" width="150" height="2" alt="" /></div>'."\n";
			echo '</td>'."\n";
		}
	} // }}}
	function show_main_content()
	{
		if($this->use_tables)
		{
			$this->show_main_content_tabled();
		}
		else
		{
			$this->show_main_content_tableless();
		}
	}
	function show_main_content_tableless() // {{{
	{
		$this->show_main_content_sections();
	} // }}}
	
	function show_main_content_tabled() // {{{
	{
		if ($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' )) 
		{
			if (empty($this->textonly))
				echo '<td valign="top" class="contentTD">'."\n";
			echo '<div class="content"><a name="content"></a>'."\n";
			$this->show_main_content_sections();
			echo '</div>'."\n";
			if (empty($this->textonly))
				echo '</td>'."\n";
		}
	} // }}}
	function show_main_content_sections()
	{
		if ($this->has_content( 'main_head' )) 
		{
			echo '<div class="contentHead">'."\n";
			$this->run_section( 'main_head' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main' )) 
		{
			echo '<div class="contentMain">'."\n";
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post' )) 
		{
			echo '<div class="contentPost">'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
	}
	function show_nav_foot() // {{{
	{
		if ($this->has_content( 'sub_nav_2' ))
			$this->run_section( 'sub_nav_2' );
		if ($this->has_content( 'navigation' )) 
		{
			echo '<div class="navigation">'."\n";
			$this->run_section( 'navigation' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'sub_nav' )) 
		{ 
			echo '<div class="subNav">'."\n";
			echo '<hr class="hideFromModern" />'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		} 
	} // }}}
	function show_sidebar()
	{
		if($this->use_tables)
		{
			$this->show_sidebar_tabled();
		}
		else
		{
			$this->show_sidebar_tableless();
		}
	}
	function show_sidebar_tableless() // {{{
	{
		if($this->has_content( 'pre_sidebar' ))
		{
			echo '<div id="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'sidebar' ))
		{
			echo '<div id="sidebar">'."\n";
			$this->run_section( 'sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'post_sidebar' ))
		{
			echo '<div id="postSidebar">'."\n";
			$this->run_section( 'post_sidebar' );
			echo '</div>'."\n";
		}
	} // }}}
	function show_sidebar_tabled() // {{{
	{
		$show_sidebar = $this->has_content( 'sidebar' );
		$show_pre_sidebar = $this->has_content( 'pre_sidebar' );
		$show_post_sidebar = $this->has_content( 'post_sidebar' );
		if ($show_sidebar || $show_pre_sidebar || $show_post_sidebar)
		{ 
			if (empty($this->textonly))
				echo '<td valign="top" class="sidebarTD">'."\n"; 
			if($show_pre_sidebar)
			{
				echo '<div class="preSidebar">'."\n";
				$this->run_section( 'pre_sidebar' );
				echo '</div>'."\n";
			}
			if($show_sidebar)
			{
				echo '<div class="sidebar">'."\n";
				$this->run_section( 'sidebar' );
				echo '</div>'."\n";
			}
			if($show_post_sidebar)
			{
				echo '<div class="postSidebar">'."\n";
				$this->run_section( 'post_sidebar' );
				echo '</div>'."\n";
			}
			echo '<div class="sidebarSpacer">'."\n";
			echo '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/stp.gif" width="80" height="2" alt="" />'."\n";
			echo '</div>'."\n";
			if (empty($this->textonly))
				echo '</td>'."\n";
		}
	} // }}}
	function show_footer()
	{
		if($this->use_tables)
		{
			$this->show_footer_tabled();
		}
		else
		{
			$this->show_footer_tableless();
		}
	}
	function show_footer_tableless() // {{{
	{
		echo '<div id="footer">'."\n";
		echo '<div class="module1">';
		$this->run_section( 'footer' );
		echo '</div>';
		echo '<div class="module2 lastModule">';
		$this->run_section( 'edit_link' );
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>';
		echo '</div>'."\n";
	} // }}}
	function show_footer_tabled() // {{{
	{
		echo '<div id="footer" class="maintained">'."\n";
		$this->run_section( 'footer' );
		$this->run_section( 'edit_link' );
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>'."\n";
	} // }}}

	function add_crumb( $name , $link = '' ) // {{{
	{
		$x = array( 'page_name' => $name , 'link' => $link );
		$this->additional_crumbs[] = $x;
	} // }}}
	
	
	/* This function allows modules to add head items. They must add any head items during their init process. */
	function add_head_item( $element, $attributes, $content = '', $add_to_top = false )
	{
		$item = array('element'=>$element,'attributes'=>$attributes,'content'=>$content);
		if($add_to_top)
		{
			$temp = array_reverse($this->head_items);
			$temp[] = $item;
			$this->head_items = array_reverse($temp);
		}
		else
		{
			$this->head_items[] = $item;
		}
	}
	function add_stylesheet( $url, $media = '', $add_to_top = false )
	{
		$attrs = array('rel'=>'stylesheet','type'=>'text/css','href'=>$url);
		if(!empty($media))
		{
			$attrs['media'] = $media;
		}
		$this->add_head_item('link',$attrs, '', $add_to_top);
	}
	
	/* this function assembles the head items from the data provided by the modules and handles some basic checking */
	function get_head_item_markup()
	{
		$allowable_elements = array('base','link','meta','script','style','title');
		$elements_that_may_have_content = array('script','style','title');
		$elements_that_may_not_self_close = array('script','title');
		$html_items = array();
		foreach($this->head_items as $item)
		{
			if(in_array($item['element'], $allowable_elements))
			{
				$html_item = '<'.$item['element'];
				foreach($item['attributes'] as $attr_key=>$attr_val)
				{
					$html_item .= ' '.$attr_key.'="'.$attr_val.'"';
				}
				if(in_array($item['element'],$elements_that_may_have_content) && !empty($item['content']) )
				{
					$html_item .= '>'.$item['content'].'</'.$item['element'].'>';
				}
				elseif(in_array($item['element'],$elements_that_may_not_self_close))
				{
					$html_item .= '></'.$item['element'].'>';
				}
				else
				{
					$html_item .= ' />';
				}
				$html_items[] = $html_item;
			}
			else
			{
				trigger_error('Encountered Invalid head element: '.$item['element'].'. Head items must be one of these elements: '.implode($allowable_elements) );
			}
		}
		return implode("\n",$html_items)."\n";
	}
	
	/*this stuff comes from the tableless template. from here... */
		function has_content_section()
	{
		if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) )
		{
			return true;
		}
		return false;
	}
	function has_navigation_section()
	{
		if( $this->has_content( 'navigation' ) || $this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) )
		{
			return true;
		}
		return false;
	}
	function has_related_section()
	{
		if( $this->has_content( 'pre_sidebar' ) || $this->has_content( 'sidebar' ) )
		{
			return true;
		}
		return false;
	}
	function show_banner_xtra()
	{
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div id="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
	}
	/* ...down to here */
	
	function do_org_navigation()
	{
		// Just here as a shell for branding
	}
	
	function do_org_navigation_textonly()
	{
		$this->do_org_navigation();
	}
	
	function do_org_head_items()
	{
		// Just here as a hook for branding head items (js/css/etc.)
	}
	function do_org_foot()
	{
		// Just here as a shell for branding
	}
	function in_documentation_mode()
	{
		if($this->mode == 'documentation')
			return true;
		return false;
	}
}
?>
