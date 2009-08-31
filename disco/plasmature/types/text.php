<?php

/**
 * Text input type library.
 *
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * Single-line text input.
 * @package disco
 * @subpackage plasmature
 */
class textType extends defaultType
{
	var $type = 'text';
	/**
	 * The size (in characters) of the input element.
	 * @var int
	 */
	
	var $size = 50;
	
	/**
	 * The maximum length (in characters) of the input value.
	 * Note that this check only occurs on the client side.
	 * @var int
	 */
	var $maxlength = 256;
	
	/** @access private */
	var $type_valid_args = array( 'size', 'maxlength' );
	
	function get_display()
	{
		return '<input type="text" name="'.$this->name.'" value="'.str_replace('"', '&quot;', $this->get()).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" />';
	}
}

/**
 * Prints out value of item without allowing you to edit it.
 * Changing of this value in userland is now deprecated; in future releases it will not be possible.
 * @todo remove userland get behavior before 4.0rc1
 * @package disco
 * @subpackage plasmature
 */
class solidtextType extends defaultType
{
	var $type = 'solidtext';
	
	function grab()
	{
		$value = $this->grab_value();
		if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
		{
			trigger_error('solidText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
		}
		parent::grab();
	}
	
	function get_display()
	{
		$str  = '<input type="hidden" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'"/>';
		$str .= "\n".'<div class="solidText">' . $this->get(). '</div>';
		return $str;
	}
}

/**
 * Plain Text -- similar to solidText -- shows text value but no form element
 * @todo remove userland get behavior before 4.0rc1
 * @package disco
 * @subpackage plasmature
 */
class plainTextType extends defaultType
{
	var $type = 'plainText';
	function grab()
	{
		$value = $this->grab_value();
		if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
		{
			trigger_error('plainText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
		}
		parent::grab();
	}
	function get_display()
	{
		$str  = '<input type="hidden" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" />'.$this->get();
		return $str;
	}
}

/**
 * Disabled Text -- shows a disabled form element
 * Changing of this value in userland is now deprecated; in future releases it will not be possible.
 * @todo remove userland get behavior before 4.0rc1
 * @package disco
 * @subpackage plasmature
 */
class disabledTextType extends textType
{
 	var $type = 'disabledText';
	function grab()
	{
		$value = $this->grab_value();
		if($value !== NULL && $value != $this->get() && preg_replace('/\s+/','',$value) != preg_replace('/\s+/','',$this->get()))
		{
			trigger_error('disabledText element ('.$this->name.') value changed in userland. This is deprecated (insecure) behavior and will not be allowed in future releases.');
		}
		parent::grab();
	}
	function get_display()
	{
		$str  = '<input type="text" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" disabled="disabled" />';
		return $str;
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class passwordType extends defaultType
{
	var $type = 'password';
	var $size = 20;
	var $maxlength = 256;
	var $type_valid_args = array( 'size', 'maxlength' );
	function get_display()
	{
		return '<input type="password" name="'.$this->name.'" value="'.htmlspecialchars($this->get(),ENT_QUOTES).'" size="'.$this->size.'" maxlength="'.$this->maxlength.'" />';
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class moneyType extends textType
{
	var $type = 'money';
	var $currency_symbol = '$';
	var $type_valid_args = array( 'currency_symbol' );
	function get_display()
	{
		$field = parent::get_display();
		return $this->currency_symbol.' '.$field;
	}
	function grab()
	{
		parent::grab();
		$this->value = str_replace( ',','',$this->value );
		if( !empty($this->value) && !is_numeric( $this->value ) )
			$this->set_error( 'Please express monetary amounts in numbers. Use a period (.) to indicate the decimal place.' );
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class textareaType extends defaultType
{
	var $type = 'textarea';
	var $rows = 8;
	var $cols = 80;
	var $type_valid_args = array('rows', 'cols');
	function get_display()
	{
		$str  = '<textarea name="'.$this->name.'" rows="'.$this->rows.'" cols="'.$this->cols.'">'.htmlspecialchars($this->get(),ENT_QUOTES,'UTF-8').'</textarea>';
		return $str;
	}
	function grab()
	{
		parent::grab();
		$length = strlen( $this->value );
		$length_limits = array('tinytext' => 255, 'text' => 65535,
			'mediumtext' => 16777215);
		
		if(!empty($this->db_type) && array_key_exists($this->db_type, $length_limits))
		{
			if($length  > $length_limits[$this->db_type])
			{
				$name_to_display = trim($this->display_name);
				if(empty($name_to_display))
					$name_to_display = prettify_string($this->name);
				$this->set_error( 'There is more text in '.$name_to_display.' than can be stored; this field can hold no more than '.$length_limits[$this->db_type].' characters.' );
			}
		}
	}
}
