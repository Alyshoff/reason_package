<?php
	/**
	 *	Image Import Disco Form
	 *	@package reason
	 *	@subpackage classes
	 */
	
	/**
	 * include the reason libraries
	 */
	include_once('reason_header.php');
	/**
	 * Include disco so we can extend it
	 */
	include_once( DISCO_INC .'disco.php' );

	/**
	 * Form to upload and add bulk metadata to images
	 *
	 * @author Dave Hendler, Matt Ryan
	 */
	class PhotoUploadForm extends disco
	{
		var $site_id;
		var $elements = array (
														'source_selection_note' => array(
															'type' => 'comment',
															'text' => '<h3>Select files to be uploaded</h3><p>Additional upload fields will be added as you progress.</p>',
														),
														'metadata_note' => array(
															'type' => 'comment',
															'text' => '<h3>Provide some information about the images</h3><p>These values will be applied to all imported images. If you would like to provide more specific information about each image, you may edit the image records in Reason once you have imported them.  Alternately, you may import images one at a time.</p>',
														),
														'name' => array(
															'type' => 'text',
															'display_name' => 'Name<br /><span class="smallText">(What is the subject of this set of images?)</span>',
														),
														'author' => array(
															'type' => 'text',
															'display_name' => 'Author<br /><span class="smallText">(Who took these photos?)</span>',
														),
														'description' => array(
															'type' => 'textarea',
															'display_name' => 'Short Caption<br /><span class="smallText">(A brief description to be used with small versions of the images)</span>',
														),
														'content' => array(
															'type' => 'textarea',
															'display_name' => 'Long Caption<br /><span class="smallText">(A more detailed description to be used with larger-sized versions of the images)</span>',
														),
														'keywords',
														'datetime' => array(
															'type' => 'textDateTime',
															'display_name' => 'Date and Time Photo Taken',
														),
														'original_image_format',
														'exif_override' => array(
															'type' => 'checkbox',
															'display_name' => 'Use camera-recorded date if found <span class="smallText">(Leave this checked to make sure that Reason knows when your images were taken)</span>',
														),
														'attach_to_gallery'=>array('type'=>'hidden'),
														'assign_to_categories'=>array('type'=>'hidden'),
														'no_share'=>array('type'=>'hidden'),
											);
		var $actions = array( 'Import Photos' );
		var $files = array();
		/**
		 * User id of who is logged in.
		 */
		var $user_id;
		var $categories;
		var $max_upload_number = 25;
		
		function pre_show_form()
		{
			echo '<script src="'.WEB_JAVASCRIPT_PATH.'import_photos.js" type="text/javascript"></script>'."\n";
		}
		
		function get_available_categories($site_id)
		{
			$es = new entity_selector($site_id);
			$es->add_type( id_of( 'category_type' ) );
			$categories = $es->run_one();
			return $categories;
		}
		function get_available_image_galleries($site_id)
		{
			reason_include_once( 'minisite_templates/page_types.php' );
			$gallery_modules = array('gallery', 'alumni_gallery', 'gallery_horizons', 'gallery_vote');
			foreach($GLOBALS['_reason_page_types'] as $page_type_name => $page_type_values)
			{
				if(!in_array($page_type_name, $gallery_modules))
				{
					foreach($page_type_values as $value)
					{
						if(is_array($value))
						{
							foreach($value as $nested_value)
							{								
								if(is_string($nested_value) && in_array($nested_value, $gallery_modules))
								{
									$gallery_modules[] = $page_type_name;
								}
							}
						}
						elseif(is_string($value) && in_array($value, $gallery_modules))
						{
							$gallery_modules[] = $page_type_name;
						}
					}
				}
			}
						
			$es = new entity_selector($site_id);
			$es->add_type( id_of( 'minisite_page' ) );
			$es->add_relation( 'page_node.custom_page IN (\''.implode('\', \'', $gallery_modules).'\')' );
			$pages = $es->run_one();
			return $pages;
		}
		function on_every_time()
		{
			$this->form_enctype = 'multipart/form-data';
			$this->change_element_type( 'original_image_format','select', array( 'options' => array( 'slide' => 'Slide','print' => 'Print','digital' => 'Digital' ) ) );
			
			$site_id = $this->site_id;

			//find available categories
			$this->categories = $this->get_available_categories($site_id);
			if(!empty($this->categories))
			{
				$args = array();
				$category_args = array();
				foreach($this->categories as $category_id => $category)
				{
					$category_args[$category_id] = $category->get_value('name');
				}
				$this->change_element_type( 'assign_to_categories','select_multiple', array( 'options' => $category_args, 'display_name'=>'Assign to Categories <span class="smallText">(Control-click (PC) or command-click (Mac) to select multiple categories)</span>') );
			}
			
			//find available galleries
			$this->galleries = $this->get_available_image_galleries($site_id);
			$gallery_args = array();
			foreach($this->galleries  as $gallery_id => $gallery)
			{
				$gallery_args[$gallery_id] = $gallery->get_value('name');
			}
			if(!empty($gallery_args))
			{
				$this->change_element_type('attach_to_gallery','select', array( 'options' => $gallery_args ) );
			}
			
			// sharing
			if(!$this->get_value('no_share'))
			{
				$this->set_value('no_share',0);
			}
			if(!$this->get_value('exif_override'))
			{
				$this->set_value('exif_override','true');
			}
			if( site_shares_type($site_id, id_of('image')) )
			{
				$this->change_element_type( 'no_share', 'select', array( 'options' => array( 0=>'Shared', 1=>'Private' ) ) );
				$this->set_display_name( 'no_share', 'Sharing' );
			}
			
			$site = new entity($site_id);
			
			$order = array();
			if($this->_is_element('cancel_text'))
			{
				$order[] = 'cancel_text';
			}
			$order[] = 'source_selection_note';
			//$order[] = 'incoming_dir';
			$order[] = 'destination_selection_note';
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				$name = 'upload_'.$i;
				$this->add_element( $name, 'image_upload', array('max_width'=>REASON_STANDARD_MAX_IMAGE_WIDTH,'max_height'=>REASON_STANDARD_MAX_IMAGE_HEIGHT) );
				$order[] = $name;
			}
			
			$this->set_order( $order );
		}
		function process()
		{
			$site_id = $this->site_id;
			
			$counts = array();
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				$name = 'upload_'.$i;
				$element = $this->get_element( 'upload_'.$i );
				if( !empty($element->tmp_full_path) AND file_exists( $element->tmp_full_path ) )
				{
					if(empty($counts[$element->file['name']]))
					{
						$this->files[$element->file['name']] = $element->tmp_full_path;
						$counts[$element->file['name']] = 1;
					}
					else
					{
						$counts[$element->file['name']]++;
						$this->files[$element->file['name'].'.'.$counts[$element->file['name']]] = $element->tmp_full_path;
					}
				}
			}
			if( count( $this->files ) )
			{
				// try to find the gallery page for the chosen site
				if( $this->get_value( 'attach_to_gallery' ) )
				{
					$gallery_page_id = '';
				}

				$page_to_image_rel_id = relationship_id_of('minisite_page_to_image');
				
				$tables = get_entity_tables_by_type( id_of( 'image' ) );
				
				echo '<ul>'."\n";
				foreach( $this->files AS $entry => $cur_name )
				{
					echo '<li><strong>'.$entry.':</strong> processing ';
					
					$date = '';
					
					// get suffix
					$type = strtolower( substr($cur_name,strrpos($cur_name,'.')+1) );
					$ok_types = array('jpg');
					
					// get exif data
					if( $this->get_value( 'exif_override' ) && in_array($type,$ok_types))
					{
						// read_exif_data() does not obey error supression
						turn_carl_util_error_logging_off();
						$exif_data = @read_exif_data( $cur_name );
						turn_carl_util_error_logging_on();
						if( $exif_data )
						{
							// some photos may have different fields filled in for dates - look through these until one is found
							$valid_dt_fields = array( 'DateTimeOriginal', 'DateTime', 'DateTimeDigitized' );
							foreach( $valid_dt_fields AS $field )
							{
								// once we've found a valid date field, store that and break out of the loop
								if( !empty( $exif_data[ $field ] ) )
								{
									$date = $exif_data[ $field ];
									break;
								}
							}
						}
					}
					else
					{
						$date = $this->get_value( 'datetime' );
					}
					
					$keywords = $entry;
					if($this->get_value( 'keywords' ))
					{
						$keywords .= ', '.$this->get_value( 'keywords' );
					}
					
					// insert entry into DB with proper info
					$values = array(
						'datetime' => $date,
						'image_type' => $type,
						'author' => $this->get_value( 'author' ),
						'state' => 'Pending',
						'keywords' => $keywords,
						'description' => $this->get_value( 'description' ),
						'name' => ($this->get_value( 'name' ) ? $this->get_value( 'name' ) : $entry),
						'content' => $this->get_value( 'content' ),
						'original_image_format' => $this->get_value( 'original_image_format' ),
						'new' => 0,		// make sure this goes in pending queue
						'no_share' => $this->get_value('no_share'),
					);
					//tidy values
					$no_tidy = array('state','new');
					foreach($values as $key=>$val)
					{
						if(!in_array($key,$no_tidy) && !empty($val))
						{
							$values[$key] = trim(get_safer_html(tidy($val)));
						}
					}
					
					$id = reason_create_entity( $site_id, id_of( 'image' ), $this->user_id, $entry, $values  );
					
					if( $id )
					{
						//assign to categories
						$categories = $this->get_value('assign_to_categories');
						if(!empty($categories))
						{
							foreach($categories as $category_id)
							{
								create_relationship($id, $category_id, relationship_id_of('image_to_category'));
							}
						}
					
						//assign to	gallery page
						$page_id = $this->get_value('attach_to_gallery');
						if(!empty($page_id))
							create_relationship($page_id, $id, relationship_id_of('minisite_page_to_image') );
						
						
						// resize and move photos
						$new_name = PHOTOSTOCK.$id.'.'.$type;
						$orig_name = PHOTOSTOCK.$id.'_orig.'.$type;
						$tn_name = PHOTOSTOCK.$id.'_tn.'.$type;
						// atomic move the file if possible, copy if necessary
						if( is_writable( $cur_name ) )
						{
							rename( $cur_name, $new_name );
						}
						else
						{
							copy( $cur_name, $new_name );
						}
						//copy( $orig_name, $new_name );
						list($width, $height, $type, $attr) = getimagesize($new_name);
						
						if($width > REASON_STANDARD_MAX_IMAGE_WIDTH || $height > REASON_STANDARD_MAX_IMAGE_HEIGHT)
						{
							copy( $new_name, $orig_name );
							shell_exec( 'mogrify -geometry '.REASON_STANDARD_MAX_IMAGE_WIDTH.'x'.REASON_STANDARD_MAX_IMAGE_HEIGHT.' '.$new_name.'  2>&1' );
						}
						
						$thumb_dimensions = get_reason_thumbnail_dimensions($site_id);
						if($width > $thumb_dimensions['width'] || $height > $thumb_dimensions['height'])
						{
							copy( $new_name, $tn_name );
							shell_exec( 'mogrify -geometry '.$thumb_dimensions['width'].'x'.$thumb_dimensions['height'].' '.$tn_name.' 2>&1' );
						}
						// real original
						if( file_exists( $cur_name.'.orig' ) )
						{
							// move the original image into the photostock directory
							if( is_writable( $cur_name.'.orig' ) )
							{
								rename( $cur_name.'.orig', $orig_name);
							}
							else
							{
								copy( $cur_name.'.orig', $orig_name);
							}
						}
						
						$info = getimagesize( $new_name );
						$size = round(filesize( $new_name ) / 1024);
						
						// now we have the size of the resized image.
						$values = array(
							'width' => $info[0],
							'height' => $info[1],
							'size' => $size,
						);
						
						// update with new info - don't archive since this is really just an extension of the creation of the item
						// we needed that ID to do something
						reason_update_entity( $id, $this->user_id, $values, false );
						
						if( !empty( $gallery_page_id ) )
						{
							create_relationship($gallery_page_id,$id,$page_to_image_rel_id);
						}
						echo 'completed</li>';
					}
					else
					{
						trigger_error('Unable to create image entity');
						echo '<li>Unable to import '.$entry.'</li>';
					}
					sleep( 1 );
				}
				echo '</ul>'."\n";
				echo '<p>Your images have been successfully imported into Reason.</p>';
				echo '<p>They are now pending.</p>';
				
				$site = new entity($site_id);
				
				echo '<p>Next Steps:</p><ul><li><a href="?site_id='.$site_id.'&amp;type_id='.id_of( 'image' ).'&amp;user_id='.$this->user_id.'&amp;cur_module=Lister&amp;state=pending">review & approve imported images</a></li><li><a href="'.get_current_url().'">Import another set of images</a></li></ul>'."\n";
			}
			else
			{
				echo 'You chose a folder that has no images.';
			}
			
			$this->show_form = false;
		}
	}
?>
