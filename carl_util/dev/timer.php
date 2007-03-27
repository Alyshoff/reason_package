<?php
/*
 * simple timer class
 * dave hendler
 * times events so you can check out the performance of your code
 */
	class Timer
	{
		// array of start times
		var $_start;
		// array of stop times
		var $_stop;
		var $_stack;
		var $_level;
		var $long_time = 10;

		// constructor
		function Timer()
		{
			$this->_start = array();
			$this->_stop = array();
			$this->_timers = array();
			$this->_level = 0;
		}
		// start a named timer
		function start( $timer )
		{
			$t = $this->_getmicrotime();
			$this->_start[ $timer ] = $t;
			$this->_timers[ $timer ] = array(
				'start' => $t,
				'level' => $this->_level,
				'end' => '',
			);
			$this->_level++;
		}
		// stop a named timer
		function stop( $timer )
		{
			$t = $this->_getmicrotime();
			$this->_stop[ $timer ] = $t;
			$this->_timers[ $timer ][ 'end' ] = $t;
			$this->_level--;
		}
		// get elapsed time for a named timer
		function elapsed( $timer )
		{
			return round(1000*($this->_stop[ $timer ] - $this->_start[ $timer ]),1);
		}
		function report_all()
		{
			echo '<table width="500" border="1" cellspacing="0" cellpadding="4">';
			foreach( $this->_start AS $timer => $start )
			{
				echo '<tr>';
				echo '<td align="left">';
				echo $timer;
				echo '</td>';
				echo '<td align="right">';
				$elapsed = $this->elapsed( $timer );
				if( $elapsed > 20 ) $elapsed = '<strong>'.$elapsed.'</strong>';
				echo $elapsed.' ms</td>';
				echo '</tr>';
			}
			echo '</table><br/>';
		}
		function report_stack()
		{
			echo '<table border="1" cellspacing="0" cellpadding="4">';
			foreach( $this->_timers AS $timer => $info )
			{
				$elapsed = $this->elapsed( $timer );
				$long = $elapsed > $this->long_time;
				echo '<tr>';
				echo '<td>';
				if( $long ) echo '<strong>';
				for( $i = 0; $i < $info['level']; $i++ )
					echo '---';
				echo $timer;
				if( $long ) echo '</strong>';
				echo '</td>';
				echo '<td align="right">';
				if( $long ) echo '<strong>';
				echo $this->elapsed( $timer );
				echo ' ms';
				if( $long ) echo '</strong>';
				echo '</td>';
				echo '</tr>';
			}
		}
		// put microtime() output into usable format
		function _getmicrotime()
		{
			list( $usec, $sec ) = explode( " ", microtime() );
			return ((float)$usec + (float)$sec);
		}
	}

?>
