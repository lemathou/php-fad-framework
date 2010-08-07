<?

/**
  * $Id: globals.inc.php 59 2009-03-03 15:48:26Z mathieu $
  * 
  * � Copyright 2008 Mathieu Moulin - iProspective - lemathou@free.fr �
  * 
  * This file is part of FTNGroupWare.
  * 
  */

if (DEBUG_GENTIME ==  true)
	gentime(__FILE__." [begin]");

class globals
{

protected $list = array();

function __construct()
{

$this->query();
	
}

function query()
{

$query = db()->query( "SELECT `name` , `value` FROM `_globals`" );
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

//

function globals()
{

if (!isset($GLOBALS["globals"]))
	$GLOBALS["globals"] = $_SESSION["globals"] = new globals();

return $GLOBALS["globals"];
	
}

if (DEBUG_GENTIME ==  true)
	gentime(__FILE__." [end]");

?>