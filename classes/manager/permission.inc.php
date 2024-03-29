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
 * Global managing object for permissions
 * 
 * @author mathieu
 * 
 */
class __permission_manager extends _manager
{

protected $type = "permission";

protected $retrieve_objects = false;
protected $retrieve_details = false;

protected static $perm_list = array("l"=>"List", "r"=>"Read", "i"=>"Insert", "u"=>"Update", "d"=>"Delete", "a"=>"Admin");

public static function perm_list()
{

return self::$perm_list;
	
}

protected function query_info_more()
{

$query = db()->query("SELECT `perm_id`, `datamodel_id`, `perm` FROM `_datamodel_perm_ref`");
while (list($perm_id, $datamodel_id, $perm) = $query->fetch_row())
	$this->list_detail[$perm_id]["datamodel_perm"][$datamodel_id] = $perm;

$query = db()->query("SELECT `perm_id`, `datamodel_id`, `object_id`, `perm` FROM `_dataobject_perm_ref`");
while (list($perm_id, $datamodel_id, $object_id, $perm) = $query->fetch_row())
	$this->list_detail[$perm_id]["dataobject_perm"][$datamodel_id][$object_id] = $perm;

$query = db()->query("SELECT `perm_id`, `library_id` FROM `_library_perm_ref`");
while (list($perm_id, $library_id, $perm) = $query->fetch_row())
	$this->list_detail[$perm_id]["library_perm"][$library_id] = array();

/*
$query = db()->query("SELECT `perm_id`, `template_id` FROM `_template_perm_ref`");
while (list($perm_id, $template_id, $perm) = $query->fetch_row())
	$this->list_detail[$perm_id]["template_perm"][$template_id] = array();
*/

$query = db()->query("SELECT `perm_id`, `page_id` FROM `_page_perm_ref`");
while (list($perm_id, $page_id) = $query->fetch_row())
	$this->list_detail[$perm_id]["page_perm"][$page_id] = array();

$query = db()->query("SELECT `perm_id`, `menu_id` FROM `_menu_perm_ref`");
while (list($perm_id, $menu_id, $perm) = $query->fetch_row())
	$this->list_detail[$perm_id]["menu_perm"][$menu_id] = array();

}

}

/**
 * Permissions
 */
class __permission extends _object
{

protected $_type = "permission";

protected $library_perm = array();
protected $datamodel_perm = array();
protected $dataobject_perm = array();
protected $template_perm = array();
protected $page_perm = array();
protected $menu_perm = array();

function __sleep()
{

return array("id", "name", "label", "description", "library_perm", "datamodel_perm", "dataobject_perm", "template_perm", "page_perm", "menu_perm");

}

protected function query_info_more()
{

$this->query_perm();

}

public function query_perm()
{

$this->datamodel_perm = array();
$query = db()->query("SELECT `datamodel_id`, `perm` from `_datamodel_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($datamodel_id, $perm) = $query->fetch_row())
	$this->datamodel_perm[$datamodel_id] = $perm;

$this->dataobject_perm = array();
$query = db()->query("SELECT `datamodel_id`, `object_id`, `perm` from `_dataobject_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($datamodel_id, $object_id, $perm) = $query->fetch_row())
	$this->dataobject_perm[$datamodel_id][$object_id] = $perm;

$this->library_perm = array();
$query = db()->query("SELECT `library_id` from `_library_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($library_id, $perm) = $query->fetch_row())
	$this->library_perm[$library_id] = array();

/*
$this->template_perm = array();
$query = db()->query("SELECT `template_id` from `_template_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($template_id, $perm) = $query->fetch_row())
	$this->template_perm[$template_id] = array();
*/

$this->page_perm = array();
$query = db()->query("SELECT `page_id` from `_page_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($page_id) = $query->fetch_row())
	$this->page_perm[$page_id] = array();

$this->menu_perm = array();
$query = db()->query("SELECT `menu_id` from `_menu_perm_ref` WHERE `perm_id` = '$this->id'");
while (list($menu_id) = $query->fetch_row())
	$this->menu_perm[$menu_id] = array();

}

function datamodel($id=null, $action=null)
{

if (is_numeric($id))
{
	if (isset($this->datamodel_perm[$id]))
		if (is_string($action) && isset($this->datamodel_perm[$id][$action]))
			return $this->datamodel_perm[$id][$action];
		else
			return $this->datamodel_perm[$id];
	else
		return false;
}
else
	return $this->datamodel_perm;

}

function dataobject($datamodel_id=null, $object_id=null, $action=null)
{

if (isset($this->dataobject_perm[$datamodel_id][$object_id]))
	return $this->dataobject_perm[$datamodel_id][$object_id];
else
	return false;

}

function template($id=null)
{

if (is_numeric($id))
{
	if (isset($this->template_perm[$id]))
		return $this->template_perm[$id];
	else
		return false;
}
else
	return $this->template_perm;

}

function page($id=null, $action=null)
{

if (is_numeric($id))
{
	if (isset($this->page_perm[$id]))
		if (is_string($action) && isset($this->page_perm[$id][$action]))
			return $this->page_perm[$id][$action];
		else
			return $this->page_perm[$id];
	return false;
}
else
	return $this->page_perm;

}

function menu($id)
{

if (is_numeric($id))
{
	if (isset($this->menu_perm[$id]))
		return $this->menu_perm[$id];
	else
		return false;
}
else
	return $this->menu_perm;

}

}

/**
 * Object used to retrieve permissions
 * Used for datamodels and dataobjects essentially for basic use
 * but also for pages actions
 */
class permission_info
{

protected $list = array
(
	"i"=>false,
	"l"=>false,
	"r"=>false,
	"u"=>false,
	"d"=>false,
	"a"=>false
);

function get($name)
{

if (isset($this->list[$name]))
	return $this->list[$name];
else
	return null;

}

function __construct($list=null)
{

$this->update($list);

}

function update($list)
{

if (is_array($list))
{
	foreach($this->list as $i=>$j)
	{
		if (in_array($i, $list))
			$this->list[$i] = true;
	}
}
elseif (is_string($list))
{
	foreach($this->list as $i=>$j)
	{
		if (strpos($list, $i) !== false)
			$this->list[$i] = true;
	}
}

}
function update_str($list)
{

if (is_string($list))
	foreach($this->list as $i=>$j)
		if (strpos($list, "+$i") !== false)
			$this->list[$i] = true;
		elseif (strpos($list, "-$i") !== false)
			$this->list[$i] = false;

}

function __tostring()
{

$return = "";
foreach ($this->list as $i=>$j)
{
	if ($j)
		$return .= "$i";
}
return $return;

}

function perm_list()
{

return $this->list;

}

}


/*
 * Specific classes for admin
 */
if (ADMIN_LOAD == true)
{
	include PATH_CLASSES."/manager/admin/permission.inc.php";
}
else
{
	class _permission_manager extends __permission_manager {};
	class _permission extends __permission {};
}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
