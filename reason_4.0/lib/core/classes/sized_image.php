<?php
/**
 * sized_image.php
 *
 * This file contains the reasonSizedImage class, which creates sized image files (if needed) and returns a filename string for them.
 *
 * @package reason
 * @subpackage classes
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'basic/image_funcs.php');
reason_include_once('function_libraries/image_tools.php');

/**
 * lets define our image directory (must exists and be web accessible and readable / writable by apache)
 */
if (!defined("REASON_SIZED_IMAGE_DIR"))
{
	define("REASON_SIZED_IMAGE_DIR", '/' . trim_slashes(WEB_PATH) . '/' . trim_slashes(WEB_TEMP) . '/');
}

/**
 * lets define our image directory (must exists and be web accessible and readable / writable by apache)
 */
if (!defined("REASON_SIZED_IMAGE_DIR_WEB_PATH"))
{
	define("REASON_SIZED_IMAGE_DIR_WEB_PATH", '/' . trim_slashes(WEB_TEMP) . '/');
}

/**
 * reasonSizedImage create sized image files (if needed) and returns a filename string for them.
 *
 * Typical use case:
 *
 * $rsi = new reasonSizedImage();
 * $rsi->set_id(23423);
 * $rsi->set_width(400);
 * $image_url = $rsi->get_url();
 *
 * @todo integrate REASON_SIZED_IMAGE_DIR and REASON_SIZED_IMAGE_DIR_WEB_PATH into reason_settings.php - use a better spot than loose in WEB_PATH/WEB_TEMP
 * @todo modify get_dimensions to set height and or width dynamically based on aspect ratio of the image
 * @todo implement resizing behavior appropriate to crop style
 * @todo what should we do if we want to resize to a larger size than the original
 *
 * @author Nathan White
 */
class reasonSizedImage
{
	/**
	 * @var int reason image id
	 */
	var $id;
	
	/**
	 * @var int width in pixels
	 */
	var $width;
	
	/**
	 * @var int height in pixels
	 */
	var $height;
	
	/**
	 * @var string "fill" or "fit" currently supported
	 */
	var $crop_style = "fill"; // fill or fit
	
	/**
	 * File system path to the directory where images are stored - apache needs write and read access to this directory, and it should probably be web accessible.
	 *
	 * You should include a trailing slash on the $image_dir - ie /tmp/
	 *
	 * @var string 
	 */
	var $image_dir = REASON_SIZED_IMAGE_DIR;
	
	/**
	 * Path from the server root directory to the image - used to construct URLs
	 *
	 * Usually dynamically determined by matching the WEB_PATH with the image_dir - if your setup is different specify this manually.
	 *
	 * @var string
	 */
	var $image_dir_web_path = REASON_SIZED_IMAGE_DIR_WEB_PATH;	
	
	/** public methods **/
	
	/**
	 * @access public
	 * @return boolean
	 */
	function exists()
	{
		$path = $this->_get_path();
		return file_exists($path);
	}
	
	/**
	 * @access public
	 * @return string
	 */
	function get_url()
	{
		if (!$this->exists()) $this->_make();
		return $this->_get_url();
	}
	
	/** private methods **/
	
	/** 
	 * @access private
	 */
	function _get_url()
	{
		if (!isset($this->_url))
		{
			$entity = $this->_get_entity();
			$filename = $this->_get_filename();
			$this->_url = $this->get_image_dir_web_path() . $entity->id() . '/' . $filename . '.' . $entity->get_value('image_type');	
		}
		return $this->_url;
	}

	/** 
	 * @access private
	 */
	function _get_path()
	{
		if (!isset($this->_path))
		{
			$entity = $this->_get_entity();
			$filename = $this->_get_filename();
			$this->_path = $this->get_image_dir() . $entity->id() . '/' . $filename . '.' . $entity->get_value('image_type');
		}
		return $this->_path;
	}
	
	function _get_filename()
	{
		if (!isset($this->_filename))
		{
			$last_modified = $this->_get_last_modified();
			$dimensions = $this->_get_dimensions();
			$crop_style = $this->get_crop_style();
			if ($last_modified && $dimensions && $crop_style) // if we have all the ingredients
			{
				$this->_filename = md5($last_modified.$dimensions.$crop_style);
			}
			else $this->_filename = false;
		}
		return $this->_filename;
	}
	
	function _get_last_modified()
	{
		$entity = $this->_get_entity();
		return $entity->get_value('last_modified');
	}
	
	function _get_entity()
	{
		if (!isset($this->_entity))
		{
			$id = $this->get_id();
			$image = new entity($id);
			if (!reason_is_entity($image, 'image'))
			{
				trigger_error('reasonSizedImage class is being used with an id ('.$id.') that does not appear to be an image.');
				return false;
			}
			else $this->_entity = $image;
		}
		return $this->_entity;
	}
	
	/**
	 * Returns a string describing the width / height (ie 300x200).
	 *
	 * If neither a width nor height has been provided, the return is empty.
	 *
	 * @return string
	 * @todo dynamically populate a missing dimension when the other is provided
	 */
	function _get_dimensions()
	{
		if (!isset($this->_dimensions))
		{
			$width = $this->get_width();
			$height = $this->get_height();
			if (!isset($width) && !isset($height))
			{
				trigger_error('reasonSizedImage class requires that you set the desired width or height of the image to output.');
				$this->_dimensions = false;
				
			}
			else
			{
				if (!isset($width)) // populate width by checking aspect ratio!!
				{
					
				}
				if (!isset($height)) // populate height by checking aspect ratio!!
				{
					
				}
				$this->_dimensions = $width .'x'. $height;
			}
		}
		return $this->_dimensions;
	}
	
	/**
	 * When making the image, the following are essential to the uniqueness
	 *
	 * - width
	 * - height
	 * - crop style
	 * - the entity last modified date (if it changes, we need a new image)
	 *
	 * @todo should we work with original size image if available or is that overkill given the processing time required??
	 * @return boolean success / failure
	 */
	function _make()
	{
		$entity = $this->_get_entity();
		if ($entity)
		{
			if ($this->_make_sure_entity_directory_exists($entity->id()) && is_writable($this->get_image_dir() . $entity->id() . '/'))
			{
				$path = reason_get_image_path($entity, 'original'); // we want to copy our original image to our destination and resize in place
				if (!file_exists($path)) $path = reason_get_image_path($entity); // we get the standard size
				$newpath = $this->_get_path();
				$width = $this->get_width();
				$height = $this->get_height();
				if (empty($path)) trigger_error('reasonSizedImage could not determine the path of the original image - did not make a sized image');
				elseif (empty($newpath)) trigger_error('reasonSizedImage could not determine the proper destination for the sized image');
				elseif (empty($width) && empty($height)) trigger_error('reasonSizedImage needs to be provided a non-empty width or height value to create a sized image');
				else
				{
					copy($path, $newpath);
					resize_image($newpath, $this->get_width(), $this->get_height());
					clearstatcache();
					$perms = substr(sprintf('%o', fileperms($path)), -4);
    				$newperms = substr(sprintf('%o', fileperms($newpath)), -4);
    				if ($perms != $newperms) @chmod($newpath, octdec($perms));
    				return true;
    			}
    			return true;
			}
			else
			{
				trigger_error('reasonSizedImage class cannot write images to ' . $this->get_image_dir() . $entity->id());
			}
		}
		return false;
	}

	function _make_sure_entity_directory_exists($id)
	{
		$image_dir = $this->get_image_dir();
		if (is_dir($image_dir))
		{
			$entity_dir = $image_dir . $id . '/';
			if (!is_dir($entity_dir))
			{
				mkdir($entity_dir);
				clearstatcache();
				$perms = substr(sprintf('%o', fileperms($this->get_image_dir())), -4);
				$newperms = substr(sprintf('%o', fileperms($entity_dir)), -4);
    			if ($perms != $newperms) @chmod($entity_dir, octdec($perms));
			}
			return true;
		}
		else
		{
			trigger_error('Make sure the image directory ('.$this->get_image_dir().') references a directory that is web accessible and writable by apache'); 
		}
		return false;
	}
	
	/** Getters ... boring **/
	function get_id() { return $this->id; }
	function get_width() { return $this->width; }
	function get_height() { return $this->height; }
	function get_image_dir() { return $this->image_dir; }
	function get_image_dir_web_path() { return $this->image_dir_web_path; }
	function get_crop_style() { return $this->crop_style; }
	
	/** Setters ... boring **/
	function set_id($id) { $this->id = $id; }
	function set_width($width) { $this->width = $width; }
	function set_height($height) { $this->height = $height; }
	function set_image_dir($image_dir) { $this->image_dir = $image_dir; }
	function set_image_dir_web_path($image_dir_web_path) { $this->image_web_path = $image_dir_web_path; }
	function set_crop_style($crop_style) { $this->crop_style = $crop_style; }
}
?>