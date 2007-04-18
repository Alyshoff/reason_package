<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to delete widowed relationships.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
	}
	?>
	<html>
	<head>
	<title>Reason: Delete Widowed Relationships</title>
	</head>
	<body>
	<h1>Delete Widowed Relationships</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>If one or both of the entities of a relationship have been expunged from the Reason database -- but the relationship still exists -- then the relationship is "widowed."</p>
	<p>This script deletes any widowed relationships in Reason. It should probably should be run regularly. In fact, it should probably be made into a cron job at some point.</p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{
		connectDB( REASON_DB );
		$results = array();
		$sides = array('a','b');
		foreach( $sides as $side )
		{
			$q = 'SELECT r.id FROM relationship AS r LEFT JOIN entity AS e ON r.entity_'.$side.' = e.id WHERE e.id IS NULL';
			$r = db_query( $q, 'Unable to grab widowed relationships.' );
			while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			{
				if(empty($results[$side]))
				{
					$results[$side] = array();
				}
				$results[$side][] = $row['id'];
				delete_relationship( $row['id'] );
			}
		}
		if(!empty($results))
		{
			echo '<h2>Widowed Relationships Deleted</h2>';
			foreach($results as $side => $ids)
			{
				echo '<h3>Side '.$side.': '.count($ids).' relationships deleted</h3>';
				echo '<ul><li>'.implode('</li><li>',$ids).'</li></ul>';
			}
		}
		else
		{
			echo '<h2>Congratulations</h2>';
			echo '<p>There are no widowed relationships in Reason</p>';
		}
	}
?>
