<?php
/**
 * This file implements the AbstractSettings class designed to handle all kinds of settings.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Fran�ois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Fran�ois PLANQUE
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Class to handle the global settings.
 *
 * @package evocore
 * @abstract
 * @see UserSettings, GeneralSettings
 */
class AbstractSettings
{
	/**
	 * The DB table which stores the settings.
	 *
	 * @var string
	 * @access protected
	 */
	var $dbTableName;

	/**
	 * Array with DB column key names.
	 *
	 * @var array of strings
	 * @access protected
	 */
	var $colKeyNames = array();

	/**
	 * DB column name for the value.
	 *
	 * @var string
	 * @access protected
	 */
	var $colValueName;


	/**
	 * The number of column keys to cache by. This are the first x keys of
	 * {@link $colKeyNames}. 0 means 'load all'.
	 *
	 * @var integer
	 */
	var $cacheByColKeys;


	/**
	 * The internal cache.
	 *
	 * @access protected
	 * @var array|NULL|false Contains the loaded settings or false, if settings
	 *                       could not be loaded or NULL if not initialized.
	 */
	var $cache = NULL;


	/**
	 * Do we have loaded everything?
	 *
	 * @var boolean
	 */
	var $allLoaded = false;

	/**
	 * Default settings.
	 *
	 * Maps last colkeyname to some default setting that will be used by
	 * {@link get()} if no value was defined (and it is set as a default).
	 *
	 * @var array
	 */
	var $_defaults = array();


	/**
	 * Constructor.
	 * @param string The name of the DB table with the settings stored.
	 * @param array List of names for the DB columns keys that reference a value.
	 * @param string The name of the DB column that holds the value.
	 */
	function AbstractSettings( $dbTableName, $colKeyNames, $colValueName, $cacheByColKeys = 0 )
	{
		$this->dbTableName = $dbTableName;
		$this->colKeyNames = $colKeyNames;
		$this->colValueName = $colValueName;
		$this->cacheByColKeys = $cacheByColKeys;


		/**
		 * @var integer internal counter for the number of column keys
		 */
		$this->count_colKeyNames = count( $this->colKeyNames );

		if( $this->count_colKeyNames > 3 || $this->count_colKeyNames < 1 )
		{
			die( 'Settings keycount not supported for class '.get_class() );
		}
	}


	/**
	 * Load all settings, disregarding the derived classes setting of
	 * {@link $cacheByColKeys} - useful if you know that you want to get
	 * all user settings for example.
	 */
	function loadAll()
	{
		return $this->load();
	}


	/**
	 * Loads the settings. Not meant to be called directly, but gets called
	 * when needed.
	 *
	 * @param array|NULL list of key values, used for caching by keys.
	 * @return boolean always true
	 */
	function load( $getArgs = NULL )
	{
		if( $this->allLoaded )
		{ // already all loaded
			return true;
		}

		/**
		 * The where clause - gets filled when {@link $cacheByColKeys} is used.
		 */
		$whereList = array();

		if( $this->cacheByColKeys && is_array($getArgs) )
		{
			$testCache = $this->cache;

			for( $i = 0; $i < $this->cacheByColKeys; $i++ )
			{
				$whereList[] = $this->colKeyNames[$i].' = "'.$getArgs[$i].'"';

				if( !is_array( $testCache )
						|| !isset( $testCache[$getArgs[$i]] )
						|| !($testCache =& $testCache[$getArgs[$i]]) )
				{
					break;
				}
			}

			if( $i == $this->cacheByColKeys )
			{ // already loaded!
				return true;
			}
		}
		else
		{ // we're about to load everything
			$this->allLoaded = true;
		}


		global $DB;
		$result = $DB->get_results( 'SELECT '.implode( ', ', $this->colKeyNames ).', '.$this->colValueName
																.' FROM '.$this->dbTableName
																.( isset( $whereList[0] ) ?
																		' WHERE '.implode( ' AND ', $whereList ) :
																		'' ) );

		switch( $this->count_colKeyNames )
		{
			case 1:
				if( !$result )
				{
					$this->cache[ $getArgs[0] ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colKeyNames[0]}]->value = $loop_row->{$this->colValueName};
					$this->cache[$loop_row->{$this->colKeyNames[0]}]->dbuptodate = true;
				}
				break;

			case 2:
				if( !$result )
				{
					$this->cache[ $getArgs[0] ][ $getArgs[1] ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colKeyNames[0]}][$loop_row->{$this->colKeyNames[1]}]->value = $loop_row->{$this->colValueName};
					$this->cache[$loop_row->{$this->colKeyNames[0]}][$loop_row->{$this->colKeyNames[1]}]->dbuptodate = true;
				}
				break;

			case 3:
				if( !$result )
				{
					$this->cache[ $getArgs[0] ][ $getArgs[1] ][ $getArgs[2] ] = NULL;
				}
				else foreach( $result as $loop_row )
				{
					$this->cache[$loop_row->{$this->colKeyNames[0]}][$loop_row->{$this->colKeyNames[1]}][$loop_row->{$this->colKeyNames[2]}]->value = $loop_row->{$this->colValueName};
					$this->cache[$loop_row->{$this->colKeyNames[0]}][$loop_row->{$this->colKeyNames[1]}][$loop_row->{$this->colKeyNames[2]}]->uptodate = true;
				}
				break;
		}

		return true;
	}


	/**
	 * Get a setting from the DB settings table.
	 *
	 * @uses {@link getDefault()}
	 * @param string $args,... the values for the column keys (depends on
	 *                         $this->colKeyNames and must match its count and order)
	 * @return string|false|NULL value as string on success;
	 *                           NULL if not found; false in case of error
	 */
	function get()
	{
		global $Debuglog;

		$args = func_get_args();
		$this->load( $args );

		if( func_num_args() != $this->count_colKeyNames )
		{
			$Debuglog->add( 'Count of arguments for AbstractSettings::get() does not '
											.'match $colKeyNames (class '.get_class($this).').', 'error' );
			return false;
		}

		$debugMsg = get_class($this).'::get( '.implode( ', ', $args ).' ): ';

		$r = NULL;

		switch( $this->count_colKeyNames )
		{
			case 1:
				if( isset($this->cache[ $args[0] ]) )
				{
					$r = $this->cache[ $args[0] ]->value;
				}
				elseif( NULL !== ($default = $this->getDefault( $args[0] )) )
				{
					$r = $default;
					$debugMsg .= '[DEFAULT]: ';
				}
				break;

			case 2:
				if( isset($this->cache[ $args[0] ][ $args[1] ]) )
				{
					$r = $this->cache[ $args[0] ][ $args[1] ]->value;
				}
				elseif( NULL !== ($default = $this->getDefault( $args[1] )) )
				{
					$r = $default;
					$debugMsg .= '[DEFAULT]: ';
				}
				break;

			case 3:
				if( isset($this->cache[ $args[0] ][ $args[1] ][ $args[2] ]) )
				{
					$r = $this->cache[ $args[0] ][ $args[1] ][ $args[2] ]->value;
				}
				elseif( NULL !== ($default = $this->getDefault( $args[2] )) )
				{
					$r = $default;
					$debugMsg .= '[DEFAULT]: ';
				}
				break;
		}

		$Debuglog->add( $debugMsg.var_export( $r, true ), 'settings' );

		return $r;
	}


	/**
	 * Get the default for the last key of {@link $colKeyNames}
	 *
	 * @param string The last column key
	 * @return NULL|mixed NULL if no default is set, otherwise the value (should be string).
	 */
	function getDefault( $lastKey )
	{
		if( isset($this->_defaults[ $lastKey ]) )
		{
			return $this->_defaults[ $lastKey ];
		}

		return NULL;
	}


	/**
	 * Only set the first variable (passed by reference) if we could retrieve a
	 * setting.
	 *
	 * @param mixed variable to eventually set (by reference)
	 * @param string the values for the column keys (depends on $this->colKeyNames
	 *               and must match its count and order)
	 * @return boolean true on success (variable was set), false if not
	 */
	function get_cond( &$toset )
	{
		$args = func_get_args();
		array_shift( $args );

		$result = call_user_func_array( array( &$this, 'get' ), $args );

		if( $result !== false )
		{
			$toset = $result;
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Temporarily sets a setting ({@link updateDB()} writes it to DB).
	 *
	 * @param string $args,... the values for the {@link $colKeyNames column keys}
	 *                         and {@link $colValueName column value}. Must match order and count!
	 */
	function set()
	{
		global $Debuglog;

		$args = func_get_args();
		$this->load( $args ); // the last Arg is not meant to go to load() but it does no harm AFAICS

		$count_args = func_num_args();

		if( $count_args != ( $this->count_colKeyNames + 1) )
		{
			$Debuglog->add( 'Count of arguments for AbstractSettings::set() does not match $colkeyname + 1 (colkeyvalue).', 'error' );
			return false;
		}

		switch( $this->count_colKeyNames )
		{
			case 1:
				$atcache =& $this->cache[ $args[0] ];
				break;
			case 2:
				$atcache =& $this->cache[ $args[0] ][ $args[1] ];
				break;
			case 3:
				$atcache =& $this->cache[ $args[0] ][ $args[1] ][ $args[2] ];
				break;
			default:
				return false;
		}

		$debugMsg = get_class($this).'::set( '.implode(', ', $args ).' ): ';

		if( isset($atcache->value) )
		{
			if( $atcache->value == $args[ $count_args-1 ] )
			{ // already set
				$Debuglog->add( $debugMsg.' Already set to the same value.', 'settings' );
				return false;
			}
		}

		$atcache->value = $args[ $count_args-1 ];
		$atcache->dbuptodate = false;

		$Debuglog->add( $debugMsg.' SET!', 'settings' );

		return true;
	}


	/**
	 * Commit changed settings to DB.
	 *
	 * @return boolean true, if settings have been updated; false otherwise
	 */
	function updateDB()
	{
		global $DB;

		$query_insert = array();

		if( !$this->cache )
		{
			return false;
		}


		switch( $this->count_colKeyNames )
		{
			case 1:
				foreach( $this->cache as $key => $value )
				{
					if( !$value->dbuptodate )
					{
						$query_insert[] = "('$key', '".$DB->escape( $value->value )."')";
					}
				}
				break;

			case 2:
				foreach( $this->cache as $key => $value )
					foreach( $value as $key2 => $value2 )
					{
						if( !$value2->dbuptodate )
						{
							$query_insert[] = "('$key', '$key2', '".$DB->escape( $value2->value )."')";
						}
					}
				break;

			case 3:
				foreach( $this->cache as $key => $value )
					foreach( $value as $key2 => $value2 )
						foreach( $value2 as $key3 => $value3 )
						{
							if( !$value3->dbuptodate )
							{
								$query_insert[] = "('$key', '$key2', '$key3', '".$DB->escape( $value3->value )."')";
							}
						}
				break;

			default:
				return false;
		}

		if( isset($query_insert[0]) )
		{
			$query = 'REPLACE INTO '.$this->dbTableName.' ('.implode( ', ', $this->colKeyNames ).', '.$this->colValueName
								.') VALUES '.implode(', ', $query_insert);
			return (boolean)$DB->query( $query );
		}

		return false;
	}

}

/*
 * $Log$
 * Revision 1.10  2005/01/06 11:35:00  blueyed
 * Debuglog changed
 *
 * Revision 1.9  2005/01/06 05:20:14  blueyed
 * refactored (constructor), getDefaults()
 *
 * Revision 1.8  2005/01/03 06:23:47  blueyed
 * minor refactoring
 *
 * Revision 1.7  2004/12/30 14:29:42  fplanque
 * comments
 *
 * Revision 1.6  2004/12/29 02:25:55  blueyed
 * no message
 *
 * Revision 1.5  2004/11/08 02:48:26  blueyed
 * doc updated
 *
 * Revision 1.4  2004/11/08 02:23:44  blueyed
 * allow caching by column keys (e.g. user ID)
 *
 * Revision 1.3  2004/10/23 21:32:42  blueyed
 * documentation, return value
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.13  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 */
?>