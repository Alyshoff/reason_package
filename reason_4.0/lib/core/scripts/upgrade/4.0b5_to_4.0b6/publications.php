<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Publications changes for 4.0 beta 6</title>
</head>

<body>
<?php
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class pubUpdaterb5b6
{
	var $mode;
	var $reason_user_id;
	//type_to_default_view
	
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->update_default_issue_view();
	}

	function update_default_issue_view()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('view'));
		$es->add_right_relationship(id_of('issue_type'), relationship_id_of('type_to_default_view'));
		$es->set_num(1);
		$views = $es->run_one();
		if(!empty($views))
		{
			echo '<p>Default issue view already exists. No need to create a default issue view.</p>'."\n";
			return;
		}
		if($this->mode == 'run')
		{
			echo '<p>Creating default issue view...</p>'."\n";
			echo '<ul>'."\n";
			$id = reason_create_entity(id_of('master_admin'),id_of('view'),$this->reason_user_id, 'Default Issue View', array('display_name' => 'List', 'column_order' => 'id, name, datetime, show_hide, last_modified', 'default_sort' => 'datetime, desc'));
			if(empty($id))
			{
				echo '<li>Unable to create default issue view. This is not a critical update, but you may want to look into why this did not work. Aborting default issue creation.</li></ul>'."\n";
				return;
			}
			
			echo '<li>Created default issue view</li>'."\n";
			
			$success = create_relationship( $id, id_of('generic_lister'), relationship_id_of('view_to_view_type'));
			if($success)
				echo '<li>Associated default issue view with the List view type.</li>'."\n";
			else
				echo '<li>Did not successfully associate default issue view with the List view type.</li>'."\n";
			
			$show_hide_field_id = $this->get_field_id('show_hide', 'show_hide');
			if(!empty($show_hide_field_id))
			{
				$success = create_relationship( $id, $show_hide_field_id, relationship_id_of('view_columns'));
				if($success)
					echo '<li>Added the show_hide field to the view.</li>'."\n";
				else
					echo '<li>Did not successfully add the show_hide field to the view.</li>'."\n";
					
				$success = create_relationship( $id, $show_hide_field_id, relationship_id_of('view_searchable_fields'));
				if($success)
					echo '<li>Added the show_hide field to the searchable fields.</li>'."\n";
				else
					echo '<li>Did not successfully add the show_hide field to the searchable fields.</li>'."\n";
			}
			
			$datetime_field_id = $this->get_field_id('dated', 'datetime');
			if(!empty($datetime_field_id))
			{
				$success = create_relationship( $id, $datetime_field_id, relationship_id_of('view_columns'));
				if($success)
					echo '<li>Added the datetime field to the view.</li>'."\n";
				else
					echo '<li>Did not successfully add the datetime field to the view.</li>'."\n";
					
				$success = create_relationship( $id, $datetime_field_id, relationship_id_of('view_searchable_fields'));
				if($success)
					echo '<li>Added the datetime field to the searchable fields.</li>'."\n";
				else
					echo '<li>Did not successfully add the datetime field to the searchable fields.</li>'."\n";
			}
			
			$success = create_relationship( id_of('issue_type'), $id, relationship_id_of('type_to_default_view'));
			if($success)
				echo '<li>Set up new view as the default view for issues.</li>'."\n";
			else
				echo '<li>Did not successfully set up new view as the default view for issues.</li>'."\n";
			
			echo '</ul>'."\n";
		}
		else
		{
			echo '<p>Would have created the default issue view.</p>'."\n";
		}
	}
	
	function get_field_id($table_name, $field_name)
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.addslashes($table_name).'"');
		$es->set_num(1);
		$tables = $es->run_one();
		if(empty($tables))
		{
			trigger_error('Unable to find table named '.$table_name);
			return false;
		}
		$table = current($tables);
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('field'));
		$es->add_left_relationship($table->id(), relationship_id_of('field_to_entity_table'));
		$es->add_relation('entity.name = "'.addslashes($field_name).'"');
		$es->set_num(1);
		$fields = $es->run_one();
		if(empty($fields))
		{
			trigger_error('Unable to find table named '.$field_name,' in table named '.$table_name);
			return false;
		}
		$field = current($fields);
		return $field->id();
	}
}

force_secure_if_available();

$user_netID = reason_require_authentication();

$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

?>
<h2>Reason: run updates to publications for 4.0b6</h2>
<p>What will this update do?</p>
<ul>
<li>If you have not already set up a default view for issues, this script will create a default issue view that displays information about issue visibility and date</li>
</ul>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new pubUpdaterb5b6();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
</body>
</html>
