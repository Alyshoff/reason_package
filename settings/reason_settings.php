<?php
/**
 * Reason Settings
 *
 * This file contains all of the settings needed for Reason to function
 * (except for those in paths.php.) Many of these settings can/should be altered to match
 * the environment for this Reason instance.
 * @package Reason_Core
 */
 	include_once( 'paths.php');
	
	/**
	 * REASON_VERSION
	 * This setting identifies the current version of Reason
	 */
	define( 'REASON_VERSION','4.0' );
 
	////////////////////////////////////////////////////////////
	//
	// Begin items you may need to change to get Reason up and running
	//
	// This area contains setting you will likely need to edit to run Reason
	//
	////////////////////////////////////////////////////////////

	/**
	 * REASON_HTTP_BASE_PATH
	 * This setting identifies the location of Reason's web-available directory
	 * If you unpack the Reason package outside your web tree, 
	 * make an alias to reason_x.x/www somewhere in your web tree and note the location
	 * of that alias here.
	 * If you want to have Reason be the primary service on your server,
	 * you can make your Apache root be the www directory 
	 * and simply enter a '/' for this setting.
	 */
	define( 'REASON_HTTP_BASE_PATH','/reason_package/reason_'.REASON_VERSION.'/www/');
	
	/**
	 * REASON_GLOBAL_FEEDS_PATH
	 * This setting identifies the directory that global feeds will be placed in
	 * this should be relative to the web root.
	 */
	define( 'REASON_GLOBAL_FEEDS_PATH', 'global_feeds');
	
	/**
	 * APACHE_MIME_TYPES
	 * The location of Apache's mime.types file - used by asset access manager to determine
	 * what mime type to use when delivering reason managed assets
	 */
	define ( 'APACHE_MIME_TYPES', '/etc/mime.types' );
	
	/**
	 * REASON_DB
	 * This setting identifies connection name for the Reason database
	 * Actual credentials and database info are kept in a separate xml file
	 */
	define( 'REASON_DB', 'reason_connection' );
	
	/**
	 * REASON_SITE_DIRECTORY_OWNER
	 * the user group which site directories should belong to so that Reason can write .htaccess rules
	 * This should be the same as either the user or the group that Apache/php runs as
	 */
	define( 'REASON_SITE_DIRECTORY_OWNER', 'www' ); // replace this with the user/group that Apache runs as
	
	////////////////////////////////////////////////////////////
	//
	// End items you may need to change to get Reason up and running
	//
	// You should be able to run reason without continuing further in this file
	//
	////////////////////////////////////////////////////////////
	
	////////////////////////////////////////////////////////////
	//
	// Start things you may want to change at some point
	//
	// This section contains settings that may be useful to alter,
	// but a basic install of Reason should work OK without touching them.
	//
	////////////////////////////////////////////////////////////
	
	/**
	 * DISABLE_REASON_LOGIN
	 * Set this to true if you want to temporarily keep people from logging in to Reason
	 * false = normal -- people can log in
	 * true = shut down -- people cannot log in
	 * Boolean (e.g. true, false -- no quotes)
	 */
	define('DISABLE_REASON_LOGIN', false);
	
	/**
	 * ADMIN_NOTIFICATIONS_EMAIL_ADDRESSES
	 * Some code will send email to an administrator if it encounters an error.
	 * This email address (or email addresses) can be set here if different from
	 * WEBMASTER_EMAIL_ADDRESS
	 */
	define('ADMIN_NOTIFICATIONS_EMAIL_ADDRESSES',WEBMASTER_EMAIL_ADDRESS);
	
	/**
	 * THIS_IS_A_DEVELOPMENT_REASON_INSTANCE
	 * Identifies whether this copy of Reason is a testing/development instance of Reason
	 * or a production/live instance of Reason
	 * Boolean (e.g. true, false -- no quotes)
	 * true = testing/development
	 * false = production/live
	 */
	define( 'THIS_IS_A_DEVELOPMENT_REASON_INSTANCE', true );
	
	/**
	 * REASON_HOST_HAS_VALID_SSL_CERTIFICATE
	 * Set this to true if you have a valid certificate signed by a known certificate authority.
	 * Otherwise set this to false (if you have a self-signed cert, for example).
	 *
	 * If this is set to true and you do not have a valid certificate, Reason will not
	 * be able to update .htaccess files.
	 *
	 * If this is set to false, Reason will work in either case, but it is better practice
	 * to set it to true when possible to eliminate the possiblity of man-in-the-middle attacks.
	 */
	define('REASON_HOST_HAS_VALID_SSL_CERTIFICATE', false);
	
	/**
	 * REASON_SESSION_TIMEOUT
	 *
	 * Determines how long a Reason session lasts, in minutes.
	 */
    define('REASON_SESSION_TIMEOUT', 60);
	
	/**
	 * REASON_SESSION_TIMEOUT_WARNING
	 *
	 * Determines how long before the end of a Reason session the user is notified, in minutes.
	 */
    define('REASON_SESSION_TIMEOUT_WARNING', 5);
	
	/**
	 * REASON_DEFAULT_HTML_EDITOR
	 *
	 * The name of the editor Reason sites use by default.
	 * This can be overridden in the Master Admin.
	 * Note that this is the same as the name of the file in lib/[core|local]/html_editors, 
	 * but without the ".php"
	 */
	define('REASON_DEFAULT_HTML_EDITOR', 'loki_2');
	
	/**
	 * REASON_URL_FOR_GENERAL_FEED_HELP
	 * Identifies a URI where users can get more information about using feeds
	 * Comment out this line if you don't want to provide a link for feed assistance
	 * (Developer note: surround any use of this constant in a if(defined()) block)
	 */
	//define( 'REASON_URL_FOR_GENERAL_FEED_HELP', 'http://www.domain_name.domain/your/path/here/' );
	
	/**
	 * REASON_URL_FOR_PODCAST_HELP
	 * Identifies a URI where users can get more information about subscribing to podcasts
	 * Comment out this line if you don't want to provide a link for feed assistance
	 * (Developer note: surround any use of this constant in a if(defined()) block)
	 */
	//define( 'REASON_URL_FOR_PODCAST_HELP', 'http://www.domain_name.domain/your/path/here/' );
	
	/**
	 * REASON_LOGIN_PATH
	 * This setting identifies the location of the login site relative to the server root directory/
	 * Reason uses this to make links to log in and to header in the cases of secured content
	 */
	define( 'REASON_LOGIN_PATH', 'login/' );
	
	define ('ACCESS_LOG_USER_AGENT_REGEX_FILTER', '(?!htdig|msnbot|psbot|NaverBot|Gigabot|sohu|YahooSeeker|.*Googlebot|.*ZyBorg|.*Slurp|.*Jeeves\/Teoma)' );
	
	/**
	 * REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS
	 * A snippet of XHTML that informs people how they can have the user permissions changed for their site
	 *  -- used on the view users backend module
	 */
	define('REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS', '<a href="mailto:'.WEBMASTER_EMAIL_ADDRESS.'">'.WEBMASTER_NAME.'</a>');
	/**
	 * ERROR_404_PATH
	 * This setting identifies the location of the 404 page relative to the server root directory/
	 * It should not have an initial slash.
	 * Example: errors/404.php
	 */
	define('ERROR_404_PATH', 'errors/404.php');
	
	/**
	 * ERROR_404_FULL_PATH
	 * This setting identifies the absolute location of the 404 page in the filesystem.
	 * Assuming the 404 page is within the server root, this setting will probably look like this:
	 * REASON_HTTP_BASE_PATH.ERROR_404_PATH
	 */
	define('ERROR_404_FULL_PATH', REASON_HTTP_BASE_PATH.ERROR_404_PATH);
	
	/**
	 * ERROR_404_PAGE
	 * This setting identifies the URI of the 404 page
	 * It will typically look like this: 'http://'.HTTP_HOST_NAME.'/'.ERROR_404_PATH
	 * Reason uses this to header when a query-string-based resource is not available
	 * You should also set the Apache 404 page to this path
	 * This page is important, as it handles redirection for moved Reason pages
	 * If you want a custom 404 page, you must add these lines to the very top of the 404 page file:
	 * include_once( 'reason_header.php' );
   	 * reason_include( 'scripts/urls/404action.php' );
	 */
	define( 'ERROR_404_PAGE', 'http://'.HTTP_HOST_NAME.'/'.ERROR_404_PATH );
	// Use this for the release package
	
	/**
	 * ERROR_403_PATH
	 * This setting identifies the location of the 403 page relative to the server root directory/
	 * It should not have an initial slash.
	 * Example: errors/403.php
	 */
	define('ERROR_403_PATH', 'errors/403.php');
	
	/**
	 * ERROR_403_FULL_PATH
	 * This setting identifies the absolute location of the 403 page in the filesystem.
	 * Assuming the 404 page is within the server root, this setting will probably look like this:
	 * REASON_HTTP_BASE_PATH.ERROR_403_PATH
	 */
	define('ERROR_403_FULL_PATH', REASON_HTTP_BASE_PATH.ERROR_403_PATH);
	
	/**
	 * ERROR_404_PAGE
	 * This setting identifies the URI of the 403 page
	 * It will typically look like this:
	 * 'http://'.HTTP_HOST_NAME.'/'.ERROR_403_PATH
	 */
	define( 'ERROR_403_PAGE', 'http://'.HTTP_HOST_NAME.'/'.ERROR_403_PATH );
	
	/**
	 * ALLOW_REASON_SITES_TO_SWITCH_THEMES
	 * Set this to true if you want sites to be able to switch themes (note that
	 * you can still turn off theme switching on a site-by-site basis)
	 * Set this to false if you only want to set site themes in the Master Admin area
	 * Boolean (e.g. true, false -- no quotes)
	 */
	define( 'ALLOW_REASON_SITES_TO_SWITCH_THEMES', true );
	
	/**
	 * REASON_PREVIOUS_HOSTS
	 * comma-separated list of hosts.  No spaces.
	 * This is used to identify links to reason resources that point to a previous host
	 * If this is the first server this Reason instance has been on, you can leave this string empty
	 */
	define( 'REASON_PREVIOUS_HOSTS', '' );
	
	/**
	 * REASON_SEARCH_ENGINE_URL
	 *
	 * The url of the search engine to use for searching Reason sites.
	 * To disable the search module, comment out this line or set its value to an empty string.
	 * (e.g. for Google, use the string 'http://www.google.com/search'.)
	 */
	define('REASON_SEARCH_ENGINE_URL','http://www.google.com/search');
	
	/**
	 * REASON_SEARCH_FORM_METHOD
	 *
	 * The method that the search module's form uses.
	 * Shoud be 'get' or 'post', depending on what the search engine you are using wants.
	 */
	define('REASON_SEARCH_FORM_METHOD','get');
	
	/**
	 * REASON_SEARCH_FORM_INPUT_FIELD_NAME
	 *
	 * The name of the field that contains the search string in the search module.
	 * This should not contain any double quotes.
	 * This should be set to be the request key that the search engine expects
	 * (e.g. for Google, use the string 'q'.)
	 */
	define('REASON_SEARCH_FORM_INPUT_FIELD_NAME','q');
	
	/**
	 * REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME
	 *
	 * The name of the field that contains the URI of the site so that the 
	 * search engine can restrict its results to the site being searched.
	 * This should not contain any double quotes.
	 * This should be set to be the request key that the search engine expects
	 * (e.g. for Google, use the string 'as_sitesearch'.)
	 */
	define('REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME','as_sitesearch');
	
	/**
	 * REASON_SEARCH_FORM_HIDDEN_FIELDS
	 *
	 * A chunk of xhtml that gets inserted into the search module.
	 * This allows you to pass the search engine any other values you want.
	 * Example: '<input type="hidden" name="method" value="and" />'
	 */
	define('REASON_SEARCH_FORM_HIDDEN_FIELDS','');
	
	/**
	 * REASON_ADMIN_LOGO_MARKUP
	 * A snippet of XHTML that you can use to customize the Reason admin banner
	 */
	define('REASON_ADMIN_LOGO_MARKUP','<a href="?">Reason at '.FULL_ORGANIZATION_NAME.'</a>');
	
	/**
	 * REASON_TAGLINE
	 * A short sentence that serves as a tagline in the Reason admin banner
	 */
	define('REASON_TAGLINE','Web Administration For '.FULL_ORGANIZATION_NAME);
	
	/**
	 * MEDIA_POPUP_TEMPLATE_FILENAME
	 * The name of the file in lib/xxx/popup_templates/ to use to generate the media popup markup
	 */
	define('MEDIA_POPUP_TEMPLATE_FILENAME','generic_media_popup_template.php');
	
	/**
	 * IMAGE_POPUP_TEMPLATE_FILENAME
	 * The name of the file in lib/xxx/popup_templates/ to use to generate the image popup markup
	 */
	define('IMAGE_POPUP_TEMPLATE_FILENAME','generic_image_popup_template.php');
	
	/**
	 * REASON_STANDARD_MAX_IMAGE_HEIGHT
	 *
	 * When images are uploaded to Reason, they are resized automatically.
	 * This setting determines their maximum vertical size in pixels
	 */
	define('REASON_STANDARD_MAX_IMAGE_HEIGHT', 500);
	
	/**
	 * REASON_STANDARD_MAX_IMAGE_WIDTH
	 *
	 * When images are uploaded to Reason, they are resized automatically.
	 * This setting determines their maximum horizontal size in pixels
	 */
	define('REASON_STANDARD_MAX_IMAGE_WIDTH', 500);
	
	/**
	 * REASON_STANDARD_MAX_THUMBNAIL_HEIGHT
	 *
	 * When images are uploaded to Reason, thumbnails are automatically created.
	 * This setting determines their maximum vertical size in pixels
	 */
	define('REASON_STANDARD_MAX_THUMBNAIL_HEIGHT', 125);
	
	/**
	 * REASON_STANDARD_MAX_THUMBNAIL_WIDTH
	 *
	 * When images are uploaded to Reason, thumbnails are automatically created.
	 * This setting determines their maximum horizontal size in pixels
	 */
	define('REASON_STANDARD_MAX_THUMBNAIL_WIDTH', 125);
	
	/**
	 * Set custom auto thumbnail sizes on a site-by-site basis using this array
	 *
	 * $GLOBALS['_reason_site_custom_thumbnail_sizes'] = array(
	 *		'site_unique_name'=>array('height'=>250,'width'=>200),
	 *		'other_site_unique_name'=>array('height'=>200,'width'=>175),
	 *	);
	 *
	 */
	$GLOBALS['_reason_site_custom_thumbnail_sizes'] = array();
	
	/**
	 * REASON_USES_DISTRIBUTED_AUDIENCE_MODEL
	 *
	 * Reason has two basic modes for how audiences are handled: "Unified" and "Distributed."
	 *
	 * In the "Unified" model, there is a single set of audiences, which all Reason sites share.
	 * The "Unified" model is appropriate for instances of Reason where all subsites belong to the same 
	 * organization, whose various subsites and subgroups all share a standard set of audiences.
	 * 
	 * In the "Distributed" model, each site manages its own set of audiences
	 * (and they can be borrowes/shared as usual.) The "Distributed" model is appropriate for
	 * instances of Reason shared by multiple organizations or units who have different sets of primary audiences.
	 *
	 * For the "unified" model, set this constant to false.
	 * For the "distributed" model, set this constant to true.
	 *
	 */
	define('REASON_USES_DISTRIBUTED_AUDIENCE_MODEL', false);
	
	/**
	 * REASON_USERS_DEFAULT_TO_AUTHORITATIVE
	 *
	 * This constant defines the default value for the user_authoritative_source 
	 * field on the user type. If it is true, when a new user is created the
	 * user_authoritative_source field will be set to "reason".
	 * If it is false, when a user is created the user_authoritative_source field will be set to "external".
	 * 
	 * In plain(ish) English, set this constant to TRUE if your instance if Reason
	 * is not integrated with other, more authoritative directory services. 
	 * In a base install, this will be set to TRUE.
	 *
	 * If your instance of Reason is integrated with an authoritative directory service,
	 * and user entries in Reason mainly serve as stubs, set this constant to FALSE.
	 *
	 * Of course, the user_authoritative_source field can be edited on a user-by-user 
	 * basis in a mixed environment; this constant simply sets the default value.
	 */
	define('REASON_USERS_DEFAULT_TO_AUTHORITATIVE', true);
	
	////////////////////////////////////////////////////////////
	//
	// End things you may want to change at some point
	//
	////////////////////////////////////////////////////////////
	
	////////////////////////////////////////////////////////////
	//
	// Start things your probably *DON'T* want to change
	//
	////////////////////////////////////////////////////////////
	
	/**
	 * REASON_CSV_DIR
	 * This setting identifies the filesystem location of the reason-managed csv data.
	 */
	define( 'REASON_CSV_DIR', REASON_INC.'data/csv_data/' );
	
	/**
	 * PHOTOSTOCK
	 * This setting identifies the filesystem location of the reason-managed images directory.
	 */
	define( 'PHOTOSTOCK', REASON_INC.'data/images/' );
	
	/**
	 * WEB_PHOTOSTOCK
	 * This setting identifies the web path for reason-managed images
	 * This should be relative to the server (e.g. don't include the domain name here)
	 */
	define( 'WEB_PHOTOSTOCK', REASON_HTTP_BASE_PATH.'images/' );
	
	/**
	 * REASON_TEMP_DIR
	 * This setting defines the location for Reason temporary data
	 */
	define( 'REASON_TEMP_DIR', REASON_INC.'data/tmp/' );
	
	/**
	 * REASON_LOG_DIR
	 * This setting defines the location for Reason to log information about its activity
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'REASON_LOG_DIR', REASON_INC.'data/tmp/' );
	
	/**
	 * PAGE_CACHE_LOG
	 * This setting defines the location for Reason to log information about page caching performance.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'PAGE_CACHE_LOG', REASON_LOG_DIR.'page_cache_log' );

	/**
	 * REASON_PATH
	 * This setting identifies the filesystem location of the Reason codebase
	 * It should be the same as the constant REASON_INC defined in paths.php
	 */
	define( 'REASON_PATH',REASON_INC);
	
	/**
	 * REASON_HOST
	 * This setting identifies the http host name (e.g. www.foo.com)
	 * It should be the same as the constant HTTP_HOST_NAME defined in paths.php
	 * All scripts should use this or HTTP_HOST_NAME rather than HTTP_HOST or
	 * other server-defined variables, so that scripts can be run from the command line.
	 */
	define( 'REASON_HOST',HTTP_HOST_NAME);

	/**
	 * WEB_TEMP
	 * This setting defines a web-available temporary directory.
	 * It is used to store uploads temporarily while error checking is resolved.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'WEB_TEMP', REASON_HTTP_BASE_PATH.'tmp/' );
	
	/**
	 * REASON_CACHE_DIR
	 * This setting identifies the directory used to store cache files.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'REASON_CACHE_DIR', REASON_INC.'data/cache' );

	/**
	 * CM_VAR_PREFIX
	 * The prefix prepended to content manager variables when an associated entity is created.
	 */
	define( 'CM_VAR_PREFIX', '__old_' );

	/**
	 * ASSET_PATH
	 * The filesystem path to the directory that contains reason-managed files
	 */
	define( 'ASSET_PATH', REASON_INC.'data/assets/' );
	
	/**
	 * WEB_ASSET_PATH
	 * The web address of all the assets in reason.  The difference between this and ASSET_PATH is the same as the difference between PHOTOSTOCK and WEB_PHOTOSTOCK.
	 */
	define( 'WEB_ASSET_PATH',  REASON_HTTP_BASE_PATH.'assets/' );
	
	/**
	 * MINISITE_ASSETS_DIRECTORY_NAME
	 * This setting defines the assets directory vis-a-vis a site's base directory
	 * So a site at /foo/bar/ will have its assets available at /foo/bar/this_string/
	 * This directory name should be defined with no slashes
	 * Note that changing this directory name could break links to assets if this is an existing instance of Reason
	 */
	 define( 'MINISITE_ASSETS_DIRECTORY_NAME',  'assets' );
	
	/**
	 * MINISITE_FEED_DIRECTORY_NAME
	 * This setting defines the feeds directory vis-a-vis a site's base directory
	 * So a site at /foo/bar/ will have its feeds live at /foo/bar/this_string/
	 * This path should be defined with slashes before and after, a la "/feeds/"
	 * Note that changing this directory name could break links to feeds if this is an existing instance of Reason
	 */
	define( 'MINISITE_FEED_DIRECTORY_NAME', 'feeds' );
	
	/**
	 * FEED_GENERATOR_STUB_PATH
	 * This setting identifies the location of the feed generation script
	 * The url manager uses this setting to create the feed rewrite rules
	 */
	define( 'FEED_GENERATOR_STUB_PATH',REASON_HTTP_BASE_PATH.'displayers/generate_feed.php' );
	/**
	 * REASON_WEB_ADMIN_PATH
	 * This setting identifies the location of the Reason admin area
	 * It should be in the form of foo.host_name.bar/http/path/to/admin/area/
	 */
	define('REASON_WEB_ADMIN_PATH', HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'admin/' );

    /**
	 * THOR_FORM_DB_CONN
	 * The name of the database connection used to store form data
	 * Thor settings will define the constant THOR_FORM_DB_CONN
	 */
	include ( SETTINGS_INC . '/thor_settings.php' );

	/**
	 * REASON_SESSION_CLASS
	 * The name of this instance of Reason's session handling class
	 */
	define( 'REASON_SESSION_CLASS', 'Session_PHP' );

    // Note: Reason is not configured to manage media uploads in this release.
	// It may be theoretically possible to get it working by setting the
	// streaming server info to your server, but this has not been tested.
	// Reason *will* manage media separately uploaded, even when REASON_MANAGES_MEDIA
	// is set to false.
	
	define('REASON_MANAGES_MEDIA',false); // change this to true when things are better
	
	define('NOTIFY_WHEN_MEDIA_IS_IMPORTED',false);
	
	define('MEDIA_FILESIZE_NOTIFICATION_THRESHOLD',0);
	
	define('MEDIA_NOTIFICATION_EMAIL_ADDRESSES',WEBMASTER_EMAIL_ADDRESS);
	
	/**
	 * REASON_BASE_STREAM_URL
	 * The http host/domain name for the media server that Reason uses
	 * This should have a trailing slash and no protocol, e.g. "media.foo.com/"
	 */
	define('REASON_BASE_STREAM_URL' , 'streaming_server.your_domain.com/');
	
	/**
	 * REASON_STREAM_DIR
	 * The path on remote host for media management
	 * This should have no slashes on either end, e.g "foo/bar"
	 * This is the http-available directory that is dedicated to Reason-managed media
	 * It should be writable by the user identified in REASON_STREAMING_USER
	 */
	define('REASON_STREAM_DIR' , 'reason_media');
	
	/**
	 * REASON_STREAMING_HOST
	 * the host name of the media server for file transfer/shell access
	 */
	define('REASON_STREAMING_HOST' , 'streaming_machine_name.your_domain.com');
	
	/**
	 * REASON_STREAM_BASE_PATH
	 * The path to the webserver root on the media server
	 */
	define('REASON_STREAM_BASE_PATH' , '/usr/local/helix/Content/');
	
	/**
	 * REASON_STREAMING_USER
	 * The username used to connect to the media server
	 */
	define('REASON_STREAMING_USER' , REASON_SITE_DIRECTORY_OWNER); 
	
	/**
	 * REASON_REMOTE_HOME_PATH
	 * The location of the personal directory space on the media server
	 */
	define('REASON_REMOTE_HOME_PATH', '/mnt/people/home/');

	// info about location and name of the utility class for moving av files around
	
	/**
	 * REASON_AV_TRANSFER_UTILITY_LOCATION
	 * The location of the file that contains the class for transferring files to the media server
	 */
	define('REASON_AV_TRANSFER_UTILITY_LOCATION', 'ssh/streaming_server.php');
	
	/**
	 * REASON_AV_TRANSFER_UTILITY_CLASS_NAME
	 * The name of the class for transferring files to the media server
	 */
	define('REASON_AV_TRANSFER_UTILITY_CLASS_NAME', 'streaming_server');
	
	/**
	 * REASON_FLASH_VIDEO_PLAYER_URI
	 * http location of the .swf file used to play flash video (.flv) files
	 */
	define('REASON_FLASH_VIDEO_PLAYER_URI', FLVPLAYER_HTTP_PATH.'flvplayer.swf');
	
	/**
	 * QUICKTIME_LINK_WEB_PATH
	 * http location of the script which generates quicktime link files for streaming quicktime media
	 */
	define('QUICKTIME_LINK_WEB_PATH',REASON_HTTP_BASE_PATH.'displayers/qt_link.php');
	
	/**
	 * REASON_ADMIN_CSS_DIRECTORY
	 * Indicates the directory location (from the web root) of the Reason admin css files
	 */
	define('REASON_ADMIN_CSS_DIRECTORY',REASON_HTTP_BASE_PATH.'css/reason_admin/');
	
	/**
	 * REASON_ADMIN_IMAGES_DIRECTORY
	 * Indicates the directory location (from the web root) of the Reason admin image files
	 */
	define('REASON_ADMIN_IMAGES_DIRECTORY',REASON_HTTP_BASE_PATH.'ui_images/reason_admin/');
	
	/**
	 * REASON_IMAGE_VIEWER
	 * The path from the web root of the image popup script
	 */
	define( 'REASON_IMAGE_VIEWER',REASON_HTTP_BASE_PATH.'displayers/image.php' );
	
	define('REASON_PRIMARY_NEWS_PAGE_URI','http://'.HTTP_HOST_NAME.'/news/'); // this should go away
	define('REASON_PRIMARY_EVENTS_PAGE_URI','http://'.HTTP_HOST_NAME.'/calendar/'); // this should go away
	define('REASON_STATS_URI_BASE','');
	
	// Reason can be configured to pull images from a remote server when
	// batch uploading, but this still contains code specific to Carleton.
	// Batch uploading of images shold be ready by the next release.
	
	define('REASON_SSH_DEFAULT_HOST', '');
	define('REASON_SSH_DEFAULT_USERID', REASON_SITE_DIRECTORY_OWNER);
	define('REASON_SSH_TEMP_STORAGE', "/tmp/import");
	define('REASON_IMAGE_IMPORT_HOST','');
	define('REASON_IMAGE_IMPORT_BASE_PATH','/mnt/people/home');
	define('REASON_IMAGE_IMPORT_USERID',REASON_SITE_DIRECTORY_OWNER);
	
	/**
	 * PREVENT_MINIMIZATION_OF_REASON_DB
	 *
	 * This should almost always be set to true.
	 * Only set this to false if this is an instance whose primary purpose 
	 * for existence is to have most of its entities deleted so as to create 
	 * a clean new base for a new Reason instance.
	 */
	define('PREVENT_MINIMIZATION_OF_REASON_DB', true);
	
	/**
	 * WEB_JAVASCRIPT_PATH
	 * The web address of javascript files used by reason modules.
	 */
	define( 'WEB_JAVASCRIPT_PATH', REASON_HTTP_BASE_PATH.'js/' );

	/**
	 * USE_JS_LOGOUT_TIMER
	 * Use the session timeout javascript
	 */
	define( 'USE_JS_LOGOUT_TIMER', true);
	
	/**
	 * DEFAULT_TO_POPUP_ALERT
	 * This specifies the way in which the user is notified that their session has expired.
	 * A value of true means a javascript:alert() is used. 
	 * A value of false means a div-based notice is used.
	 * The alert (true) is more accessible, but also more obtrusive
	 * (e.g. the browser window will ask for attention even when user is doing something else.)
	 * Note that this setting is just for the default behavior; this can be set on a per-user basis.
	 */
	define( 'DEFAULT_TO_POPUP_ALERT', false);
	
	// This is for the session_cookie class, which is not fully implemented.
	//define('REASON_COOKIE_DOMAIN','.domain_name.domain');
	
	define( 'REASON_ICALENDAR_UID_DOMAIN','reason');
	
	/**
	 * Record the types that have feeds for the editor link 
	 *
	 * If you add a new feed for the Loki link dialog box, register it here so it can show up
	 */
	$GLOBALS['_reason_types_with_editor_link_feeds'] = array('minisite_page','news','event_type','asset');
?>
