<?php
/**
 * This file display the additional tools
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Plugins, $template_action;

$block_item_Widget = new Widget( 'block_item' );

if( !empty( $template_action ) )
{ // Execute action inside template to display a process in real rime
	$block_item_Widget->title = T_('Log');
	$block_item_Widget->disp_template_replaced( 'block_start' );

	// Turn off the output buffering to do the correct work of the function flush()
	@ini_set( 'output_buffering', 'off' );
	evo_flush();

	switch( $template_action )
	{
		case 'optimize_tables':
			// Optimize MyISAM & InnoDB tables
			dbm_optimize_tables();
			break;

		case 'check_tables':
			// Check ALL database tables
			dbm_check_tables();
			break;

		case 'analyze_tables':
			// Analize ALL database tables
			dbm_analyze_tables();
			break;

		case 'delete_orphan_files':
			// delete orphan File objects with no matching file on disk
			dbm_delete_orphan_files();
			break;

		case 'delete_orphan_file_roots':
			// delete orphan file roots with no matching Blog or User entry in the database
			dbm_delete_orphan_file_roots();
			break;

		case 'recreate_autogenerated_excerpts':
			// Re-create all autogenerated excerpts
			dbm_recreate_autogenerated_excerpts();
			break;

		case 'convert_item_content_separators':
			// Convert item content separators to new format
			dbm_convert_item_content_separators();
			break;

		case 'del_broken_posts':
			// Delete all broken posts that have no matching category
			dbm_delete_broken_posts();
			break;

		case 'utf8upgrade':
			// Upgrade DB to UTF-8
			db_upgrade_to_utf8();
			break;
	}
	$block_item_Widget->disp_template_raw( 'block_end' );
}


if( $current_User->check_perm( 'users', 'edit' ) )
{ // Setting to lock system
	global $Settings;

	$Form = new Form( NULL, 'settings_checkchanges' );
	$Form->begin_form( 'fform' );

	$Form->add_crumb( 'globalsettings' );
	$Form->hidden( 'ctrl', 'gensettings' );
	$Form->hidden( 'action', 'update_tools' );

	$Form->begin_fieldset( T_('Locking down b2evolution for maintenance, upgrade or server switching...').get_manual_link('system-lock') );

		$Form->checkbox_input( 'system_lock', $Settings->get('system_lock'), T_('Lock system'), array(
				'note' => T_('check this to prevent login (except for admins) and sending comments/messages. This prevents the DB from receiving updates (other than logging)').'<br />'.
				          T_('Note: for a more complete lock down, rename the file /conf/_maintenance.html to /conf/maintenance.html (complete lock) or /conf/imaintenance.html (gives access to /install)') ) );

	if( $current_User->check_perm( 'options', 'edit' ) )
	{
		$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
	}

	$Form->end_fieldset();

	$Form->end_form();
}

// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
if( $current_User->check_perm('options', 'edit') )
{ // default admin actions:
	global $Settings;

	$block_item_Widget->title = T_('Cache management');
	// dh> TODO: add link to delete all caches at once?
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_itemprecache&amp;'.url_crumb('tools')).'">'.T_('Clear pre-renderered item cache (DB)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_commentprecache&amp;'.url_crumb('tools')).'">'.T_('Clear pre-renderered comment cache (DB)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_messageprecache&amp;'.url_crumb('tools')).'">'.T_('Clear pre-renderered message cache (DB)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_filecache&amp;'.url_crumb('tools')).'">'.T_('Clear thumbnail caches (?evocache directories)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_pagecache&amp;'.url_crumb('tools')).'">'.T_('Clear full page caches (/cache/* directories)').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=repair_cache&amp;'.url_crumb('tools')).'">'.T_('Repair /cache/* directory structure').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );

	$block_item_Widget->title = T_('Database management');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=check_tables&amp;'.url_crumb('tools')).'">'.T_('CHECK database tables').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=optimize_tables&amp;'.url_crumb('tools')).'">'.T_('OPTIMIZE database tables').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=analyze_tables&amp;'.url_crumb('tools')).'">'.T_('ANALYZE database tables').'</a></li>';
	// echo '<li><a href="'.regenerate_url('action', 'action=backup_db').'">'.T_('Backup database').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );

	$block_item_Widget->title = T_('Cleanup tools');
	$block_item_Widget->disp_template_replaced( 'block_start' );
	echo '<ul>';
	echo '<li><a href="'.regenerate_url('action', 'action=del_obsolete_tags&amp;'.url_crumb('tools')).'">'.T_('Find and delete all orphan Tag entries (not used anywhere) - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=find_broken_posts&amp;'.url_crumb('tools')).'">'.T_('Find all broken posts (with no matching Category) + Option to delete with related objects - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=find_broken_slugs&amp;'.url_crumb('tools')).'">'.T_('Find all broken slugs (with no matching Item) + Option to delete - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=delete_orphan_comments&amp;'.url_crumb('tools')).'">'.T_('Find and delete all orphan Comments (with no matching Item) - Disk &amp; DB.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=delete_orphan_comment_uploads&amp;'.url_crumb('tools')).'">'.T_('Find and delete all orphan comment Uploads - Disk &amp; DB.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=delete_orphan_files&amp;'.url_crumb('tools')).'">'.T_('Find and delete all orphan File objects (with no matching file on disk) - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=delete_orphan_file_roots&amp;'.url_crumb('tools')).'">'.T_('Find and delete all orphan file roots (with no matching Collection or User) and all of their content recursively - Disk &amp; DB.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=prune_hits_sessions&amp;'.url_crumb('tools')).'">'.T_('Prune old hits &amp; sessions (includes OPTIMIZE) - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=recreate_itemslugs&amp;'.url_crumb('tools')).'">'.T_('Recreate all item Slugs (change title-[0-9] canonical slugs to a slug generated from current title). Old slugs will still work, but will redirect to the new ones - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=recreate_autogenerated_excerpts&amp;'.url_crumb('tools')).'">'.T_('Recreate autogenerated excerpts - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=convert_item_content_separators&amp;'.url_crumb('tools')).'">'.T_('Convert item content separators to [teaserbreak] and [pagebreak] - DB only.').'</a></li>';
	echo '<li><a href="'.regenerate_url('action', 'action=utf8upgrade&amp;'.url_crumb('tools')).'">'.T_('Upgrade your DB to UTF-8 - DB only.').'</a></li>';
	echo '</ul>';
	$block_item_Widget->disp_template_raw( 'block_end' );
}

// We should load GeoIP plugin here even if it is disabled now, because action 'geoip_download' may be requested
$Plugins->load_plugin_by_classname( 'geoip_plugin' );

// Event AdminToolPayload for each Plugin:
$tool_plugins = $Plugins->get_list_by_event( 'AdminToolPayload' );
foreach( $tool_plugins as $loop_Plugin )
{
	$block_item_Widget->title = format_to_output($loop_Plugin->name);
	$block_item_Widget->disp_template_replaced( 'block_start' );
	$Plugins->call_method_if_active( $loop_Plugin->ID, 'AdminToolPayload', $params = array() );
	$block_item_Widget->disp_template_raw( 'block_end' );
}

?>