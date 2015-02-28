<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $action_success, $download_success;

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

$Form = new Form( NULL, 'upgrade_form', 'post' );

$Form->add_crumb( 'upgrade_started' ); // In case we want to "Force download" again
$Form->add_crumb( 'upgrade_downloaded' ); // In case we want to "Unzip"
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform' );

// Display the form buttons
$Form->begin_fieldset( T_( 'Actions' ) );

$form_buttons = array();
if( $action_success && $download_success )
{ // Init a button to unzip
	$form_buttons[] = array( 'submit', 'actionArray[unzip]', T_( 'Unzip package' ), 'SaveButton' );
}
elseif( $download_success )
{ // Init the buttons to select next action
	$form_buttons[] = array( 'submit', 'actionArray[unzip]', T_( 'Skip Download' ), 'SaveButton' );
	$form_buttons[] = array( 'submit', 'actionArray[force_download]', T_( 'Force New Download' ), 'SaveButton' );
}
else
{ // Init a button to back step
	$form_buttons[] = array( 'submit', 'actionArray[start]', T_( 'Back to Package Selection' ), 'SaveButton' );
}

$Form->end_form( $form_buttons );

?>