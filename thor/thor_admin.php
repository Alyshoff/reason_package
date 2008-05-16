<?

/**
 * Thor Admin
 *
 * @package thor
 * @author nathan white
 *
 * Provides a view of thor form data with filtering, and view/edit/delete row action support
 *
 */
include_once ( 'paths.php' );
include_once ( CARL_UTIL_INC . 'db/table_admin.php' );

class ThorAdmin extends TableAdmin
{
	/**
	 * @var array extra fields to display that are not actually thor elements described in xml
	 * @todo we should grab this from the ThorCore probably
	 */
	var $extra_fields = array('id', 'submitted_by', 'submitter_ip', 'date_created');
	
	function ThorAdmin()
	{
	}

	function init_thor_admin($xml, $table_name)
	{
		$this->set_xml($xml);
		$this->set_db_conn(THOR_FORM_DB_CONN);
		$this->set_table_name($table_name);
		$this->set_filename_frag($table_name);
		
		// grab information from the table and setup display value array
		if ($this->_check_table_exists())
		{
			$this->thor_build_display_values();
			
			$this->thor_pass_display_names_to_admin_form();
			
			// grab the request and set parameters accordingly
			$this->_set_params_from_request();
		
			// call appropriate init
			if (isset($this->table_action)) $this->init_action();
			elseif (isset($this->table_row_action) && isset($this->table_action_id) && $this->verify_table_action_id()) $this->init_row_action();
			else $this->init_default();
		}
	}
	
	function set_xml($xml)
	{
		$this->_xml = $xml;
	}
	
	function get_xml()
	{
		return $this->_xml;
	}
	
	function thor_build_display_values()
	{
		$xml = $this->get_xml();
		$form_xml_obj = new XMLParser($xml);
		$form_xml_obj->Parse();
		$display_values = array();
		foreach ($form_xml_obj->document->tagChildren as $k=>$v)
		{
			$tagname = is_object($v) ? $v->tagName : '';
			if (method_exists($this, '_build_display_'.$tagname))
			{
				$build_function = '_build_display_'.$tagname;
				$display_values = array_merge($display_values, $this->$build_function($v));
			}
		}
		foreach ($this->extra_fields as $field_name)
		{
			$display_values[$field_name]['label'] = prettify_string($field_name);
			$display_values[$field_name]['type'] = 'text';
		}
		$this->_display_values = (isset($display_values)) ? $display_values : array();
	}
	
	function thor_pass_display_names_to_admin_form()
	{
		$form =& $this->get_admin_form();
		if (!$form) $form = new DiscoDefaultAdmin();
		foreach ($this->_display_values as $k=>$v)
		{
			$field_display_names[$k] = $v['label'];
		}
		$form->field_display_names = $field_display_names;
	}
	
	function _delete_data()
	{
		// connect with table database defined in settings.php3
		connectDB($this->get_db_conn());
		$q = 'DROP TABLE ' . $this->get_table_name();
		$res = mysql_query( $q ) or mysql_error();//or trigger_error( 'Error: mysql error in Thor Data delete - URL ' . get_current_url() . ': '.mysql_error() );
  		connectDB($this->get_orig_db_conn());
		return $res;
	}
	
	/**
	 * Helper functions for _build_display_values()
	 * @todo these should be in thor core I think
	 * @access private
	 */
	 
	function _build_display_input($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'input';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_hidden($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'hidden';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}
 
	function _build_display_textarea($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$type = 'textarea';
		$display_values[$element_attrs['id']] = array('label' => $element_attrs['label'], 'type' => $type);
		return $display_values;
	}

	function _build_display_checkboxgroup($element_obj)
	{
		$element_children = $element_obj->tagChildren;
		$type = 'checkbox';
		foreach ($element_children as $element_child) 
		{
			$child_attrs = $element_child->tagAttrs;
			$label = $child_attrs['label'];
			$display_values[$child_attrs['id']] = array('label' => $label, 'type' => $type);
		}
		return $display_values;
	}
	
	function _build_display_radiogroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'radiogroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}

	function _build_display_optiongroup($element_obj)
	{
		$element_attrs = $element_obj->tagAttrs;
		$element_children = $element_obj->tagChildren;
		$id = $element_attrs['id'];
		$label = $element_attrs['label'];
		$type = 'optiongroup';
		foreach ($element_children as $element_child)
		{
			$child_attrs =& $element_child->tagAttrs;
			$options[] = $child_attrs['value'];
		}
		$display_values[$id] = array('label' => $label, 'type' => $type, 'options' => $options);
		return $display_values;
	}
}

class DiscoThorAdmin extends DiscoDefaultAdmin
{
	function DiscoThorAdmin()
	{
	}
	
	//function authorize()
	//{
	//	return true;
	//}
	
	//function set_form_id($id)
	//{
	//	$this->form_id = $id;
	//}
	
	//function get_form_id()
	//{
	//	return $this->form_id;
	//}
	
	function pre_show_form_edit()
	{
		$id = $this->get_id();
		echo '<h3>Editing row id ' . $id . '</h3>';
		$link = carl_make_link(array('table_row_action' => '', 'table_action_id' => ''));
		echo '<p><a href="'.$link.'">Return to summary form data</a></p>';
	}
	
	function pre_show_form_delete()
	{
		$id = $this->get_id();
		echo '<h3>Are you sure you want to delete row id ' . $id . '?</h3>';
	}
	
	function pre_show_form_view()
	{
		$id = $this->get_id();
		echo '<h3>Viewing row id ' . $id . '</h3>';
		$link = carl_make_link(array('table_row_action' => '', 'table_action_id' => ''));
		echo '<p><a href="'.$link.'">Return to summary form data</a></p>';
	}
	
	function on_every_time()
	{
		$this->remove_element('id');
		$this->setup_display_names();
		parent::on_every_time();
	}
	
	function on_every_time_delete()
	{
		$this->actions = array('delete' => 'Confirm Delete', 'cancel' => 'Cancel');
		$element_names = $this->get_element_names();
		foreach ($element_names as $element)
		{
			$this->change_element_type($element, 'solidtext');
		}
	}
	
	function setup_display_names()
	{
		$element_names = $this->get_element_names();
		foreach ($element_names as $element)
		{
			if (isset($this->field_display_names[$element]));
			$this->set_display_name($element, $this->field_display_names[$element]);
		}
	}
	
	function process_delete()
	{
		if ($this->chosen_action == 'delete')
		{
			$id_to_delete = $this->get_id();
			$qry = 'DELETE FROM ' . $this->get_table_name() . ' WHERE '.$this->id_column_name.' = '.$id_to_delete;
			$result = db_query($qry, 'The delete query failed');
			
			$qry = 'SELECT COUNT(*) as totalfound FROM ' . $this->get_table_name();
			$result = db_query($qry, 'The count query failed');
			$result = mysql_fetch_assoc($result);
			if ($result['totalfound'] == 0)
			{
				$q = 'DROP TABLE ' . $this->get_table_name();
				$res = mysql_query( $q ) or mysql_error();//or trigger_error( 'Error: mysql error in Thor Data delete - URL ' . get_current_url() . ': '.mysql_error() );
			}
		}
	}
	
	function where_to()
	{
		$link = carl_make_redirect(array('table_row_action'=>'', 'table_action_id'=>''));
		return $link;
	}
}
?>
