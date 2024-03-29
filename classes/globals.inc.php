<?php

/**
  * $Id$
  * 
  * Copyright 2008-2011 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * http://sourceforge.net/projects/phpfadframework/
  * Licence : http://www.gnu.org/copyleft/gpl.html  GNU General Public License
  * 
  */

if (DEBUG_GENTIME == true)
	gentime(__FILE__." [begin]");


/**
 * Common necessary or useful project data
 * @author mathieu
 *
 */
class _globals
{

protected $_db = DB_FW_PROJECT_BASE;

protected $list = array();

function __construct()
{

$this->query();
	
}

function query()
{

$query = db()->query( "SELECT `name` , `value` FROM `$this->_db`._globals`" );
while (list($name, $value) = $query->fetch_row())
	$this->list[$name] = $value;

}

function get($name)
{

if (isset($this->list[$name]))
	return $this->list[$name];
	
}

function get_list()
{
	
return $this->list;

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
