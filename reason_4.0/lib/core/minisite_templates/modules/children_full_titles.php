<?php

	reason_include_once( 'minisite_templates/modules/children.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildrenFullTitlesModule';
	
	class ChildrenFullTitlesModule extends ChildrenModule 
	{
		function run() // {{{
		{
			/* If the page has no entries, say so */
			if( empty($this->offspring ) )
			{
				echo 'This page has no children<br />';	
			}
			/* otherwise, list them */
			else
			{
				echo '<ul class="childrenList">'."\n";

				foreach( $this->offspring AS $child )
				{
					if ( $this->parent->cur_page->id() != $child->id() )
					{
						
						/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
						if( $child->get_value( 'url' ) )
							$link = $child->get_value( 'url' );
						else
						{
							$link = $child->get_value( 'url_fragment' ).'/';
							if (!empty($this->parent->textonly))
								$link .= '?textonly=1';
						}
							
						echo '<li><h4><a href="'.$link.'"';
						if($child->get_value('description'))
							echo ' title="'.str_replace('"','&quot;',$child->get_value('description')).'"';
						echo '>'.$child->get_value('name').'</a></h4>';
						if ( $child->get_value( 'description' ))
							echo "\n".'<div class="childDesc">'.$child->get_value( 'description' ).'</div>';
						echo "</li>\n";
					}
				}
				echo "</ul>\n";
			}
		} // }}}
	}

?>
