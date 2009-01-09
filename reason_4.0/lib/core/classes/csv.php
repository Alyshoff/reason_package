<?
/**
 * Simple CSV Class
 * @package reason
 * @subpackage classes
 */

/**
 * Simple CSV Class
 * @author nwhite
 *
 * This class is a basic CSV class which can read (and minimally write to) a .csv file stored in the filesystem
 *
 * Handles:
 *	- providing an array representation of a csv file
 *	- creates CSV file as needed
 *	- appends array of values to csv file with proper separator
 *  	- appends string to end of csv file
 */
class CSV {

	/**
	* File system pathname to existing (or desired) cvs file
	* @var string
	*/
	var $file_path;
	
	/**
	* Whether or not the cvs file has a header line
	* @var boolean
	*/
	var $head; // should there be a header row?

	/**
	* Delimiter used to separate values
	* @var string
	*/	
	var $delim; // delimiter character
	
	/**
	* Enclose character to surround fields with spaces
	* @var string
	*/	
	var $enclos; // enclosure character

	/**
	* Maximum line length to consider per line
	* @var int
	*/
	var $len; // max line length
	var $readable = false;
	var $writable = false;
	var $exists = false;
	
	function CSV($file_path, $head = false, $delim = ',', $len = 1000, $enclos = '"')
	{
		$this->file_path = $file_path;
		$this->head = $head;
		$this->delim = $delim;
		$this->enclos = $enclos;
		$this->len = $len;
		$this->exists = file_exists($file_path);
		$this->readable = is_readable($file_path);
		$this->writable = is_writable($file_path);
	}

	/**
 	 * Partially based on an example by ramdac at ramdac dot org
 	 * Returns a multi-dimensional array from a CSV file optionally using the 
 	 * first row as a header to create the underlying data as associative arrays.
 	 * 
 	 * @return array from cvs data.
     */	
	function csv_to_array()
	{
		$return = array();
		if ($this->exists && $this->readable)
		{
			$handle = fopen($this->file_path, "r");
			if ($this->head) 
			{
				$header = fgetcsv($handle, $this->len, $this->delim, $this->enclos);
			}
			while (($data = fgetcsv($handle, $this->len, $this->delim, $this->enclos)) !== FALSE)
			{
				if ($this->head AND isset($header))
				{
					foreach ($header as $key=>$heading)
					{
						$row[$heading]=(isset($data[$key])) ? $data[$key] : '';
					}
					$return[]=$row;
				} 
				else 
				{
					$return[]=$data;
    			}
   			}
   			fclose($handle);
   		}
   		return $return;
	}
	
	/**
	 * Writes the contents of a multidimensional array (like that created by csv_to_array)
	 * out to a CSV file, overwriting the existing contents (if any).
	 * Uses locking to ensure only one client is writing to the file at one time
	 *
	 * @param string line to add to csv file
	 * @return boolean success / failure to append a line
	 *
	 * TODO: Modify to use a temp file and swap into place on success
	 */ 
	function array_to_csv($arr)
	{
		$success = true;
		if (($handle = fopen($this->file_path, 'w')) && (flock($handle, LOCK_EX)))
		{
			if ($this->head)
			{
				$headings = array_keys(reset($arr));
				$result = fputcsv($handle, $headings, $this->delim, $this->enclos);
				$success = $success && $result;
			}
			foreach ($arr as $row)
			{
				$result = fputcsv($handle, $row, $this->delim, $this->enclos);
				$success = $success && $result;
			}
			flock($handle, LOCK_UN);
			fclose($handle);
		} else {
			$success = false;
		}
		return $success;
	}

	/**
 	 * Passes an array of values into appendLine with proper separators
 	 * 
 	 * @param values array of values to be added to a new csv line
 	 * @return boolean success / failure of appendLine
     */	
	function appendRow($values)
	{
		$line = '';
		$separator = '';
		foreach ($values as $v)
		{
        	$pos = strpos($v, ',');
        	if ($pos === false) $line .= $separator . $v;
        	else $line .= $separator. '"' . $v . '"';
        	if ($separator == '') $separator = $this->delim;
		}
		$line .= "\n";
		return $this->appendLine($line);
	}
	
	/**
	 * Adds a line to a new or existant csv file
	 * Uses locking to ensure only one client is writing to the file at one time
	 *
	 * @param string line to add to csv file
	 * @return boolean success / failure to append a line
	 */ 
	function appendLine($line)
	{
		$complete = false;
		if (($handle = fopen($this->file_path, 'a')) && (flock($handle, LOCK_EX)))
		{
			if (fwrite($handle, $line))
			{
				$complete = true;
			}
			flock($handle, LOCK_UN);
		}
		return $complete;
	}
	
	/**
	 * Outputs an HTML table from csv data
	 * If the csv file has a header, we use that for thead data
	 *
	 * @return string HTML table
	 */
	function csv_to_table($border = '0')
	{
		$csv_array = $this->csv_to_array();
		$html = '<table border="'.$border.'">' . "\n";
		if ($this->head)
		{
			$html .= "\t".'<thead>' . "\n";
			$html .= "\t\t".'<tr>' . "\n";
			$head_array = array_keys($csv_array[0]);
			foreach ($head_array as $head_item)
			{
				$html.= "\t\t\t".'<th>'.htmlentities($head_item).'</th>'."\n";
			}
			$html .= "\t\t".'</tr>' . "\n";
			$html .= "\t".'</thead>' . "\n";
		}
		$html .= "\t".'<tbody>'."\n";
		foreach ($csv_array as $item_array)
		{
			$html .= "\t\t".'<tr>'."\n";
			foreach ($item_array as $item)
			{
				$html .= "\t\t\t".'<td>'.htmlentities($item).'</td>'."\n";
			}
			$html .= "\t\t".'</tr>'."\n";
		}
		$html .= "\t".'</tbody>'."\n";
		$html .= '</table>' . "\n";
		return $html;
	}
}

if (!function_exists('fputcsv')) {
	/**
	 * PHP4 replacement for PHP5 fputcsv function; from nate at example dot com on php.net
	 *
	 * @param $fh resource handle to opened file
	 * @param $fields array values to write to file
	 * @param $delimiter string character to use as field delimiter
	 * @param $enclosure string character to enclose fields
	 * @param $mysql_null boolean flag to change PHP nulls to MySQL nulls
	 * @return boolean success / failure to write a line
	 */ 

	function fputcsv ($fh, $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false) {
	    $delimiter_esc = preg_quote($delimiter, '/');
	    $enclosure_esc = preg_quote($enclosure, '/');
	
	    $output = array();
	    foreach ($fields as $field) {
		if ($field === null && $mysql_null) {
		    $output[] = 'NULL';
		    continue;
		}
	
		$output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
		    $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
		) : $field;
	    }
	
	    return fwrite($fh, join($delimiter, $output) . "\n");
	} 
}
?>
