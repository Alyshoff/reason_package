<html>
<head>
</head>
<body>
<center>
<?php
	include_once( 'reason_header.php' );
	connectDB( REASON_DB );
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	//   Total hits and misses, last 30 days
	//
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	include_once( CARL_UTIL_INC . 'charts/charts.php' );
	
	include_once( CARL_UTIL_INC . 'charts/chart_funcs.php' );
	
	reason_include_once( 'function_libraries/user_functions.php' );
	if(!on_secure_page())
	{ 
		force_secure();
	}
	$current_user = check_authentication();
	if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to view cache graphs.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
	}
	
	$chart = array(
		'table' => 'page_cache_log_archive',
		'date_field' => 'dt',
		'grouping' => 'month',
		'select' => array(
			'Total Views' => 'sum( if( action_type = "hit" OR action_type = "miss", 1, 0 ) )',
		),
	);
	
	
	$chart_options[ 'canvas_bg' ][ 'width' ] = 800;
	$chart[ 'select' ] = array(
		'Total Views' => 'sum( if( action_type IN ("miss","hit"), 1, 0 ) )',
	);
	
	unset( $chart_options[ 'canvas_bg' ][ 'width' ] );
	unset( $chart[ 'num_groupings' ] );
	$chart_options[ 'chart_type' ] = '';
	
	
	echo '<hr/>';
	echo '<br/><strong>Total Views, broken down by hits and misses</strong><br/>';
	
	
	$chart[ 'select' ] = array(
		'Cache Hits' => 'sum( if(action_type = "hit", 1, 0) )',
		'Cache Misses' => 'sum( if( action_type = "miss", 1, 0 ) )',
	);
	$chart[ 'grouping' ] = 'month';
	$chart_options[ 'chart_type' ] = 'stacked column';
	draw_date_chart( $chart, $chart_options );

	$chart[ 'grouping' ] = 'week';
	draw_date_chart( $chart, $chart_options );
	
	
	echo '<hr/>';
	echo '<br/><strong>Cache Effectiveness (Hit Ratio)</strong><br/>';
	
	$chart[ 'select' ] = array(
		'Hit Percent' => '100 * (sum( if(action_type="hit",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) )',
		// showing the miss percent just gives a visual confirmation that everything adds up to 100%
		//'Miss Percent' => '100 * ( sum( if(action_type="miss",1,0 ) ) / sum( if(action_type="hit" OR action_type="miss",1,0 ) ) )',
	);
	$chart_options[ 'chart_type' ] = 'column';
	$chart[ 'grouping' ] = 'month';
	draw_date_chart( $chart, $chart_options );
	$chart[ 'grouping' ] = 'week';
	draw_date_chart( $chart, $chart_options );
	
	echo '<hr/>';
	echo '<br/><strong>Page Generation Time</strong><br/>';
	$chart[ 'select' ] = array(
		'Page Gen Time' => 'sum( if(action_type="store" AND page_gen_time IS NOT NULL, page_gen_time, 0 ) ) / sum( if( action_type="store" AND page_gen_time IS NOT NULL, 1, 0) )',
	);
	unset( $chart[ 'num_groupings' ] );
	$chart[ 'grouping' ] = 'month';
	draw_date_chart( $chart, $chart_options );
	$chart[ 'grouping' ] = 'week';
	draw_date_chart( $chart, $chart_options );
	
	
	
	
	
	
	
	
	
	
	
	
?>
</center>
</body>
</html>
