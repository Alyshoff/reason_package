<?
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');

force_secure();

$user_netID = check_authentication();

echo '<h2>Reason Publication Setup</h2>';
if (!isset ($_POST['verify']))
{
        echo '<p>This script creates .</p>';
        echo '<h2>Changes database as follows:</h2>';
        echo '<h3>New fields:</h3>';
        echo '<ul>';
        echo "<li>user_popup_alert_pref in entity table users - enum('yes', 'no')</li>";
        echo '</ul>';
}

if (isset ($_POST['verify']) && ($_POST['verify'] == 'Run'))
{	
	$entity_table_name = 'user';
	$fields = array('user_popup_alert_pref' => array('db_type' => "enum('yes','no')"));
	$updater = new FieldToEntityTable($entity_table_name, $fields);
	$updater->update_entity_table();
	$updater->report();
	
}

else
{
	echo_form();
}

function echo_form()
{
	echo '<form name="doit" method="post" src="'.get_current_url().'" />';
	echo '<p><input type="submit" name="verify" value="Run" /></p>';
	echo '</form>';
}

?>
