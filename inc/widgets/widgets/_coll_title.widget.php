<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_title_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_title_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_title' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Blog title');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		global $Blog;

		return $Blog->dget( 'name', 'htmlbody' );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		global $Blog;
		return sprintf( T_('&laquo;%s&raquo; from the blog\'s <a %s>general settings</a>.'),
				'<strong>'.$Blog->dget('name').'</strong>', 'href="?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'"' );
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;

		$this->init_display( $params );

		// Collection title:
		echo $this->disp_params['block_start'];

		$title = '<a href="'.$Blog->get( 'url', 'raw' ).'">'
							.$Blog->dget( 'name', 'htmlbody' )
							.'</a>';
		$this->disp_title( $title );

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>