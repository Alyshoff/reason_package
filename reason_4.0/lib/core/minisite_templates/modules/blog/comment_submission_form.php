<?
	include_once( DISCO_INC.'disco.php');
	
	////////////////////////
	//COMMENT SUBMISSION FORM
	///////////////////////
	class commentForm extends Disco
	{
		var $elements = array(
			'author' => array(
									'type'=>'text',
									'display_name' => 'Name',
									'size' => 30,
								),
			'comment_content' => array(
									'type'=>'loki',
									'display_name' => 'Comment',
									'widgets' => array('strong','em','link',),
								),
			'tarbaby_pre' => array(
									'type'=>'comment',
									'text'=>'The following fields are not to be filled out. <a href="#discoSubmitRow">Skip to Submit Button</a>.',
								),
			'tarbaby' => array(
									'type'=>'text',
									'display_name'=>'Not Comment',
									'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
								),
			'not_url' => array(
									'type'=>'text',
									'display_name'=>'not URL',
									'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
								),
			'antlion' => array(
									'type'=>'text',
									'display_name'=>'Avoid',
									'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
								),
		); 
		
		var $required = array(
			'author',
			'comment_content',
		);
		var $forbidden = array(
			'tarbaby',
			'not_url',
			'antlion',
		);
		
		var $actions = array('Submit'=>'Submit Comment');
		
		var $site_id;
		var $site_info;
		var $news_item;
		var $comment_id;
		var $username;
		var $blog;
		
		function commentForm($site_id, $news_item, $blog)
		{
			$this->site_id = $site_id;
			$this->site_info = get_entity_by_id ($this->site_id);
			$this->blog = $blog;
			$this->news_item = $news_item;
		}
		function set_username($username)
		{
			$this->username = $username;
		}
		function on_every_time()
		{
			if($this->blog->get_value('hold_comments_for_review') == 'yes')
			{
				$this->actions['Submit'] = 'Submit Comment (Moderated)';
			}
			$this->do_editable_alterations();
		}
		function do_editable_alterations()
		{
			$editor_name = html_editor_name($this->site_info['id']);
			$editor_params = html_editor_params($this->site_info['id']);
			if(strpos($editor_name,'loki') === 0)
			{
				$editor_params['widgets'] = array('strong','em','link');
				$editor_params['paths'] = array(); // remove integration pieces so that commenters cannot spelunk Reason
			}
			$this->change_element_type( 'comment_content',  $editor_name, $editor_params );
		}
		function get_site_user_id( $username, $site)
		{
			// Check if site user is a type on the site
			// Add site user type to site if it is not there
			// Check if this netid is a site user
			// if so, return id
			// Otherwise create a site user and return ID of new site user
		}
		function run_error_checks()
		{
			foreach($this->forbidden as $field)
			{
				if($this->get_value($field))
				{
					$this->set_error($field,'This field must be left empty for your comment to work');
				}
			}
			$fields_to_tidy = array('comment_content');
			foreach($fields_to_tidy as $field)
			{
				if($this->get_value($field))
				{
					$tidied = trim(tidy($this->get_value($field)));
					if(empty($tidied) && in_array($field,$this->required))
					{
						if(!empty($this->elements[$field]['display_name']))
						{
							$display_name = $this->elements[$field]['display_name'];
						}
						else
						{
							$display_name = prettify_string($field);
						}
						$this->set_error($field,'Please fill in the '.$display_name.' field');
					}
					else
					{
						$tidy_errors = tidy_err($this->get_value($field));
						if(!empty($tidy_errors))
						{
							$msg = 'The html in the '.$field.' field is misformed.  Here is what the html checker has to say:<ul>';
							foreach($tidy_errors as $tidy_error)
							{
								$msg .= '<li>'.$tidy_error.'</li>';
							}
							$msg .= '</ul>';
							$this->set_error($field,$msg);
						}
					}
				}
			}
		}
		function process(){
			if(!empty($this->username))
			{
				$user_id = make_sure_username_is_user($this->username, $this->site_id);
			}
			else
			{
				$user_id = $this->site_info['id'];
			}
	#  Things to consider - difference between names and titles.  Perhaps add a title field and default name to title, 
	#  then make it be an author-date name if there is not title.  Dave says do first sixty words for name.
	
			if($this->blog->get_value('hold_comments_for_review') == 'yes')
			{
				$show_hide = 'hide';
			}
			else
			{
				$show_hide = 'show';
			}
			$flat_values = array (
				'state' => 'Live',
				'author' => trim(get_safer_html(strip_tags($this->get_value('author')))),
				'content' => trim(get_safer_html(strip_tags(tidy($this->get_value('comment_content')), '<p><em><strong><a><ol><ul><li><blockquote><acronym><abbr><br><cite><code><pre>'))),
				'datetime' => date('Y-m-d H:i:s'),
				'show_hide' => $show_hide,
				'new'=>'0',
			);
	
			$this->comment_id = reason_create_entity( 
				$this->site_id, 
				id_of('comment_type'), 
				$user_id, 
				trim(substr(strip_tags($flat_values['content']),0,40)),
				$flat_values,
				$testmode = false
			);
			
			create_relationship(
				$this->news_item->_id,
				$this->comment_id,
				relationship_id_of('news_to_comment')
			);
		}
		function where_to() // {{{
		{
			return '?story_id='.$this->news_item->id().'&comment_posted_id='.$this->comment_id;
		} // }}}
	}
?>
