<html>
<head>
<title>Reason Setup</title>
<style>
.error
{
color: red;
}

.success
{
color: green;
}
</style>
</head>
<body>

<h2>Reason Setup</h2>
<?

if (isset($_GET['curl_test']))
{
	echo '</body></html>';
	die;
}

?>
<p>This script should be run after you have setup Reason according to the instructions in the <a href="./install.htm">Reason Install Documentation</a>. 
The script will verify the Reason environment, perform a variety of checks for Reason utilities, confirm file paths and permissions, 
and then setup the first site and user for your instance.</p>
<?
// do what we can to enable error reporting
ini_set("display_errors","On");
error_reporting (E_ALL);

// Environmental checks - include path and basic location of files needed to perform other checks
if (isset($_POST['do_it_pass']) == false)
{
	echo '<h3>Verifying Environment</h3>';
	echo '<h4>Checking package availability</h4>';
	check_php_include_path();
	echo '...attempting to load paths.php, which loads the package_settings - a fatal error here probably means that the require_once statement in paths.php is referencing a file that does not exist<br/>';
	if (!file_exists('paths.php')) die_with_message('<p class="error">ERROR: The file paths.php in the reason_package directory does not appear to exist - check permissions and file paths.</p>');
	include_once('paths.php'); // paths loads the package_settings file
	if (!defined('REASON_INC'))
	{
		die_with_message('<p class="error">ERROR: The file paths.php was included, but did not properly include the package_settings.php file. Modify the require_once statement in paths.php
						  to include an absolute file system path reference to package_settings.php</p>
						  <p>Unless you are placing your settings files in a different location than the default, the absolute path of the package_settings.php file 
						  should probably look like this:</p>
						  <p><pre>'.$_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) .'/settings/package_settings.php</pre></p>');
	}
	else
	{
		$included_so_far = get_included_files();
		$package_settings_path = array_pop($included_so_far);
		echo '...paths.php loaded package_settings.php<br/>';
		echo '<p><strong>Package settings path</strong>: ' . $package_settings_path . '</p>';
		echo '<h4>Checking component availability</h4>';
		if (is_readable(INCLUDE_PATH . 'reason_setup.php'))
		{
			// verify settings files loaded by header.php before we load the header
			check_environment_and_trailing_slash(WEB_PATH, 'web path', 'Check the WEB_PATH constant in package_settings.php.</p><p>
								 The value should probably be</p>
								 <p><pre>'.$_SERVER['DOCUMENT_ROOT'].'/</pre>');
			check_environment(CARL_UTIL_INC.'error_handler/error_handler.php', 'error handler', 'Verify the path to CARL_UTIL_INC in package_settings.php');
			check_environment(DB_CREDENTIALS_FILEPATH, 'db credentials xml file', 'Verify the DB_CREDENTIALS_FILEPATH in package_settings.php and permissions');
			check_environment(DISCO_INC.'disco.php', 'disco include path', 'Verify the path to DISCO_INC in package_settings.php');
			check_environment(TYR_INC.'tyr.php', 'tyr include path', 'Verify the path to TYR_INC in package_settings.php');
			check_environment(THOR_INC.'thor.php', 'thor include path ', 'Verify the path to THOR_INC in package_settings.php');
			check_environment(XML_PARSER_INC.'xmlparser.php', 'xml parser', 'Verify the path to XML_PARSER_INC in package_settings.php');
			check_environment(HTML_PURIFIER_INC.'htmlpurifier.php', 'html purifier', 'Verify the path to HTML_PURIFIER_INC in package_settings.php');
			check_environment(SETTINGS_INC.'reason_settings.php', 'reason settings', 'Verify the path stored in the constant SETTINGS_INC in package_settings.php');
			check_environment(REASON_INC.'header.php', 'reason header', 'Verify the path stored in the constant REASON_INC in package_settings.php');
			include_once( REASON_INC.'header.php' );
			$found = array_search(SETTINGS_INC.'reason_settings.php', get_included_files());
			if ($found !== false)
			{
				echo '<p><strong>Reason settings path</strong>: ' . SETTINGS_INC.'reason_settings.php' . '</p>';
			}
			check_error_handler_log_file_dir();
			echo '<p style="color: green;"><strong>...the Reason environment has been loaded</strong></p>';
		}
		else die_with_message('<p class="error">ERROR: The INCLUDE_PATH constant ('.INCLUDE_PATH.') appears to be invalid.</p>
							   <p>Check package_settings.php to make sure the value is correct - it should probably be set to:</p>
							   <p><pre>'.$_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) .'/</pre></p>');
	}
}
else include_once('reason_header.php');
include_once( CARL_UTIL_INC . 'tidy/tidy.php' );
	
if (isset($_POST['do_it_pass']) == false)
{
	if (perform_checks() == false)
	{
		die_with_message('<p>Please address the identified problems and run this script again.</p>');
	}
	
	// we only get here if the above was okay ... we need database / credentials / file access for the following to work
	reason_include_once ('function_libraries/admin_actions.php');
	reason_include_once ('classes/entity_selector.php');
	$login_site_id = id_of('site_login');
	$login_site_entity = new entity($login_site_id);
	$path = WEB_PATH.trim_slashes($login_site_entity->get_value('base_url'));
	echo '<h3>Checking for Login Site</h3>';
	if(!is_dir($path))
	{
		echo '<p>Creating login site</p>';
		reason_include_once ('classes/url_manager.php');
		include_once(CARL_UTIL_INC.'basic/filesystem.php');
		mkdir_recursive($path, 0775);
		if (!is_dir($path)) die_with_message('<p>The login site folder at ' . $path.' could not be written. Check paths and permissions.</p>');
		else echo '<p>The login site folder at ' . $path.' has been created.</p>';
	}
	
	$htaccess = $path . '/.htaccess';
	if (!file_exists($htaccess))
	{
		reason_include_once ('classes/url_manager.php');
		echo '<p>Creating .htaccess rewrite rules</p>';
		$um = new url_manager( $login_site_id, true );
		$um->update_rewrites();
		if (!file_exists($htaccess)) die_with_message('<p>The login site .htaccess rules were not written to ' . $htaccess.'. Checks paths and permissions.</p>');
		else echo '<p>The .htaccess access rules were written to ' . $htaccess .'.</p>'; 
	}
	else echo '<p>The login site appears to be setup</p>';
}

if (admin_user_exists() == false)
{
	if (isset($_POST['do_it_pass']))
	{
		$password = create_pass();
		$password_hash = sha1($password);
		$user_id = create_admin_user($password);
		if ($user_id > 0)
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_relation ('((entity.unique_name = "master_admin") OR (entity.unique_name = "site_login"))');
			$result = $es->run_one();
			foreach ($result as $result)
			{
				// check if current primary maintainer is invalid, if so, switch it to the just created admin user
				$current_username = $result->get_value('primary_maintainer');
				$current_userid = get_user_id($current_username);

				if (empty($current_userid))
				{ 
					reason_update_entity( $result->id(), $user_id, array('primary_maintainer' => 'admin'), $archive = false);
				}
			}
			created_admin_HTML($password);
		}
		else 
		{
			die_with_message('<p class="error">Sorry to be the bearer of bad news, but the admin user does not exist and could not be created.</p>');
		}
	}	
	else
	{
		admin_user_HTML();
	}
}
else
{
	die_with_message('<p>This reason instance already has an admin user - you should consider moving this script out of the web tree or deleting it.
					  <p><a href="'.securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'">Login to Reason</a></p>');
}
?>
</body>
</html>
<?
function admin_user_exists()
{
	reason_include_once('function_libraries/admin_actions.php');
	return reason_unique_name_exists('admin_user');
}

function create_admin_user($password)
{
	reason_include_once ('classes/user.php');
	$password_hash = sha1($password);
	$my_user = new User();
	$user = $my_user->create_user('admin');
	$user_id = $user->id();
	reason_update_entity( $user_id, $user_id, array('unique_name' => 'admin_user', 'user_email' => WEBMASTER_EMAIL_ADDRESS, 'user_password_hash' => $password_hash, 'user_authoritative_source' => 'reason'), false);
	$admin_id = id_of('admin_role');
	$rel_id1 = relationship_id_of('user_to_user_role');
	$rel_id2 = relationship_id_of('site_to_user');
	$ma_id = id_of('master_admin');
	create_relationship($user_id, $admin_id, $rel_id1, false, true);
	create_relationship($ma_id, $user_id, $rel_id2, false, true);
	return $user_id;
}

function created_admin_HTML($password)
{
	echo '<h3>Admin User Created</h3>';
	echo '<p>The reason user <strong>admin</strong> has been created with password <strong>'.$password.'</strong></p>';
	echo '<p><strong>Write down the password!</strong> This script will not create another admin user unless the original is deleted.</p>';
	echo '<p>You should now be able to login to the <a href="'.securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'">reason administrative interface</a>.</p>';
}

function check_php_include_path()
{
	$include_path = ini_get('include_path');
	
	// method one - found to be unpredicable in some shared hosting environments (ie. dreamhost)
	$cur_path = dirname(__FILE__);
	$include_path_set = strpos($include_path, $cur_path);
	
	// method two - if this works the path is probably okay
	$alt_cur_path = $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']);
	$alt_include_path_set = strpos($include_path, $alt_cur_path);
	
	if (($include_path_set === false) && ($alt_include_path_set === false))
	{
		$include_path_separator = (setup_check_is_windows()) ? ';' : ':';
		die_with_message('<p class="error"><strong>ERROR:</strong> The reason_package directory must be part of your php include path.</p><p>Your current include path is:</p>
						  <p><pre>'.$include_path.'</pre></p>
						  <p>Please modify the include path line in your php.ini file so that it reads as follows</p>
						  <p><pre>include_path = "'.$include_path.$include_path_separator.$cur_path.'"</pre></p>
						  <p>If you do not have access to modify php.ini you may be able to create an .htaccess 
						  file that sets the include path. For this to work, AllowOverride Option must be enabled in your httpd.conf file. The .htaccess rule
						  should read as follows:</p>
					<p><pre>php_value include_path ".'.$include_path_separator.$cur_path.'"</pre></p>
					<p>Please run the script again after your include path has been properly setup</p>');
	}
	else
	{
		echo '...the php include_path includes the reason_package directory<br/>';
	}
}

function setup_check_is_windows()
{
	if (strtoupper(substr(PHP_OS,0,3) == 'WIN')) return true;
	else return false;
}

function perform_checks()
{
	$check_passed = 0;
	$check_failed = 0;
	echo '<h3>Performing Basic Checks</h3>';
	// perform checks - each check should echo a success or failure string, and return true if successful
	// $check_passed and $check_failed increment accordingly. perform_checks returns true if all checks pass
	
	// check mysql connections
	if (verify_mysql(REASON_DB, 'REASON_DB', 'reason_settings.php', 'entity')) $check_passed++;
	else $check_failed++;
	
	if (verify_mysql(THOR_FORM_DB_CONN, 'THOR_FORM_DB_CONN', 'thor_settings.php', false)) $check_passed++;
	else $check_failed++;
	
	if (http_host_check()) $check_passed++;
	else $check_failed++;
	
	if (tidy_check()) $check_passed++;
	else $check_failed++;
	
	if (curl_check()) $check_passed++;
	else $check_failed++;
	
	if (imagemagick_check()) $check_passed++;
	else $check_failed++;
	
	echo '<h3>Performing Directory and File Checks</h3>';
	
	if (data_dir_writable(WEB_PATH, 'WEB_PATH')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(REASON_CSV_DIR, 'REASON_CSV_DIR')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(REASON_LOG_DIR, 'REASON_LOG_DIR')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(ASSET_PATH, 'ASSET_PATH')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(PHOTOSTOCK, 'PHOTOSTOCK')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(REASON_TEMP_DIR, 'REASON_TEMP_DIR')) $check_passed++;
	else $check_failed++;
	
	// In our default config if this path is not writable then uploads should fail. Probably it would be better to distribute the package with 
	// the /www/tmp directory being an alias to the file system location of REASON_TEMP_DIR. Until this is done, we are leaving it like this. 
	// We may want to do something to check the validity of those aliases, such as writing a file then trying to access it via curl. The same 
	// thing could be done for assets.
	if (data_dir_writable($_SERVER[ 'DOCUMENT_ROOT' ].WEB_TEMP, 'WEB_TEMP')) $check_passed++;
	else $check_failed++;
	
	if (check_file_readable(APACHE_MIME_TYPES, 'APACHE_MIME_TYPES', 
							' Also make sure APACHE_MIME_TYPES constant in reason_settings is set to the full path of the mime.types file (include the filename).')) $check_passed++;
	else $check_failed++;
	
	// lets check the thor http file using curl
	$link = carl_construct_link(array(''), array(''), THOR_HTTP_PATH);
	$link_with_file = $link . 'getXML.php';
	if (strpos(get_reason_url_contents($link_with_file), 'tmp_id') !== false)
	{	
		msg('<span class="success">thor getXML.php file accessible over http</span> - passed', true);
		$check_passed++;
	}
	else
	{
		msg('<span class="error">could not access thor getXML.php over http</span><p>You may need to set THOR_HTTP_PATH equal to "/thor/", and create an alias at ' . WEB_PATH . 'thor/ to ' . THOR_INC.'. 
		     Future revisions to thor should make this more flexible, but for the moment you need the alias in your web root to the thor directory</p>', false);
		$check_failed++;
	}
	
	echo '<h3>Summary</h3>';
	echo '<ul>';
	echo '<li class="success">'.$check_passed.' checks were successful</li>';
	echo '<li class="error">'.$check_failed.' checks failed</li>';
	echo '</ul>';
	if ($check_failed == 0) return true;
	else return false;
}

function verify_mysql($db_conn_name, $constant_name, $constant_location, $check_for_tables = false) // see if we can connect to mysql using the connection parameters specified in REASON_DB
{
	include_once( INCLUDE_PATH . 'xml/xmlparser.php' ); // we have verified this exists already
	$db_file = DB_CREDENTIALS_FILEPATH; // we have verified this exists
	$xml = file_get_contents($db_file);
	if(!empty($xml))
	{
		$xml_parse = new XMLParser($xml);
		$xml_parse->Parse();
		foreach ($xml_parse->document->database as $database)
		{
			$tmp = array();
			$tmp['db'] = $database->db[0]->tagData;
			$tmp['user'] = $database->user[0]->tagData;
			$tmp['password'] = $database->password[0]->tagData;
			$tmp['host'] = $database->host[0]->tagData;
			$db_info_all[$database->connection_name[0]->tagData] = $tmp;
		}
	}
	else return msg('<span class="error">mysql connection ' . $db_conn_name . ' check failed</span> - the db connection xml file does not appear to have any contents', false);
	$db_info = (isset($db_info_all[$db_conn_name])) ? $db_info_all[$db_conn_name] : false;
	if ($db_info === false) return msg ('mysql check failed - ' . $db_conn_name . ' is an invalid connection name.
		<p>Make sure the constant ' . $constant_name . ' in ' . $constant_location . ' maps to the connection name in your db connection xml file</p>', false);
	
	if (empty($db_info['db']) || empty($db_info['user']) || empty($db_info['password']) || empty($db_info['host']))
	{
		return msg('<span class="error">mysql connection ' . $db_conn_name . ' check failed</span> - the db connection xml file for does not have full information for the connection named ' . $db_conn_name . '.
		<p>Check the constant ' . $constant_name . ' in ' . $constant_location . ' to make sure it matches the connection name in your db connection xml file.</p>', false);
	}
	$db = mysql_connect($db_info['host'], $db_info['user'], $db_info['password']);
	if (empty($db))
	{
		return msg('<span class="error">mysql connection ' . $db_conn_name . ' check failed</span> - count not connect to server - could be one of the following
					<ul>
					<li>Improper username and/or password in the db credentials file</li>
					<li>Improper mysql hostname - currently set to ' .$db_info['host'].'</li>
					<li>The user ' . $db_info['user'] . ' needs to have been granted permission to connect to ' . $db_info['host'] . ' from the web server</li>
					</ul>', false);
	}
	else
	{
		if( !mysql_select_db($db_info[ 'db' ], $db) )
		{
			return msg('<span class="error">mysql connection ' . $db_conn_name . ' check failed</span> - connected to host as user ' . $db_info['user'] . ' but could not select database ' . $db_info['db'] . '. Check the db credential xml file and user privileges', false);
		}
	}
	
	// check_for_tables
	if ($check_for_tables)
	{
		$result = db_query('show tables');
		$table_count = mysql_num_rows($result);
		if ($table_count == 0)
		{
			return msg('<span class="error">mysql connection ' . $db_conn_name . ' check failed</span> - 
				   The database ' . $db_info['db'] . ' does not appear to have any tables.<p><a href="./install.htm#database_setup">Consult the reason install documentation</a> 
				   for information on how to import the reason database.</p>', false);
		}
	}
	return msg('<span class="success">mysql connection ' . $constant_name . '('.$db_conn_name . ') check passed</span>', true);
}

function http_host_check()
{
	if ($_SERVER['HTTP_HOST'] == HTTP_HOST_NAME) return msg('<span class="success">http host check passed</span>', true);
	else return msg('<span class="error">http host check failed</span> - make sure the HTTP_HOST_NAME constant in paths.php is equivalent to the $_SERVER[\'HTTP_HOST\'] value', false);
}

function tidy_check()
{
	$html_string = '<html><body><h3>babababab</h3></body></html>';
	$string = tidy($html_string);
	if ($string == '') return msg('<span class="error">tidy check failed</span> - make sure the constant TIDY_EXE in paths.php is set to the location of the tidy executable', false);
	elseif (strpos($string, 'body') !== false) return msg('<span class="error">tidy check failed</span> - tidy is not properly stripping body tags - make sure that the tidy.conf file in your settings directory includes "show-body-only: yes"', false);
	else return msg('<span class="success">tidy check passed</span>', true);
}

function curl_check()
{
	$insecure_link = 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'?curl_test=true';
	$secure_link = 'https://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'?curl_test=true';
	$content = get_reason_url_contents( $insecure_link );
	if (empty($content)) return msg('<span class="error">curl check failed</span>', false);
	else 
	{
		// if HTTPS_AVAILABLE is true, lets hit the current page in that way
		if (securest_available_protocol() == 'https') 
		{
			$content = get_reason_url_contents($secure_link);
			if (empty($content)) return msg('<span class="error">curl check failed over https</span>.
											<p>Your server probably does not support https connections</p>
											<p>Set the HTTPS_AVAILABLE constant in package_settings.php to false and try again.</p>', false);
		}
		return msg('<span class="success">curl check passed</span>', true);
	}
}

function imagemagick_check()
{
	$mogrify_filename = (server_is_windows()) ? 'mogrify.exe' : 'mogrify';
	if (file_exists(IMAGEMAGICK_PATH.$mogrify_filename))
	{
		$cmd = IMAGEMAGICK_PATH . 'mogrify -version 2>&1';
		$output = shell_exec($cmd);
		
		// see if the string imagemagick exists in the output - if not it did not work properly
		if (strpos(strtolower($output), 'imagemagick') === false) return msg('<span class="error">imagemagick check failed</span> - mogrify exists but does not appear to function properly when invoked via php...your php install should not be running in safe mode and needs to be able to use exec and shell_exec functions', false);
		else return msg('<span class="success">imagemagick check passed</span>', true);
	}
	else return msg('<span class="error">imagemagick check failed</span> - ' .IMAGEMAGICK_PATH.'mogrify not found - check the IMAGEMAGICK_PATH constant in package_settings.php, and php permissions.', false);
}

function data_dir_writable($dir, $name)
{
	if (is_writable($dir)) return msg('<span class="success">'.$name . ' directory is writable</span> - check passed', true);
	else return msg ('<span class="error">'.$name . ' directory not writable - failed</span>. Make sure apache user has write access to ' . $dir, false);
}

function check_file_readable($file, $name, $extra = '')
{
	if (is_readable($file)) return msg('<span class="success">'.$name . ' file is readable</span> - check passed', true);
	else return msg ('<span class="error">'.$name . ' file not readable - failed</span>. Make sure ' .$file. ' exists and apache user has read access to it. '.$extra, false);
}

function check_environment($path, $check_name, $error_msg)
{
	if (file_exists($path)) return msg($check_name . ' found', true);
	else die_with_message('<p class="error">ERROR: '.$check_name . ' not found</p><p>'.$error_msg.'</p><p>Please fix the problem and run this script again.</p>');
}

function check_environment_and_trailing_slash($path, $check_name, $error_msg)
{
	if (file_exists($path))
	{
		// lets make sure the last character of the path is a trailing slash
		if (substr($path, -1) != '/') die_with_message('<p class="error">ERROR: '.$check_name . ' missing trailing slash.</p><p>'.$error_msg.'</p><p>Please fix the problem and run this script again.</p>');
		return msg($check_name . ' found', true);
	}
	else die_with_message('<p class="error">ERROR: '.$check_name . ' not found</p><p>'.$error_msg.'</p><p>Please fix the problem and run this script again.</p>');
}

function check_error_handler_log_file_dir()
{
	if (!file_exists(PHP_ERROR_LOG_FILE))
	{
		$success = false;
		// attempt to create the file.
		$file = PHP_ERROR_LOG_FILE;
		if ($file_handle = fopen($file,"a")) fclose($file_handle);
		else
		{
			die_with_message('<p class="error">The error handler log file is set to ' . PHP_ERROR_LOG_FILE . ' - this file does not exist, and
				   could not be created. Please create the file, and make sure the apache user can write to it. You can alternatively change the
				   PHP_ERROR_LOG_FILE constant in error_handler_settings.php to a writable directory. After you have fixed the problem
				   run this script again.</p>');
		}
	}
	if (!is_writable(PHP_ERROR_LOG_FILE))
	{
		die_with_message('<p class="error">The error handler log file is set to ' . PHP_ERROR_LOG_FILE . ' - this file is not writable.
				   Please make the file writable to the apache user or change the value of the constant PHP_ERROR_LOG_FILE in error_handler_settings.php
				   to a writable file. After you have fixed the problem run this file again.</p>');
	}
	return true;
}

function msg($msg, $bool)
{
	echo '...' . $msg;
	echo '<br />';
	return $bool;
}

function admin_user_HTML()
{
	echo '<h3>Create Admin User</h3>';
	echo '<p>Your Reason instance does not have an administrative user. In order to login and create users, we need to setup the administrative 
user. Press submit to create the user and a random password - MAKE SURE TO WRITE DOWN THE PASSWORD!. You can change the password later but will
need it to login</p>';
echo '<form method="post"><input type="submit" name="do_it_pass" value="Do It!" /></form>';
}

function create_pass()
{
	$pass = '';
	$chars = "1234567890abcdefghijklmnopqrstuvwxyz";
	while (strlen($pass) < 6)
	{
		$my_char = $chars{rand(0,35)};
		if (!is_numeric($my_char))
		{
			if (rand(0,1) == 0) $my_char = strtoupper($my_char);
		}
		$pass .= $my_char;
	}
	return $pass;
}

function die_with_message($msg)
{
	echo $msg;
	die;
}
?>

