<?php
/*
 * Rules for inclusion of admin modules
 *
 * This file sets up the array $GLOBALS['_reason_admin_modules'].
 * 
 * This array identifues the filename and class name for each admin module.
 * Each key of this array corresponds to a string identified in the request as "cur_module"
 * reason compares the requested module to this keys in this array. If it finds a matching key
 * (case-sensitive) it includes the file identified by the key "file" in the value array of the cur_module key in the classes/admin/modules directory.
 * Reason then instantiates the class identified by the key "class" in the value array.
 *
 * A brief schematic of the $GLOBALS['_reason_admin_modules'] array:
 *
 * <pre>
 *	$GLOBALS['_reason_admin_modules'] = array(
 *		'Default'=>array('file'=>'default.php','class'=>'DefaultModule'),
 *		'Another'=>array('file'=>'another.php','class'=>'AnotherModule'),
 * );
 * </pre>
 *
 * To create a new admin module, add a file to classes/admin/modules. In this file, define a 
 * class that extends the DefaultModule. Overload the various methods as needed. Add a line to this 
 * file (actually, it's probably best to duplicate this file and place it in your local area first)
 * identifying the filename and class name of your new module. Now the new module should be 
 * available simply by altering the cur_module request element to match the key you used in 
 * this array.
 *
 * @package Reason_Core
 */
 	$GLOBALS['_reason_admin_modules'] = array(
		'Default'=>array('file'=>'default.php','class'=>'DefaultModule'),
		'DoBorrow'=>array('file'=>'doBorrow.php','class'=>'DoBorrowModule'),
		'DoAssociate'=>array('file'=>'doAssociate.php','class'=>'DoAssociateModule'),
		'DoDisassociate'=>array('file'=>'doDisassociate.php','class'=>'DoDisassociateModule'),
		'Archive'=>array('file'=>'archive.php','class'=>'ArchiveModule'),
		'Sorting'=>array('file'=>'sorter.php','class'=>'SortingModule'),
		'Site'=>array('file'=>'site.php','class'=>'SiteModule'),
		'Lister'=>array('file'=>'lister.php','class'=>'ListerModule'),
		'Delete'=>array('file'=>'delete.php','class'=>'DeleteModule'),
		'Editor'=>array('file'=>'editor.php','class'=>'EditorModule'),
		'Associator'=>array('file'=>'associator.php','class'=>'AssociatorModule'),
		'ReverseAssociator'=>array('file'=>'reverse_associator.php','class'=>'ReverseAssociatorModule'),
		'user_info'=>array('file'=>'user_info.php','class'=>'UserInfoModule'),
		'kill_session'=>array('file'=>'kill_session.php','class'=>'KillSessionModule'),
		'show_session'=>array('file'=>'show_session.php','class'=>'ShowSessionModule'),
		'about_reason'=>array('file'=>'reason_info.php','class'=>'ReasonInfoModule'),
		'Test'=>array('file'=>'test.php','class'=>'TestModule'),
		'Sharing'=>array('file'=>'sharing.php','class'=>'SharingModule'),
		'Preview'=>array('file'=>'preview.php','class'=>'PreviewModule'),
		'Finish'=>array('file'=>'finish.php','class'=>'FinishModule'),
		'Cancel'=>array('file'=>'cancel.php','class'=>'CancelModule'),
		'NoDelete'=>array('file'=>'no_delete.php','class'=>'NoDeleteModule'),
		'ChooseTheme'=>array('file'=>'choose_theme.php','class'=>'ChooseThemeModule'),
		'ViewUsers'=>array('file'=>'view_users.php','class'=>'ViewUsersModule'),
		'clone'=>array('file'=>'cloner.php','class'=>'cloneModule'),
		'ThorData'=>array('file'=>'thor_data.php','class'=>'ThorDataModule'),
		'GroupTester'=>array('file'=>'group_tester.php','class'=>'GroupTesterModule'),
		'ListSites'=>array('file'=>'list_sites.php','class'=>'ListSitesModule'),
		'ImageImport'=>array('file'=>'image_import.php','class'=>'ImageImportModule'),
		'AllowableRelationshipManager'=>array('file'=>'allowable_relationship_manager.php','class'=>'AllowableRelationshipManagerModule'),
	);
?>