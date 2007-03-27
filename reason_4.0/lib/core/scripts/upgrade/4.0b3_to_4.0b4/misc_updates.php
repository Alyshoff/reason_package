<?php

include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/user.php');
reason_include_once('classes/theme.php');
reason_include_once('function_libraries/file_finders.php');

// try to increase limits in case user chooses a really big chunk
set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade: Miscellaneous 4.0b3 to 4.0b4 Updates</title>
</head>

<body>
<?php

force_secure();

$GLOBALS['__cur_username__hack__'] = reason_require_authentication();
$usr = new user();
$user = $usr->get_user($GLOBALS['__cur_username__hack__']);
if($user)
{
	$GLOBALS['__cur_user_id_hack__'] = $user->id();
}
else
{
	echo 'You must be a valid reason user to run this script';
	die();
}

echo '<h2>Reason: Miscellaneous 4.0b3 to 4.0b4 Updates</h2>';
if ( !isset ($_POST['verify']))
{
        echo '<p>This script does a variety of minor updates to your Reason instance, including:</p>';
		echo '<ul>';
		echo '<li>Setting up a new content manager for themes</li>';
		echo '<li>Adding new themes:';
		echo '<ul>';
		foreach(get_themes_to_add_b3_to_b4() as $theme_uname=>$theme_info)
		{
			if(!empty($theme_info['name']))
				$name = $theme_info['name'];
			else
				$name = prettify_string($theme_uname);
			echo '<li>'.$name.'</li>';
		}
		echo '</ul>';
		echo '</li>';
		echo '<li>Changes the event.last_occurence field to be a date field rather than a text field</li>';
		echo '<li>Adds indexes to the following fields:';
		echo '<ul>';
		foreach(get_indexes_to_add_b3_to_b4() as $table=>$fields)
		{
			foreach($fields as $field)
			{
				echo '<li>'.$table.'.'.$field.'</li>';
			}
		}
		echo '</ul>';
		echo '</li>';
		echo '</ul>';
		echo_form();
}

if (isset ($_POST['verify']))
{
	$test_mode = true;
	if(!empty($_POST['run']) && $_POST['run'] == 'Run')
		$test_mode = false;
	run_updates($test_mode);
}

function echo_form()
{
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="run" value="Run" />';
	echo '<input type="submit" name="test" value="Test" />';
	echo '<input type="hidden" name="verify" value="true" />';
	echo '</form>';
}

function run_updates($test_mode = true)
{
	$updates = array('update_theme_content_manager','add_new_themes','change_event_last_occurence_to_date','add_indexes_b3_to_b4');
	if($test_mode)
	{
		echo '<h2>Testing</h2>';
	}
	foreach($updates as $update)
	{
		$update($test_mode);
	}
}

function get_themes_to_add_b3_to_b4()
{
	return array(
				'simplicity_tan_theme'=>array(
					'name'=>'Simplicity Tan',
					'css'=>array('Simplicity Tan'=>array('url'=>'css/simplicity/tan.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'default'
				),
				'simplicity_grey_theme'=>array(
					'name'=>'Simplicity Grey',
					'css'=>array('Simplicity Grey'=>array('url'=>'css/simplicity/grey.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'default'
				),
				'black_box_theme'=>array(
					'name'=>'Black Box',
					'css'=>array('Black Box'=>array('url'=>'css/themes/black_box/black_box.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'pedagogue_plum_theme'=>array(
					'name'=>'Pedagogue Plum',
					'css'=>array('Pedagogue Plum'=>array('url'=>'css/themes/pedagogue/plum.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_hematite_theme'=>array(
					'name'=>'Gemstone Hematite',
					'css'=>array('Gemstone Base'=>array('url'=>'css/themes/gemstone/gemstone.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_ruby_theme'=>array(
					'name'=>'Gemstone Ruby',
					'css'=>array('Gemstone Ruby'=>array('url'=>'css/themes/gemstone/ruby/ruby.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_emerald_theme'=>array(
					'name'=>'Gemstone Emerald',
					'css'=>array('Gemstone Emerald'=>array('url'=>'css/themes/gemstone/emerald/emerald.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables',
					'image'=>'css/themes/gemstone/emerald/example.png', // this is not yet working, but the idea is that we would import the example image at the same time as we import the theme
				),
				'starbaby_theme'=>array(
					'name'=>'Starbaby',
					'css'=>array('Starbaby'=>array('url'=>'css/themes/starbaby/starbaby.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables',
				),
	);
}

function update_theme_content_manager($test_mode = true)
{
	echo '<h3>Theme Content Manager Update</h3>';
	$theme_type = new entity(id_of('theme_type'));
	if($theme_type->get_values())
	{
		if(!$theme_type->get_value('custom_content_handler'))
		{
			if($test_mode)
			{
				echo '<p>Would have updated the theme type to use the new content manager</p>';
			}
			else
			{
				if(reason_update_entity( id_of('theme_type'), $GLOBALS['__cur_user_id_hack__'], array('custom_content_handler'=>'theme.php')))
				{
					echo '<p>Updated the theme type to use the new content manager</p>';
				}
				else
				{
					echo '<p>Some sort of problem has occurred with updating the theme type to use the new content manager</p>';
				}
			}
		}
		else
		{
			echo '<p>Theme type appears to be using the file '.$theme_type->get_value('custom_content_handler').' as a content manager. No database changes are needed.</p>';
		}
	}
	else
	{
		echo '<p>Theme type not found; unable to update</p>';
	}
}

function add_new_themes($test_mode = true)
{
	echo '<h3>Adding new themes</h3>';
	$themes_to_add = get_themes_to_add_b3_to_b4();
	//pray($themes_to_add);
	foreach($themes_to_add as $unique_name=>$theme_info)
	{
		$rt = new reasonTheme();
		$rt->set_test_mode($test_mode);
		$css = array();
		if(!empty($theme_info['css']))
			$css = $theme_info['css'];
		$results = $rt->add_complete($unique_name,$theme_info['name'],$css,$theme_info['template'],$GLOBALS['__cur_user_id_hack__']);
		echo $results['report'];
	}
}

function change_event_last_occurence_to_date($test_mode = true)
{
	echo '<h3>Changing event.last_occurence to DATE</h3>';
	$handle = db_query('DESC `event` `last_occurence`');
	$results = array();
	while($row = mysql_fetch_assoc($handle))
	{
		$results = $row;
	}
	if(strtolower($results['Type']) == 'date')
	{
		echo '<p>event.last_occurence is already set to be a date field. No db changes are necessary.</p>';
	}
	else
	{
		if($test_mode)
		{
			echo '<p>Would have updated event.last_occurence to be a true date field</p>';
		}
		else
		{
			if(db_query('ALTER TABLE `event` CHANGE `last_occurence` `last_occurence` DATE NULL DEFAULT NULL'))
			{
				echo '<p>Successfully updated event.last_occurence to be a date field</p>';
			}
			else
			{
				echo '<p>Failed to update event.last_occurence to be a date field. You might try to manually update the column definition for event.last_occurence from "tinytext" to "date."</p>';
			}
		}
	}
}

function get_indexes_to_add_b3_to_b4()
{
	return array('dated'=>array('datetime'),'event'=>array('last_occurence'));
}

function add_indexes_b3_to_b4($test_mode = true)
{
	echo '<h3>Adding indexes</h3>';
	echo '<ul>';
	foreach(get_indexes_to_add_b3_to_b4() as $table=>$fields)
	{
		$handle = db_query('SHOW INDEX FROM `'.addslashes($table).'`');
		$results = array();
		while($row = mysql_fetch_assoc($handle))
		{
			$results[] = $row['Column_name'];
		}
		foreach($fields as $field)
		{
			if(in_array($field, $results))
			{
				echo '<li>'.$table.'.'.$field.' is already indexed. No need to do anything.</li>';
			}
			else
			{
				if($test_mode)
				{
					echo '<li>Would have added index on '.$table.'.'.$field.'.</li>';
				}
				else
				{
					if(db_query('ALTER TABLE `'.addslashes($table).'` ADD INDEX ( `'.addslashes($field).'` )'))
					{
						echo '<li>Successfully added index on '.$table.'.'.$field.'.</li>';
					}
					else
					{
						echo '<li>Attempted to add index on '.$table.'.'.$field.', but failed.</li>';
					}
				}
			}
		}
	}
	echo '</ul>';
}

?>
</body>
</html>
