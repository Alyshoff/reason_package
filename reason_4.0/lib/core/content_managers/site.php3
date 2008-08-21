<?php
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'SiteManager';
	
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	reason_include_once('classes/url_manager.php');
	
	/**
	 * Content manager for sites
	 *
	 * @todo Write an upgrade script to remove the is_incarnate field from the DB, now that it is not used
	 */
	class SiteManager extends ContentManager
	{
		var $old_entity_values = array();

		function init_head_items()
		{
			$this->head_items->add_javascript(JQUERY_URL, true); // uses jquery - jquery should be at top
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/site.js');
		}
		
		function alter_data() // {{{
		{
			// don't allow the user to see whether the site is new or not
			$this->remove_element( 'is_incarnate' );
			
			$old_entity = new entity( $this->get_value('id'), false );
			$this->old_entity_values = $old_entity->get_values();
				
			$this->change_element_type(
				'use_page_caching',
				'select',
				array(
					'options' => array(
						0 => 'Off',
						1 => 'On' 
					),
					'add_null_value_to_top' => false,
				)
			);
			$this->add_required( 'unique_name' );

			// make the form easier to read
			$this->change_element_type( 'short_department_name','hidden');
			$this->set_display_name( 'department','Department Code/ID');
			$this->set_display_name( 'custom_url_handler','Custom URL Handler');
			$this->set_display_name( 'use_page_caching', 'Page Caching' );
			$this->set_comments( 'primary_maintainer',form_comment('Username of maintainer') );
			$this->set_comments( 'base_url',form_comment( 'Path to your site.<br />eg: /campus/multicultural<br /><span style="color: #f33"><strong>Warning:</strong> If a site already occupies the URL, it will get clobbered.</span>' ) );
			$this->set_comments( 'department',form_comment('(If integrated) The office or department code or ID in central information system (e.g. LDAP, etc.)') );
			//$this->set_comments( 'custom_url_handler', form_comment('Give a descriptive value if this site will NOT use Reason\'s default URL management code.') );
			$this->change_element_type( 'custom_url_handler','hidden');
			$this->change_element_type( 'use_custom_footer','select_no_sort',array('display_name'=>'Footer Type','options'=>array('no'=>'Standard','yes'=>'Custom'),'add_null_value_to_top' => false,));
			if(!$this->get_value('use_custom_footer'))
			{
				$this->set_value('use_custom_footer','no');
			}
			if(defined('REASON_DEFAULT_FOOTER_XHTML') && $this->get_value('use_custom_footer') == 'no' && !$this->get_value('custom_footer'))
			{
				$this->set_value('custom_footer',REASON_DEFAULT_FOOTER_XHTML);
			}
			$this->set_comments( 'use_page_caching', form_comment('<strong>Note:</strong> page caching may make changes to your pages be delayed up to 1 hour after they are made.  Only turn this on if your site has a lot of traffic and you need to improve performance.') );
			$this->set_comments( 'keywords',form_comment('These words or phrases will be used by the A-Z module to provide a keyword index of Reason sites. Separate words and phrases with a comma, like this: <em>Economics, Monetary Policy, Political Economy</em>'));
			$this->set_comments( 'site_state',form_comment('The current status of the site. "Live" sites are listed in the A-Z and Sitemap modules, and cannot borrow items from "Not Live" sites. "Not Live" sites are hidden from search engines and do not appear in listings of live sites. When you are building a site you probably want it to be "Not Live," and when it is ready for primetime you should set it to be "Live" so it can be indexed.'));
			$this->set_comments( 'other_base_urls',form_comment('This field is used by the stats integration feed to identify other directories whose stats you want to see along with this site\'s. Enter URLs relative to the server base, separated by commas (e.g. <em>/foo/bar/, /bar/foo/</em>.) You can ignore this field if you are not running a stats package integrated with Reason.'));
			$this->set_comments( 'name',form_comment('The name of the site.'));
			$this->set_comments( 'unique_name',form_comment('A stable textual identifier for the site. This should be a url-safe string -- no spaces, weird characters, etc.'));
			$this->set_comments( 'base_breadcrumbs',form_comment('If you want to add breadcrumb navigation before the root level of the site, place initial breadcrumb html here. <br />Example: &lt;a href="/asite/"&gt;A site&lt;/a&gt; &amp;gt; &lt;a href="/asite/another/"&gt;Another Site&lt;/a&gt;'));
			$this->set_comments( 'description',form_comment('A general description of the site. This is shown when administrators log in to the site, and in the child_sites module.'));
			// a temporary fix until we get assets settled
			$this->set_value( 'asset_directory','asset' );
			$this->change_element_type( 'asset_directory','hidden' );

			// get rid of archaic fields
			$this->remove_element( 'script_url' );

			// check for valid data
			$this->add_required( 'base_url' );
			$this->add_required( 'primary_maintainer' );
			
			// Make sure site is given a unique name for stats & other stuff
			$this->add_required( 'unique_name' );
			//$this->add_required( 'site_type' );
			
			$this->alter_editor_options_field();
			
			$this->add_relationship_element('theme', id_of('theme_type'), 
			relationship_id_of('site_to_theme'),'right','select');
			
			$this->add_relationship_element('site_type', id_of('site_type_type'), 
			relationship_id_of('site_to_site_type'));

			
			// if this is a new site, set the loki buttons to 'no tables'
			if( $this->is_new_entity() )
				$this->set_value( 'loki_default','notables' );

			$this->set_comments( 'loki_default',form_comment('The HTML editor options available when editing content on the site.'));
			$this->set_order(array('name','unique_name','primary_maintainer','base_url','base_breadcrumbs','description','keywords','department','site_state','loki_default','other_base_urls','use_page_caching','theme','allow_site_to_change_theme','site_type','use_custom_footer','custom_footer',));
		} // }}}
		function alter_editor_options_field()
		{
			$options = html_editor_options($this->get_value('id'));
			
			if(!empty($options))
			{
				$this->change_element_type( 'loki_default','select_no_sort',array( 'options' => $options ) );
				$this->set_display_name('loki_default',prettify_string(html_editor_name($this->get_value('id'))).' Options');
			}
			else
			{
				$this->change_element_type( 'loki_default','hidden');
			}
		}
		function run_error_checks() // {{{
		{
		
			if( !$this->has_error( 'primary_maintainer' ) )
			{
				// Make sure the primary maintainer exists in the directory
				$dir = new directory_service();
				if (!$dir->search_by_attribute('ds_username', $this->get_value('primary_maintainer')))
					$this->set_error( 'primary_maintainer','Invalid username for primary maintainer' );
			}

			// check for spaces
			if( !$this->has_error( 'base_url' ) )
			{
				if( !eregi( '^[a-z0-9_/]*$', $this->get_value('base_url') ) )
				{
					$this->set_error( 'base_url', 'Your base URL contains illegal characters. Allowable characters are letters, numbers, underscores, and slashes.' );
				}
			}
			
			if( $this->get_value('use_custom_footer') != 'yes' )
			{
				$this->set_value('custom_footer','');
			}


			// file/dir check - don't overwrite real files
			if( !$this->has_error( 'base_url' ) AND $this->get_value('base_url') )
			{
				$clean_url = '/'.trim_slashes($this->get_value('base_url')).'/';
				$clean_url = str_replace('//','/',$clean_url);
				$this->set_value ('base_url', $clean_url);
				//check against other base_urls
				$es = new entity_selector();
				$es->add_type( id_of( 'site' ) );
				$es->add_relation('base_url = "'.$this->get_value('base_url').'"');
				$es->add_relation('entity.id != "'.$this->get_value('id').'"');
				$es->set_num( 1 );
				$sites = $es->run_one();
				if(!empty($sites))
				{
					$site = current($sites);
					$this->set_error('base_url','The site <strong>'.$site->get_value('name').'</strong> already has the base url '.$site->get_value('base_url').'. Base URLs must be unique.');
					$this->add_comments( 'base_url', form_comment('<span style="color: #f00"><strong>'.$site->get_value('name').'</strong> already has that base url.  Please use another.</span>' ) );
				}
			}

		} // }}}
		
		function finish() // {{{
		{
			$first_time = empty($this->old_entity_values['base_url']);

			if($first_time) // a new site
			{
				// create site entry
				$site_id = $this->get_value('id');

				// add the logged in user to the site
				create_relationship( $site_id, $this->admin_page->user_id, relationship_id_of( 'site_to_user' ) );
				if($this->get_value( 'primary_maintainer' ))
				{
					$primary_maintainer_user_id = get_user_id( $this->get_value( 'primary_maintainer' ) );
					if( !empty($primary_maintainer_user_id) && $primary_maintainer_user_id != $this->admin_page->user_id )
					{
						create_relationship( $site_id, $primary_maintainer_user_id , relationship_id_of( 'site_to_user' ) );
					}
				}
				
				// add the page,image, and blurb modules
				create_relationship( $site_id, id_of('minisite_page'), relationship_id_of('site_to_type'));
				create_relationship( $site_id, id_of('image'), relationship_id_of('site_to_type'));
				create_relationship( $site_id, id_of('text_blurb'), relationship_id_of('site_to_type'));

				// create root page and set it as its own parent
				$root_page = reason_create_entity( $site_id, id_of('minisite_page'), $this->admin_page->user_id, $this->get_value('name'),array('nav_display'=>'Yes','new'=>'0'));
				create_relationship( $root_page, $root_page, relationship_id_of('minisite_page_parent') );
				
				$this->create_base_dir();
			}
			elseif( $this->old_entity_values['base_url'] != $this->get_value('base_url') ) // try to move the directory if it has changed
			{
				$this->move_base_dir($this->old_entity_values['base_url'], $this->get_value('base_url'));
			}
			
			//update URL history informaiton. 
			reason_include_once( 'function_libraries/root_finder.php');
			reason_include_once( 'function_libraries/URL_History.php');
			$site_id = $this->get_value( 'id' );
			$page_id = root_finder( $site_id ); 
			if(!empty($page_id))
			{
				update_URL_history( $page_id );
			}                      
		} // }}}
		
		function create_base_dir()
		{
			$path = WEB_PATH.trim_slashes($this->get_value('base_url'));
			if(!is_dir($path))
			{
				include_once(CARL_UTIL_INC.'basic/filesystem.php');
				mkdir_recursive($path, 0775);
			}
			$um = new url_manager( $this->get_value( 'id'));
			$um->update_rewrites();
		}
		// This function does not truly move the base dir
		// it actually makes a new dir and moves the .htaccess file from the old dir
		// otherwise we might accidentally move other sites that are inside the old dir
		// we might want to delete the old dir (recursively?) if it's empty after the .htaccess move
		// Otherwise the filesystem gets ugly
		// We're not doing this yet because svn might get unhappy
		function move_base_dir($old_base_dir, $new_base_dir)
		{
			if(!empty($old_base_dir) && !empty($new_base_dir))
			{
				$old_path = WEB_PATH.trim_slashes($old_base_dir);
				$new_path = WEB_PATH.trim_slashes($new_base_dir);
				if(!is_dir($new_path))
				{
					include_once(CARL_UTIL_INC.'basic/filesystem.php');
					if( mkdir_recursive($new_path, 0775) )
					{
						// we don't want to move the entire directory because other reason sites
						// might live in the directory, which we may not want to move
						// instead, we just move the .htaccess file.
						// This ensures that we won't lose any custom rules
						// that have been added to the .htaccess file.
						if(file_exists($old_path.'/.htaccess') && !file_exists($new_path.'/.htaccess'))
						{
							rename($old_path.'/.htaccess',$new_path.'/.htaccess');
						}
					}
				}
				else
				{
					// see above
					if(file_exists($old_path.'/.htaccess') && !file_exists($new_path.'/.htaccess'))
					{
						rename($old_path.'/.htaccess',$new_path.'/.htaccess');
					}
				}
				$um = new url_manager( $this->get_value( 'id'));
				$um->update_rewrites();
				if($old_path != $new_path)
				{
					$file_contents = '# reason-auto-rewrite-begin !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n";
					$file_contents .= '# THIS SECTION IS AUTO-GENERATED - DO NOT TOUCH'."\n";
					$file_contents .= '#!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n\n";

					$file_contents .= 'RewriteEngine On'."\n\n";
					$file_contents .= 'RewriteRule ^$ '.REASON_HTTP_BASE_PATH.'displayers/push_moved_site.php?id='.$this->get_value('id')."\n\n";
					
					$file_contents .= '# reason-auto-rewrite-end !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'."\n";
					
					$handle = fopen($old_path.'/.htaccess', 'w');
					fputs($handle, $file_contents);
					fclose($handle);
					chmod($old_path.'/.htaccess', 0664);
				}
			}
		}
	}
?>
