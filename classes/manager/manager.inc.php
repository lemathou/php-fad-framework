<?php

/**
  * $Id: gestion.inc.php 27 2011-01-13 20:58:56Z lemathoufou $
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
 * Database gestion
 * Default object data bank
 * @author mathieu
 */
abstract class __manager
{

protected $type = "";

protected $list = array();
protected $list_detail = array();
protected $list_name = array();

// Detailled data (with type, etc.)
protected $info_detail = array // Keep at least name and label !
(
	"name"=>array("label"=>"Nom (unique)", "type"=>"string", "size"=>64, "lang"=>false),
	"label"=>array("label"=>"Label", "type"=>"string", "size"=>128, "lang"=>true),
	"description"=>array("label"=>"Description", "type"=>"text", "lang"=>true)
);
// Required info
protected $info_required = array("name");
// Data in main database table
protected $info_list = array();
// Data in lang database table
protected $info_lang_list = array();

// Retrieve all objects on first load
protected $retrieve_objects = false;
// Keep all non required info after first load
protected $retrieve_details = true;

public function info_list()
{

return $this->info_list;

}
public function info_lang_list()
{

return $this->info_lang_list;

}
public function info_detail_list()
{

return $this->info_detail;

}

/**
 * Sauvegarde/Restauration de la session
 */
function __sleep()
{

if ($this->retrieve_objects)
	return array("list_detail", "list");
else
	return array("list_detail");

}
function __wakeup()
{

$this->info_list_update();
$this->list_update();

}

protected function info_list_update()
{

$this->info_list = array();
$this->info_lang_list = array();

foreach($this->info_detail as $name=>&$info)
{
	if (isset($info["lang"]))
		if ($info["lang"] == true)
			$this->info_lang_list[] = &$info["name"];
		else
			$this->info_list[] = &$info["name"];
}

}

protected function list_update()
{

$this->list_name = array();

foreach($this->list_detail as $id=>$info)
{
	$this->list_name[$info["name"]] = $id;
}

}

/**
 * Construct the object
 * @param boolean query : Retrieve from DB otherwise file
 */
function __construct($query=true)
{

$this->info_list_update();

if ($query)
{
	$this->query_info();
	$this->construct_more();
}
elseif (false) // TODO : Restore $this->list_detail FROM a file. Mau be faster than object cache
{
	include PATH_ROOT."/include/$this->type.inc.php";
	$this->__wakeup();
}

}
protected function construct_more()
{

// To be extended if needed !

}

/**
 * Query required info
 */
function query_info($retrieve_objects=false)
{

$this->list = array();
$this->list_name = array();
$this->list_detail = array();

$query_objects = array();
$query_fields = array("`t1`.`id`");
foreach ($this->info_detail as $name=>$field)
{
	if (isset($field["lang"]))
	{
		if ($field["lang"])
			$query_fields[] = "`t2`.`$name`";
		else
			$query_fields[] = "`t1`.`$name`";
	}
	elseif ($field["type"] == "object_list")
	{
		$query_objects[] = $name;
	}
}

$query_string = "SELECT ".implode(", ", $query_fields)." FROM `_".$this->type."` as t1 LEFT JOIN `_".$this->type."_lang` as t2 ON t1.`id`=t2.`id` AND t2.`lang_id`='".SITE_LANG_ID."'";
//echo "<p>$query_string</p>\n";
$query = db()->query($query_string);
while($info = $query->fetch_assoc())
{
	$this->list_detail[$info["id"]] = $info;
	$this->list_name[$info["name"]] = $info["id"];
	foreach ($query_objects as $name)
		$this->list_detail[$info["id"]][$name] = array();
}

foreach ($query_objects as $name)
{
	$field = $this->info_detail[$name];
	$query = db()->query("SELECT `$field[db_id]`, `$field[db_field]` FROM `$field[db_table]`");
	while (list($id, $object_id)=$query->fetch_row())
	{
		if (isset($this->list_detail[$id]))
			$this->list_detail[$id][$name][] = $object_id;
	}
}

//var_dump($this->list_detail);

$this->query_info_more();

// TEMPORARY HACK so retrieve_objects() does not become silly ^^
if (!$this->retrieve_details)
{
	$rd = false;
	$this->retrieve_details = true;
}
else
	$rd = true;

if ($retrieve_objects || $this->retrieve_objects)
{
	$this->retrieve_objects();
}
// generate objects in cache in some cases to optimise website
if (!$this->retrieve_objects && !$rd && CACHE)
{
	foreach($this->list_detail as $id=>$info)
	{
		cache::store($this->type."_$id", $this->construct_object($id), CACHE_GESTION_TTL);
	}
}
if (!$rd)
{
	$this->retrieve_details = false;
	foreach ($this->list_detail as $id=>$info)
	{
		$this->list_detail[$id] = array();
		foreach ($this->info_required as $name)
			$this->list_detail[$id][$name] = $info[$name];
	}
}

if (CACHE)
	cache::store($this->type, $this, CACHE_GESTION_TTL);

}
protected function query_info_more()
{

// To be extended if needed !

}

/**
 * Constructs an object
 */
protected function construct_object($id)
{

$type = "_$this->type";

if ($this->retrieve_details)
	return new $type($id, false, $this->list_detail[$id]);
else
	return new $type($id, true);

}

/**
 * Retrieve all objects
 */
public function retrieve_objects()
{

$type = $this->type;
foreach ($this->list_detail as $id=>$info)
{
	//var_dump($info);
	if (!isset($this->list[$id]))
		$this->list[$id] = $this->construct_object($id);
}

}

/**
 * Returns an object using its ID
 * @param int $id
 */
function get($id)
{

if (!is_numeric($id))
	return null;
else
	$id = (int)$id;

if (isset($this->list[$id]))
{
	return $this->list[$id];
}
elseif (!$this->retrieve_objects && CACHE && ($object=cache::retrieve($this->type."_$id")))
{
	return $this->list[$id] = $object;
}
elseif (!$this->retrieve_objects && isset($this->list_detail[$id]))
{
	$object = $this->construct_object($id);
	if (CACHE)
		cache::store($this->type."_$id", $object, CACHE_GESTION_TTL);
	return $this->list[$id] = $object;
}

}

/**
 * Retrieve an object using its unique name
 * @param unknown_type $name
 */
function __get($name)
{

if (is_string($name) && array_key_exists($name, $this->list_name))
	return $this->get($this->list_name[$name]);

}
function get_name($name)
{

return $this->__get($name);

}

/**
 * Returns if an object exists
 * @param int $id
 */
function exists($id)
{

return (is_numeric($id) && array_key_exists($id, $this->list_detail));

}
/**
 * 
 * Returns if an object exists using its unique name
 * @param string $name
 */
function __isset($name)
{

return (is_string($name) && array_key_exists($name, $this->list_name));

}
function exists_name($name)
{

return $this->__isset($name);

}

/**
 * Returns the list
 */
public function list_get()
{

return $this->list;

}
public function list_name_get($name=null)
{

if ($name)
	return $this->list_name[$name];
else
	return $this->list_name;

}
public function list_detail_get($id=null)
{

if ($id)
	return $this->list_detail[$id];
else
	return $this->list_detail;

}

}

/**
 * Default object type
 */
abstract class __object
{

protected $_type = ""; // To be overloaded !!

protected $id = null;
protected $name = "";
protected $label = "";
protected $description = "";

public function __construct($id, $query=true, $infos=array())
{

$type = $this->_type;
$this->id = $id;

if ($query)
{
	$this->query_info();
}
else foreach ($infos as $name=>$value)
{
	if (property_exists($this, $name))
	{
		$this->{$name} = $value;
	}
}

//var_dump($this->param_list);
$this->construct_more($infos);

}
protected function construct_more($infos)
{

// To be extended if needed !

}

/**
 * Query required info
 */
function query_info()
{

$type = $this->_type;
$info_detail = $type()->info_detail_list();

$query_fields = array("`t1`.`id`");
$query_objects = array();
foreach ($info_detail as $name=>$field)
{
	if (isset($field["lang"]))
	{
		if ($field["lang"])
			$query_fields[] = "`t2`.`$name`";
		else
			$query_fields[] = "`t1`.`$name`";
	}
	elseif ($field["type"] == "object_list")
	{
		$query_objects[] = $name;
	}
}

$query_string = "SELECT ".implode(", ", $query_fields)." FROM `_$type` as t1 LEFT JOIN `_".$type."_lang` as t2 ON t1.`id`=t2.`id` AND t2.`lang_id`='".SITE_LANG_ID."' WHERE t1.id='$this->id'";
//echo "<p>$query_string</p>";
$query = db()->query($query_string);
while($infos = $query->fetch_assoc())
{
	foreach($infos as $name=>$value)
		if (isset($info_detail[$name]["type"]) && $info_detail[$name]["type"] == "fromlist")
			$this->{$name} = explode(",", $value);
		else
			$this->{$name} = $value;
}

foreach ($query_objects as $name)
{
	$this->{$name} = array();
	$field = $info_detail[$name];
	$query = db()->query("SELECT `$field[db_field]` FROM `$field[db_table]` WHERE `$field[db_id]`='$this->id'");
	while (list($object_id)=$query->fetch_row())
	{
		//var_dump($object_id);
		$this->{$name}[] = (int)$object_id;
	}
}

$this->query_info_more();

if (CACHE)
	cache::store($this->_type."_$this->id", $this, CACHE_GESTION_TTL);

}
protected function query_info_more()
{

// To be extended if needed

}

function __tostring()
{

return "[$this->_type][ID#$this->id] $this->name : $this->label";

}
public function id()
{

return $this->id;

}
public function name()
{

return $this->name;

}
public function label()
{

return $this->label;

}
public function info($name)
{

// OUlala attention... même si on est jamais à l'abri d'un var_dump !
if (property_exists($this, $name))
	return $this->{$name};

}

}

/*
 * Specific classes for admin
 */
if (ADMIN_LOAD == true)
{
	include PATH_CLASSES."/manager/admin/manager.inc.php";
}
else
{
	class _manager extends __manager {};
	class _object extends __object {};
}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
