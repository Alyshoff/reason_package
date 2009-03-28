<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * Provide basic information on any entity ID
	 *
	 * This module limits its access to users who have the privilege to view sensitive data,
	 * as it can show some basic info about any entity.
	 */
	class EntityInfoModule extends DefaultModule// {{{
	{
		function EntityInfoModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$this->admin_page->title = 'Get Basic Entity Information';
		} // }}}
		function run() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p>'."\n";
				return;
			}
			if(!empty($this->admin_page->request['entity_id_test']))
			{
				$id = $this->admin_page->request['entity_id_test'];
				settype($id, 'integer');
			}
			if(empty($id))
			{
				$id = '';
			}
			echo '<form method="get" action="?"><label for="entity_id_test">Entity ID:</label> <input type="text" name="entity_id_test" id="entity_id_test" value="'.$id.'"/><input type="submit" value="submit" /><input type="hidden" name="cur_module" value="EntityInfo" /></form>';
			if(!empty($id))
			{
				$entity = new entity($id);
				if($entity->get_values())
				{
					$owner = $entity->get_owner();
					if(is_object($owner))
					{
						echo '<p>Owner site: '.$owner->get_value('name').' (ID: '.$owner->id().')</p>'."\n";
					}
					else
					{
						echo '<p>No owner site (orphan entity)</p>'."\n";
					}
					echo '<p>Entity name: '.$entity->get_display_name().'</p>'."\n";
					echo '<p>Entity data:</p>';
					pray($entity->get_values());
				}
				else
				{
					echo '<p>The Reason ID '.$id.' does not belong to a real entity. It may have been deleted.</p>';
				}
			}
		} // }}}
	} // }}}
?>