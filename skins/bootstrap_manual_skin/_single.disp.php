<?php
/**
 * This is the template that displays a post for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Display message if no post:
display_if_empty();

if( $Item = & mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	echo '<div id="styled_content_block">'; // Beginning of posts display TODO: get rid of this ID, use class .evo_content_block instead
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
		), $Skin->get_template( 'disp_params' ) ) );
	// ----------------------------END ITEM BLOCK  ----------------------------
	echo '</div>'; // End of posts display
}

?>