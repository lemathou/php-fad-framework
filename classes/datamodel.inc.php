<?php

/**
  * $Id: datamodel.inc.php 30 2011-01-18 23:29:06Z lemathoufou $
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
 * Datamodel global managing class
 * 
 */
class _datamodel_gestion extends gestion
{

protected $type = "datamodel";

protected $info_list = array("name", "library_id", "perm", "dynamic", "shared");

protected $info_detail = array
(
	"name"=>array("label"=>"Nom (unique)", "type"=>"string", "size"=>64, "lang"=>false),
	"label"=>array("label"=>"Label", "type"=>"string", "size"=>128, "lang"=>true),
	"description"=>array("label"=>"Description", "type"=>"text", "lang"=>true),
	"library_id"=>array("label"=>"Librairies", "type"=>"object", "object_type"=>"library", "lang"=>false),
	"dynamic"=>array("label"=>"Dynamique", "type"=>"boolean", "lang"=>false),
	"shared"=>array("label"=>"Shared database", "type"=>"boolean", "lang"=>false) // TODO : or use a 'shared' keyword ..?
);

protected $retrieve_details = false;

function __wakeup()
{

gestion::__wakeup();
$this->access_function_create();

}

protected function construct_more()
{

$this->access_function_create();

}

/**

 * Create databank access functions
 */
protected function access_function_create()
{

foreach($this->list_name as $name=>$id)
{
	$function = "function $name(\$id=null, \$fields=array()) { return datamodel(\"$id\", \$id, \$fields); }";
	//echo "<p>$function</p>\n";
	eval($function);
}

}

}

/**
 * Modèle de données pour remplir une maquette
 *
 */
class _datamodel extends object_gestion
{

protected $_type = "datamodel";

/**
 * Library containing required objects infos
 *
 * @var integer
 */
protected $library_id = 0;

/**
 * If objects are often updated, uses a specific _update field !
 */
protected $dynamic = 0;

/**
 * Shared datamodel & library, in the shared database TODO defined in config.inc.php
 * Be carefull that the mysql user must have select/insert/update/etc. right correctly defined !
 */
protected $shared = false;
protected $db = null;

/**
 * Account default permissions.
 * set : r,i,u,d,a : read, insert, update, delete, admin
 * 
 * read : get values of an object
 * insert : add an object (so you become admin of it)
 * update : update values of an object
 * delete : delete an object
 * admin : admin the databank an so set all perm values for all
 * objects in the databank and all accounts
 * 
 * A read/insert/update/admin requires at least a read value for
 * any related required databank in the info_list.
 * 
 * Complex actions are defined by the permissions over related dataobjects.
 * For example :
 * 1) if someone can both insert messages and read contact list,
 * he (she) will be able to send an email to everybody...
 * Sending an email is no more than inserting a message linked to a contact.
 * 2) if someone has access to different stock places, he can
 * move a resource from a place to another
 * 
 * To conclude, permissions defines action poeple can do on objects,
 * and must reflect reality, so that a poeple must have access to his car,
 * etc.
 * 
 * A dataobject permission is defined in order of precision :
 * 1) databank global permissions
 * 2) dataobject specific permissions
 * 3) account global permissions
 * 4) account specific permissions
 * 
 * @var string
 */
protected $perm = "";

/**
 * Data fields : model specifications
 * 
 * @var array
 */
protected $fields = array();

protected $fields_required = array();
protected $fields_calculated = array();
protected $fields_index = array();

/*
 * Public actions / Kind of controller part of a MVC-like design
 */
protected $action_list = array();

/**
 * Objects
 */
protected $objects = array();

function __sleep()
{

return array("id", "name", "label", "description", "dynamic", "shared", "perm", "library_id", "fields", "fields_required", "fields_calculated", "fields_index");

}
function __wakeup()
{

if ($this->shared)
	$this->db = DB_SHARED_BASE;
else
	$this->db = DB_BASE;

$this->library_load();

}

protected function construct_more($infos)
{

if ($this->shared)
	$this->db = DB_SHARED_BASE;
else
	$this->db = DB_BASE;

$this->query_fields();
$this->library_load();

}

protected function construct_fields()
{

// TODO !

}

protected function query_info_more()
{

$this->query_fields();

}

/**
 * Retrieve informations about fields
 */
public function query_fields()
{

$this->fields = array();
$this->fields_required = array();
$this->fields_calculated = array();
$this->fields_index = array();
// Création des champs du datamodel
$query = db()->query("SELECT t1.pos, t1.`name` , t1.`type` , t1.`defaultvalue` , t1.`opt` , t1.`lang` , t1.`query` , t2.`label` FROM `_datamodel_fields` as t1 LEFT JOIN `_datamodel_fields_lang` as t2 ON t1.`datamodel_id`=t2.`datamodel_id` AND t1.`name`=t2.`fieldname` WHERE t1.`datamodel_id`='$this->id' ORDER BY t1.`pos`");
while ($field=$query->fetch_assoc())
{
	$datatype = "data_$field[type]";
	$this->fields[$field["name"]] = new $datatype($field["name"], json_decode($field["defaultvalue"], true), $field["label"]);
	$this->fields[$field["name"]]->datamodel_set($this->id);
	if ($field["opt"] == "required")
		$this->fields_required[] = $field["name"];
	if ($field["query"])
		$this->fields_index[] = $field["name"];
	if ($field["lang"])
		$this->fields[$field["name"]]->opt_set("lang",true);
}
$query = db()->query("SELECT `fieldname`, `opt_name`, `opt_value` FROM `_datamodel_fields_opt` WHERE `datamodel_id`='$this->id'");
while ($opt=$query->fetch_assoc())
{
	//echo "<p>$this->id : $opt[fieldname] : $opt[opt_name]</p>\n";
	//var_dump(json_decode($opt["opt_value"], true));
	$this->fields[$opt["fieldname"]]->opt_set($opt["opt_name"], json_decode($opt["opt_value"], true));
}

// TODO : Transform datamodel from string to id if possible !

$this->library_load();

}

public function __tostring()
{

return $this->label;

}

/**
 * Returns the associated library
 */
public function library()
{

if ($this->library_id)
	return library($this->library_id);
else
	return null;

}
function library_load()
{

if ($this->library_id)
{
	library($this->library_id)->load();
}

}

/**
 * Returns database name
 * in case of shared datamodel
 */
public function db()
{

return $this->db;

}

/**
 * Returns a data field
 * @param unknown_type $name
 */
public function __get($name)
{

if (isset($this->fields[$name]))
	return $this->fields[$name];

}

/**
 * Returns if a data field is defined
 * @param unknown_type $name
 */
public function __isset($name)
{

return isset($this->fields[$name]);

}

/**
 * Returns the complete data field list
 */
public function fields()
{

return $this->fields;

}
public function fields_required()
{

return $this->fields_required;

}
public function fields_calculated()
{

return $this->fields_calculated;

}
public function fields_index()
{

return $this->fields_index;

}

/**
 * Returns the list of associated actions
 */
public function action_list()
{

return $this->action_list;

}

/**
 * Permission for this page
 * Using global page perm, specific group page, and specific user page
 */
public function perm($type="")
{

$_type = $this->_type;

// Default perm (all)
$perm = new permission_info($this->perm);
// Specific perm
foreach(login()->perm_list() as $perm_id)
	$perm->update(permission($perm_id)->$_type($this->id));
// Specific perm for user
if ($account_perm=login()->user_perm($_type, $this->id))
	$perm->update($account_perm);

if ($type)
	return $perm->get($type);
else
	return $perm;

}

/**
 * returns an empty object
 * @param boolean $fields_all_init (depreacated ?)
 * @return object
 */
public function create($fields_all_init=false)
{

$classname = $this->name."_agregat";
$object = new $classname();
if ($fields_all_init) foreach($this->fields as $name=>$field)
	$object->{$name} = null;
return $object;

}

/**
 * Display a form to create a new object with default values
 * A better way is to create a new object and display the form using an object method 
 * @param $name
 */
function insert_form($name="")
{

if ($name)
	$datamodel_display = "$name";
else
	$datamodel_display = "datamodel_insert_form";

return new $datamodel_display($this, $this->fields);

}
/**
 * Insert an object in database
 *
 * @param array $fields
 * @return mixed integer boolean
 */
public function insert_from_form($fields)
{

if (!is_array($fields))
	return false;

foreach($fields as $name=>$value)
{
	if (!isset($this->fields[$name]))
		unset($fields[$name]);
	else
	{
		$field = clone $this->fields[$name];
		$field->value_from_form($value);
		$fields[$name] = $field;
	}
}
return $this->insert($fields);

}
public function insert($fields)
{

// TODO : not compatible with db_insert() !!
if ($id=$this->db_insert($fields))
	return $this->get($id);
else
	return false;

}
public function db_insert($object)
{

$fields = &$object->field_list();

// Verify required fields
//foreach ($this->fields_required as $name) if (!$fields[$name]->nonempty())
//	return false;

// Verify fields and supress keys
$query_list = array();
$query_fields = array("`id`");
$query_values = array("null");
$query_fields_lang = array();
$query_values_lang = array();
foreach ($fields as $name=>$field)
{
	// Unknown field name
	if (!isset($this->fields[$name]))
	{
		unset($fields[$name]);
	}
	// Champs complexes
	elseif ($this->fields[$name]->type == "dataobject_list")
	{
		if ($fields[$name]->nonempty())
		{
			$query_list[$name] = $field->value_to_db();
		}
	}
	elseif ($this->fields[$name]->type == "list")
	{
		if ($fields[$name]->nonempty())
		{
			$query_list[$name] = $field->value_to_db();
		}
	}
	elseif ($this->fields[$name]->type == "dataobject_select")
	{
		if (!isset($this->fields[$name]->opt["db_field"]) || !($fieldname=$this->fields[$name]->opt["db_field"]))
			$fieldname = $name;
		$query_fields[] = "`$fieldname`";
		$query_values[] = "'".db()->string_escape($field->value_to_db())."'";
	}
	// Champ simple
	else
	{
		if (!isset($this->fields[$name]->opt["db_field"]) || !($fieldname=$this->fields[$name]->opt["db_field"]))
			$fieldname = $name;
		if (isset($this->fields[$name]->opt["lang"]))
		{
			$query_fields_lang[] = "`$fieldname`";
			$query_values_lang[] = "'".db()->string_escape($field->value_to_db())."'";
		}
		else
		{
			$query_fields[] = "`$fieldname`";
			$query_values[] = "'".db()->string_escape($field->value_to_db())."'";
		}
	}
}

if ($this->dynamic)
{
	$datetime = time();
	$query_fields[] = "`_update`";
	$query_values[] = "'".date("Y-m-d H:i:s", $datetime)."'";
}

$query_str = "INSERT INTO `".$this->db."`.`".$this->name."` (".implode(", ", $query_fields).") VALUES (".implode(", ", $query_values).")";
$query = db()->query($query_str);
// db()->insert("$this->name","");

if (!($id=$query->last_id()))
	return false;

if (count($query_fields_lang))
{
	$query_str = "INSERT INTO `".$this->db."`.`".$this->name."_lang` (".implode(", ", $query_fields_lang).") VALUES (".implode(", ", $query_values_lang).")";
	$query = db()->query($query_str);
}

if (count($query_list)>0)
{
	foreach($query_list as $name=>$list)
	{
		if (in_array($this->fields[$name]->type, array("list", "dataobject_list")) && ($ref_field=$this->fields[$name]->opt("db_ref_field")) && ($ref_table=$this->fields[$name]->opt("db_ref_table")) && ($ref_id=$this->fields[$name]->opt("db_ref_id")))
		{
			$details["query_values"] = array();
			foreach ($list as $value) if (is_string($value) || is_numeric($value))
			{
				$details["query_values"][] = "('".db()->string_escape($value)."', '$id')";
			}
			if (count($details["query_values"])>0)
			{
				$query_str = "INSERT INTO `".$this->db."`.`$ref_table` (`$ref_field`, `$ref_id`) VALUES ".implode(", ", $details["query_values"]);
				$query = db()->query($query_str);
			}
		}
	}
}

$object->id = $id;
if ($this->dynamic)
	$object->_update = $datetime;

}

/**
 * Returns if an object exists with this ID
 * @param $id
 */
public function exists($id)
{

if (!is_numeric($id) || $id <= 0)
	return false;
elseif (isset($this->objects[$id]))
	return true;
elseif (CACHE && ($object=cache::retrieve("dataobject_".$this->id."_".$id)))
{
	$this->objects[$id] = $object;
	return true;
}
elseif (db()->query("SELECT 1 FROM `".$this->db."`.`".$this->name."` WHERE `id`='".db()->string_escape($id)."'")->num_rows())
	return true;
else
	return false;

}

/**
 * Remove an object
 *
 * @param unknown_type $params
 */
public function delete($params)
{

if (is_array($list=$this->db_delete($params)))
{
	if (CACHE)
		$cache_delete_list = array();
	foreach($list as $id)
	{
		if (isset($this->objects[$id]))
			unset($this->objects[$id]);
		if (CACHE)
			$cache_delete_list[] = "dataobject_".$this->id."_".$id;
	}
	if (CACHE && count($cache_delete_list))
		cache::delete($cache_delete_list);
	return true;
}
else
	return false;

}
public function db_delete($params)
{

if (!$this->db_count($params))
	return false;

$list = $this->db_select($params, array("id"));

// TODO : create a function db_select_id() for simple queries !
$delete_list_id = array();
$delete_lang = false;
foreach ($list as $fields)
{
	$delete_list_id[] = $fields["id"];
}

db()->query("DELETE FROM `".$this->db."`.`".$this->name."` WHERE `id` IN (".implode(", ", $delete_list_id).")");

foreach($this->fields as $name=>$field)
{
	if (isset($field->opt["lang"]) && $field->opt["lang"])
		$delete_lang = true;
	elseif ($field->type == "dataobject_list" || $field->type == "list")
	{
		db()->query("DELETE FROM `".$this->db."`.`".$field->opt["ref_table"]."` WHERE `".$field->opt["ref_id"]."` IN (".implode(", ", $delete_list_id).")");
	}
}

if ($delete_lang)
{
	db()->query("DELETE FROM `".$this->db."`.`".$this->name."_lang` WHERE `id` IN (".implode(", ", $delete_list_id).")");
}

return $return;

}

/**
 * Update an object in database
 * 
 * @param data[] $fields
 */
public function db_update($params=array(), $fields=array(), $sort=array(), $limit=0, $start=0)
{

$query_ok = true;

$query_base = array("update"=>array(), "having"=>array(), "where"=>array(), "from"=>array("`".$this->db."`.`$this->name` as t0"), "sort"=>array());
// Timestamp field
if ($this->dynamic)
{
	$datetime = time();
	$query_base["update"]["_update"] = "t0.`_update` = '".date("Y-m-d H:i:s", $datetime)."'";
}
$query_list = array();

foreach ($fields as $name=>$field)
{
	// Unknown field
	if (!isset($this->fields[$name]))
	{
		unset($fields[$name]);
	}
	// Bad field
	elseif (!is_a($field, "data") || get_class($this->fields[$name]) != get_class($field))
	{
		unset($fields[$name]);
	}
	// Extra table update : dataobject_list or List
	elseif ($this->fields[$name]->type == "dataobject_list" || $this->fields[$name]->type == "list")
	{
		$query_list[$name] = array();
	}
	// Primary table update
	elseif ($this->fields[$name]->type == "dataobject_select")
	{
		//print $field->value;
		if ($field->nonempty())
		{
			$query_base["update"][$fieldname=$this->fields[$name]->opt["db_databank_field"]] = "t0.`$fieldname` = '".db()->string_escape($field->value->datamodel()->name())."'";
			$query_base["update"][$fieldname=$this->fields[$name]->opt["db_field"]] = "t0.`$fieldname` = '".db()->string_escape($field->value->id)."'";
		}
		else
		{

			$query_base["update"][$fieldname=$this->fields[$name]->opt["db_databank_field"]] = "t0.`$fieldname` = NULL";
			$query_base["update"][$fieldname=$this->fields[$name]->opt["db_field"]] = "t0.`$fieldname` = NULL";
		}
	}
	// Primary table update
	else
	{
		if (!isset($this->fields[$name]->opt["db_field"]) || !($fieldname = $this->fields[$name]->opt["db_field"]))
			$fieldname = $name;
		if (($value = $fields[$name]->value_to_db()) !== null)
			$value = "'".db()->string_escape($value)."'";
		else
			$value = "NULL";
		if (isset($fields[$name]->opt["lang"]))
		{
			$query_base["lang"] = true;
			$query_base["update"][$name] = "t1.`$fieldname` = $value";
		}
		else
		{
			$query_base["update"][$name] = "t0.`$fieldname` = $value";
		}
	}
}

$this->query_params($params, $query_base);

// Where
if (count($query_base["where"]) > 1)
	$query_where = "WHERE ".$this->query_where_constr($query_base["where"]);
else
	$query_where = "";
// Having
if (count($query_base["having"]))
	$query_having = "HAVING ".implode(" AND ", $query_base["having"]);
else
	$query_having = "";
// Sort
if (count($sort)>0)
{
	foreach($sort as $name=>$order)
		if (isset($this->fields[$name]) || $name == "relevance")
		{
			if (strtolower($order) == "desc")
				$query_base["sort"][] = "`$name` DESC";
			else
				$query_base["sort"][] = "`$name` ASC";
		}
}
if (count($query_base["sort"]))
	$query_sort = "ORDER BY ".implode(",", $query_base["sort"]);
else
	$query_sort = "";
// Limit
if (is_numeric($limit) && $limit>0 && is_numeric($start) && $start>=0)
	$query_limit = " LIMIT $start, $limit";
else
	$query_limit = "";

// Test number
// TODO
if (false)
{
	
}

// Primary query

$return = false;
if (count($query_base["update"])>0)
{
	$query_string = "UPDATE ".implode(", ", $query_base["from"])." SET ".implode(", ", $query_base["update"])." $query_where $query_having $query_sort $query_limit";
	//echo "<p>$query_string</p>\n";
	$query = db()->query($query_string);
	if ($query->affected_rows())
	{
		$return = true;
	}
	//echo mysql_error();
}

// Other queries

// TODO : problem if the changes to do are checked in params : the other queries will not check all the good rows (some may disappear from the whery) !!
// So try first to count objects and if there are not too much, retrive list_id and use it...

foreach($query_list as $name=>$insert_list)
{
	if (($this->fields[$name]->type == "dataobject_list" || $this->fields[$name]->type == "list") && ($ref_field=$this->fields[$name]->opt("db_ref_field")) && ($ref_table=$this->fields[$name]->opt("db_ref_table")) && ($ref_id=$this->fields[$name]->opt("db_ref_id")))
	{
		if ($query_where)
			$query_string = "DELETE `".$this->db."`.`$ref_table` FROM ".implode(", ", array_merge(array("`$ref_table`"), $query_base["from"]))." $query_where AND `$ref_table`.`$ref_id`=t0.`id` $query_having $query_sort $query_limit";
		else
			$query_string = "DELETE `".$this->db."`.`$ref_table` FROM ".implode(", ", array_merge(array("`$ref_table`"), $query_base["from"]))." WHERE `$ref_table`.`$ref_id`=t0.`id` $query_having $query_sort $query_limit";
		//echo "<p>$query_string</p>\n";
		db()->query($query_string);
		//echo mysql_error();
		if ($fields[$name]->nonempty())
		{
			foreach($fields[$name]->value as $id)
			{
				$query_string = "INSERT INTO `".$this->db."`.`$ref_table` (`$ref_id`, `$ref_field`) SELECT t0.`id`, '".db()->string_escape($id)."' FROM ".implode(", ", $query_base["from"])." $query_where $query_having $query_sort $query_limit";
				//echo "<p>$query_string</p>\n";
				db()->query($query_string);
				//echo mysql_error();
			}
			$return = true;
		}
	}
	elseif ($this->fields[$name]->type == "dataobject_list" && ($ref_id=$this->fields[$name]->opt("db_ref_id")) && ($datamodel=datamodel($this->fields[$name]->opt("datamodel"))))
	{
		$query_string = "SELECT t0.`id` FROM ".implode(", ", $query_base["from"])." $query_where $query_having $query_sort $query_limit";
		$query = db()->query($query_string);
		// Only 1 record, otherwise unicity problem !
		if ($query->num_rows() == 1)
		{
			list($id) = $query->fetch_row();
			// TODO : Update object cache
			$query_string = "UPDATE `".$datamodel->db_table()."`.`".$datamodel->name()."` SET `$ref_id`=NULL WHERE `$ref_id`='$id'";
			//echo "<p>$query_string</p>\n";
			db()->query($query_string);
			if ($fields[$name]->nonempty())
			{
				$query_string = "UPDATE `".$datamodel->db_table()."`.`".$datamodel->name()."` SET `$ref_id`='$id' WHERE `id` IN ('".implode("', '", $fields[$name]->value)."')";
				//echo "<p>$query_string</p>\n";
				db()->query($query_string);
				//echo mysql_error();
				$return = true;
			}
		}
	}
}

return $return;

}

/**
 * alias of db_get()
 * 
 * @param array $query
 * @return mixed
 */
public function query($params=array(), $fields=array(), $sort=array(), $limit=0, $start=0)
{

return $this->db_get($params, $fields, $sort, $limit, $start);

}
/**
 * Retrieve a list of objects by giving query params and fields to be retrieved
 * The function adds the required fields
 * 
 * @param unknown_type $query_where
 */
public function db_get($params=array(), $fields=array(), $sort=array(), $limit=0, $start=0)
{

if (!is_array($result=$this->db_select($params, "id", $sort, $limit, $start)) || !count($result))
{
	return array();
}

$objects_order = array();
$objects = array();
$db_retrieve_list = array();
$cache_retrieve_list = array();
//$cache_store_list = array(); // For a future

foreach($result as $nb=>$o)
{
	$id = $o["id"];
	if (isset($this->objects[$id]))
	{
		$objects[$nb] = $this->objects[$id];
	}
	elseif (CACHE)
	{
		$objects[$nb] = null;
		$cache_retrieve_list[$id] = "dataobject_".$this->id."_".$id;
	}
	else
	{
		$objects[$nb] = null;
		$db_retrieve_list[] = $id;
	}
	$objects_order[$id] = $nb;
}

// Retrieve from cache
if (count($cache_retrieve_list))
{
	$cache_list = cache::retrieve($cache_retrieve_list);
	foreach($cache_retrieve_list as $id=>$cache_id)
	{
		if (isset($cache_list[$cache_id]))
		{
			$objects[$objects_order[$id]] = $this->objects[$id] = $cache_list[$cache_id];
		}
		else
		{
			$db_retrieve_list[] = $id;
		}
	}
}

// Retrieve from database
if (count($db_retrieve_list))
{
	$params = array( array("name"=>"id", "value"=>$db_retrieve_list) );
	if (is_array($result=$this->db_select($params, true)))
	{
		foreach($result as $o)
		{
			$id = $o["id"];
			$object = $this->create();
			$object->update_from_db($o);
			$objects[$objects_order[$id]] = $this->objects[$id] = $object;
			// TODO : store all values in one time
			if (CACHE)
				cache::store("dataobject_".$this->id."_".$id, $object, CACHE_DATAOBJECT_TTL);
		}
	}
}

return $objects;

}
/**
 * Retrieve an objet from the datamodel.
 * Optimized version of db_get() for only one object
 * 
 * @param integer $id
 * @return mixed 
 */
public function get($id, $fields=array())
{

// Permissinon check
if (false && !$this->perm("r"))
{
	if (DEBUG_DATAMODEL)
		trigger_error("Databank $this->name : Permission error : Read access denied");
	return false;
}

// Parameters check
if (!is_numeric($id) || $id <= 0 || !is_array($fields))
	return false;

// OK
if (isset($this->objects[$id]))
{
	return $this->objects[$id];
}
elseif (CACHE && ($object=cache::retrieve("dataobject_".$this->id."_".$id)))
{
	return $this->objects[$id] = $object;
}
elseif (is_array($info_list=$this->db_select(array(array("name"=>"id", "value"=>$id)), true)) && count($info_list)==1)
{
	$o = array_pop($info_list);
	$id = $o["id"];
	$object = $this->create();
	$object->update_from_db($o);
	if (CACHE)
		cache::store("dataobject_".$this->id."_".$id, $object, CACHE_DATAOBJECT_TTL);
	return $this->objects[$id] = $object;
}
else
{
	return null;
}

}
/**
 * Retrieve an array containing each the one an array of fields to be retrieved by giving query params
 * Deprecated ?
 * 
 * @param unknown_type $query_where
 */
public function db_fields($params=array(), $fields=array(), $sort=array(), $limit=0, $start=0)
{

// Retrieve the resulting fields
if (is_array($result = $this->db_select($params, $fields, $sort, $limit, $start)))
{
	$return = array();
	// Fields for each object
	foreach($result as $list)
	{
		$elm = array();
		foreach($list as $name=>$value)
		{
			$elm[$name] = clone $this->fields[$name];
			$elm[$name]->value_from_db($value);
		}
		$return[] = $elm;
	}
	return $return;
}
else
	return false;

}

/**
 * Retrieve a complete field value list by giving query params and fields to be retrieved
 * 
 * @param unknown_type $query_where
 */
public function db_select($params=array(), $fields_input=array(), $sort=array(), $limit=0, $start=0)
{

if ($params === true)
{
	$params = array();
}
elseif (!is_array($params))
{
	if (DEBUG_DATAMODEL)
		trigger_error("datamodel(#ID$this->id)->db_select() : incorrect params.");
	return false;
}

// Requete sur la table principale
$query_base = array("fields"=>array("t0.`id`"), "having"=>array(), "where"=>array(), "join"=>array(), "groupby"=>array(), "from"=>array("`".$this->db."`.`$this->name` as t0"), "sort"=>array(), "table_nb"=>1, "lang"=>array());
if ($this->dynamic)
	$query_base["fields"][] = "t0.`_update`";

$fields_explode = array();

$fields = array();
// Verify fields to be retrieved :
foreach($this->fields as $name=>$field)
{
	// Add fields
	if ($fields_input === true || (is_array($fields_input) && in_array($name, $fields_input)) || (is_string($fields_input) && $name == $fields_input))
	{
		$fields[] = $name;
		if ($field->type == "dataobject_select")
		{
			$fieldname_1 = $field->opt["db_databank_field"];
			$fieldname_2 = $field->opt["db_field"];
			$query_base["fields"][] = "CONCAT(t0.`$fieldname_1`,',',t0.`$fieldname_2`) as `$name`";
		}
		elseif (($field->type == "dataobject_list" || $field->type == "list") && ($ref_field=$field->opt["db_ref_field"]) && ($ref_id=$field->opt["db_ref_id"]) && ($ref_table=$field->opt["db_ref_table"]))
		{
			$query_base["table_nb"]++;
			$tablename = "t".$query_base["table_nb"];
			$query_base["join"][] = "`".$this->db."`.`$ref_table` as $tablename ON t0.`id` = $tablename.`$ref_id`";
			if (!count($query_base["groupby"]))
				$query_base["groupby"][] = "t0.`id`";
			$query_base["fields"][] = "CAST(GROUP_CONCAT(DISTINCT QUOTE($tablename.`$ref_field`) SEPARATOR ',') as CHAR) as `$name`";
			$fields_explode[] = $name;
		}
		elseif ($this->fields[$name]->type == "dataobject_list" && ($ref_id=$this->fields[$name]->opt("db_ref_id")) && ($datamodel=datamodel($this->fields[$name]->opt("datamodel"))))
		{
			$query_base["table_nb"]++;
			$tablename = "t".$query_base["table_nb"];
			$query_base["join"][] = "`".$datamodel->db()."`.`".$datamodel->name()."` as $tablename ON t0.`id` = $tablename.`$ref_id`";
			if (!count($query_base["groupby"]))
				$query_base["groupby"][] = "t0.`id`";
			$query_base["fields"][] = "CAST(GROUP_CONCAT(DISTINCT QUOTE($tablename.`id`) SEPARATOR ',') as CHAR) as $name";
			$fields_explode[] = $name;
			
		}
		else
		{
			// Rare case...
			/*
			if (isset($field->db_opt["table"]) && ($tablename = $field->db_opt["table"]))
				$query_base["join"][] = "`$tablename`";
			*/
			if (!($fieldname=$field->opt("db_field")))
				$fieldname = $name;
			if ($field->opt("lang"))
			{
				$query_base["lang"][0] = false;
				$query_base["fields"][] = "t1.`$fieldname` as `$name`";
			}
			else
				$query_base["fields"][] = "t0.`$fieldname` as `$name`";
		}
	}
}

$this->query_params($params, $query_base);

//var_dump($query_base["where"]);
//var_dump($query_base["from"]);

/* Primary query */

// Where
if (count($query_base["where"]) > 1)
{
	$query_where = "WHERE ".$this->query_where_constr($query_base["where"]);
}
else
	$query_where = "";
// Having
if (count($query_base["having"]))
	$query_having = "HAVING ".implode(" AND ", $query_base["having"]);
else
	$query_having = "";
// Join
if (count($query_base["join"]))
	$query_join = "LEFT JOIN ".implode(" LEFT JOIN ", $query_base["join"]);
else
	$query_join = "";
// Group By
if (count($query_base["groupby"]))
	$query_groupby = "GROUP BY ".implode(", ", $query_base["groupby"]);
else
	$query_groupby = "";
// Sort
if (count($sort)>0)
{
	foreach($sort as $name=>$order) if (isset($this->fields[$name]) || $name == "relevance")
	{
		if (strtolower($order) == "desc")
			$query_base["sort"][] = "`$name` DESC";
		else
			$query_base["sort"][] = "`$name` ASC";
	}
}
if (count($query_base["sort"]))
	$query_sort = "ORDER BY ".implode(",", $query_base["sort"]);
else
	$query_sort = "";
// Limit
if (is_numeric($limit) && $limit>0 && is_numeric($start) && $start>=0)
	$query_limit = " LIMIT $start, $limit";
else
	$query_limit = "";

$query_string = "SELECT DISTINCT ".implode(" , ",$query_base["fields"])." FROM ".implode(" , ",$query_base["from"])." $query_join $query_where $query_having $query_groupby $query_sort $query_limit";
//echo "<p>$query_string</p>";

// Effective Query
$query = db()->query($query_string);

echo mysql_error();

if ($query->num_rows() == 0)
	return array();

// Retrieve values
$return = array();
while ($row=$query->fetch_assoc())
{
	foreach($fields_explode as $name)
	{
		if ($row[$name] == "NULL")
		{
			$row[$name] = array();
		}
		else
		{
			$row[$name] = explode("','",substr($row[$name], 1, -1));
			if ($this->fields[$name]->type == "list")
				foreach($row[$name] as $i=>$value)
					$row[$name][$i] = stripslashes($value);
			elseif ($this->fields[$name]->type == "dataobject_list")
				foreach($row[$name] as $i=>$value)
					$row[$name][$i] = (int)$value;
		}
	}
	$return[] = $row;
}

return $return;

}

/**
 * Returns the number of objects corresponding to the given param list
 * @param unknown_type $params
 */
public function count($params=array())
{

return $this->db_count($params);

}
public function db_count($params=array())
{

if (!is_array($params))
{
	return false;
}

// Requete sur la table principale
$query_base = array ("where"=>array(), "from"=>array("`".$this->db."`.`".$this->name."` as t0"), "having"=>array(), "lang"=>array());
// Autres requetes
$query_list = array();
// Result
$return = array();
// Result params mapping
$return_params = array();

$this->query_params($params, $query_base);

//var_dump($query_base["where"]);
//die();

// Primary query

// Where
if (count($query_base["where"]) > 1)
	$query_where = "WHERE ".$this->query_where_constr($query_base["where"]);
else
	$query_where = "";
// Having
if (count($query_base["having"]))
	$query_having = "HAVING ".implode(" AND ", $query_base["having"]);
else
	$query_having = "";

$query_string = "SELECT COUNT(*) FROM ".implode(", ", $query_base["from"])." $query_where $query_having";
// echo "<p>$query_string</p>\n";

return array_pop(db()->query($query_string)->fetch_row());

}

/**
 * Constructs the query with specific params...
 * Used in functions db_select(), db_count() and db_update().
 */
public function query_params(&$params, &$query_base, &$where_list=null, $t0nb=0)
{

if (!isset($query_base["table_nb"]))
	$query_base["table_nb"] = 1;
if (!isset($query_base["lang"][$t0nb]))
	$query_base["lang"][$t0nb] = false;
if (!isset($query_base["lang_ok"][$t0nb]))
	$query_base["lang_ok"][$t0nb] = false;
// List to update
if (!is_array($where_list))
	$where_list = &$query_base["where"];

if (!isset($params["_type"]) || !is_string($params["_type"]) || !in_array(strtoupper($params["_type"]), array("OR", "AND")))
	$where_list["_type"] = "AND";
else
	$where_list["_type"] = strtoupper($params["_type"]);

foreach($params as $param_nb=>$param)
{
	// Recursion
	if (is_array($param) && isset($param["_type"]) && ($param["_type"] == "OR" || $param["_type"] == "AND"))
	{
		$list = array();
		$this->query_params($param, $query_base, $list, $t0nb);
		$where_list[] = $list;
	}
	// Bad param
	elseif (!is_array($param) || !isset($param["value"]))
	{
		unset($params[$param_nb]);
	}
	// ID Field
	elseif (isset($param["name"]) && $param["name"] == "id")
	{
		if (!isset($param["type"]))
			$param["type"] = "";
		$data_id = new data_id();
		$where_list[] = "t$t0nb.".$data_id->db_query_param($param["value"], $param["type"]);
	}
	// UPDATE Field
	elseif (isset($param["name"]) && $param["name"] == "_update")
	{
		if (!isset($param["type"]))
			$param["type"] = "";
		$data_datetime = new data_datetime("_update", null, "datetime");
		$where_list[] = "t$t0nb.".$data_datetime->db_query_param($param["value"], $param["type"]);
	}
	// User Field
	elseif (isset($param["name"]) && isset($this->fields[$param["name"]]))
	{
		if (!isset($param["type"]))
			$param["type"] = "";
		$field = $this->fields[$param["name"]];
		// Champs "spéciaux"
		if ($field->type == "dataobject_select")
		{
			$where_list[] = "t$t0nb.`".$field->opt["db_databank_field"]."` = '".db()->string_escape($param["value"])."'";
		}
		elseif ($field->type == "dataobject" && $param["type"] == "object")
		{
			$query_base["table_nb"]++; // incrementation for the main table
			$tablename = "t".$query_base["table_nb"];
			$query_base["from"][] = "`".datamodel($field->opt["datamodel"])->db()."`.`".datamodel($field->opt["datamodel"])->name()."` as $tablename";
			$query_base["table_nb"]++; // incrementation for the eventual 'lang' table
			$list = array();
			datamodel($field->opt["datamodel"])->query_params($param["value"], $query_base, $list, ($query_base["table_nb"]-1));
			$where_list[] = array
			(
				"_type"=>"AND",
				"t$t0nb.`".$param["name"]."` = $tablename.`id`",
				$list
			);
		}
		elseif ($field->type == "dataobject_list" && $param["type"] == "object")
		{
			$ref_id = $field->opt["db_ref_id"];
			$ref_table = $ref_field = "";
			if (($ref_table=$field->opt["db_ref_table"]) && ($ref_field=$field->opt["db_ref_field"]))
			{
				$query_base["table_nb"]++; // incrementation for the join table
				$tablename_ref = "t".$query_base["table_nb"];
				$query_base["from"][] = "`".$this->db."`.`$ref_table` as $tablename_ref";
			}
			$query_base["table_nb"]++; // incrementation for the main table
			$tablename = "t".$query_base["table_nb"];
			$query_base["from"][] = "`".datamodel($field->opt["datamodel"])->db()."`.`".datamodel($field->opt["datamodel"])->name()."` as $tablename";
			$list = array();
			datamodel($field->opt["datamodel"])->query_params($param["value"], $query_base, $list, ($query_base["table_nb"]-1));
			if ($ref_table && $ref_field)
				$where_list[] = array
				(
					"_type"=>"AND",
					"t$t0nb.`id` = $tablename_ref.`$ref_id`",
					"$tablename_ref.`$ref_field` = $tablename.`id`",
					$list
				);
			else
				$where_list[] = array
				(
					"_type"=>"AND",
					"t$t0nb.`id` = $tablename.`id`",
					$list
				);
		}
		elseif ($field->type == "dataobject_list" || $field->type == "list")
		{
			if (!($ref_table=$field->opt["db_ref_table"]))
				$ref_table = $field->opt["datamodel"]->name();
			if (!($ref_field=$field->opt["db_ref_field"]))
				$ref_field = "id";
			$query_base["table_nb"]++;
			$tablename = "t".$query_base["table_nb"];
			$query_base["from"][] = "`".$this->db."`.`$ref_table` as $tablename";
			$where_list[] = "t$t0nb.`id` = $tablename.`".$field->opt["db_ref_id"]."`";
			if (is_array($param["value"])) // TODO : string_escape !
				$where_list[] = "$tablename.`$ref_field` IN ('".implode("', '",$param["value"])."')";
			else
				$where_list[] = "$tablename.`$ref_field` = '".db()->string_escape($param["value"])."'";
		}
		// Champ "standard"
		else
		{
			if (isset($field->opt["lang"]) && $field->opt["lang"])
			{
				$query_base["lang"][$t0nb] = true;
				$where_list[] = "t".($t0nb+1).".".$field->db_query_param($param["value"], $param["type"]);
			}
			else
			{
				$where_list[] = "t$t0nb.".$field->db_query_param($param["value"], $param["type"]);
			}
		}
	}
	// Query using fulltext index
	elseif (count($this->fields_index))
	{
		if (!isset($param["type"]) || $param["type"] == "like")
		{
			$l = array("type"=>"OR");
			foreach($this->fields_index as $i)
				$l[] = "t$t0nb.`$i` LIKE '%".db()->string_escape($param["value"])."%'";
			$where_list[] = $l;
		}
		else //if ($param["type"] == "fulltext")
		{
			$query_base["fields"][] = "MATCH(t$t0nb.`".implode("`, t$t0nb.`", $this->fields_index)."`) AGAINST('".db()->string_escape($param["value"])."') as `relevance`";
			$query_base["having"][] = "relevance > 0";
		}
	}
	// No index and no fields specified
	else
	{
		unset($params[$param_nb]);
	}
}

// Lang
if ($query_base["lang"][$t0nb] == true && !$query_base["lang_ok"][$t0nb])
{
	$query_base["from"][] = "`".$this->db."`.`".$this->name."_lang` as t".($t0nb+1);
	$where_list[] = "t$t0nb.`id` = t".($t0nb+1).".`id`";
	$where_list[] = "t".($t0nb+1).".`lang_id` = '".SITE_LANG_ID."'";
	$query_base["lang_ok"][$t0nb] = true;
}

}

protected function query_where_constr($list)
{

foreach($list as $nb=>$str)
{
	// Récursion
	if (is_numeric($nb) && is_array($str))
	{
		if ($str=$this->query_where_constr($str))
			$list[$nb] = $str;
		else
			unset($list[$nb]);
	}
	// On vire ce qui n'est pas bon
	elseif (!is_string($str))
		unset($list[$nb]);
}

// On a construit de façon à ce que cela y soit
$type = $list["_type"];
unset($list["_type"]);

if (count($list))
	return "(".implode(" $type ", $list).")";
else
	return null;

}

/**
 * 
 * Enter description here ...
 * @param array $params
 * @param mixed $fields
 * @param array $order
 * @param integer $limit
 */
function json_query($params=array(), $fields=true, $order=array(), $limit=0)
{

$query = $this->query($params, $fields, $order, $limit);

$o = array();
foreach ($query as $object)
{
	$field_list = array();
	foreach ($this->fields as $i=>$field)
	{
		if ($fields === "1" || (is_array($fields) && in_array($i, $fields)))
		{
			if ($object->{$i}->value === null)
				$field_list[] = "\"$i\":null";
			elseif ($object->{$i}->value === true)
				$field_list[] = "\"$i\":true";
			elseif ($object->{$i}->value === false)
				$field_list[] = "\"$i\":false";
			elseif (is_numeric($object->{$i}->value))
				$field_list[] = "\"$i\":".json_encode($object->{$i}->value);
			else
				$field_list[] = "\"$i\":".json_encode($object->{$i}->value);
		}
	}
	$o[] = "{\"id\":".$object->id.", \"datamodel_id\":".$this->id.", \"value\":".json_encode((string)$object).", \"fields\":{".implode(", ", $field_list)."}}";
}

return "[".implode(", ",$o)."]";

}

/**
 * Display a Select form
 * @param $params
 * @param $url
 * @param $varname
 */
function select_form($params=array(), $url="", $varname="id")
{

if (!$this->perm("l"))
{
	trigger_error("Databank $this->name : list acces denied in select form");
	return;
}

?>
<form method="get" action="" class="datamodel_form <?php echo $this->name; ?>_form">
<p>Formulaire de sélection&nbsp;:&nbsp;<select name="id" onchange="document.location.href = '<?php echo SITE_BASEPATH."/$url?$varname="; ?>'+this.value;">
<option value="">-- Choisir --</option>
<?
$objects = $this->db_get($params);
foreach ($objects as $object)
{
	if (isset($_GET["id"]) && $_GET["id"] == "$object->id")
		print "<option value=\"$object->id\" selected>$object</option>";
	else
		print "<option value=\"$object->id\">$object</option>";
}
?>
</select></p>
</form>
<?

}

}


/*
 * Specific classes for admin
 */
if (ADMIN_LOAD == true)
{
	include PATH_FRAMEWORK."/classes/admin/datamodel.inc.php";
}
else
{
	class datamodel_gestion extends _datamodel_gestion {};
	class datamodel extends _datamodel {};
}

include PATH_FRAMEWORK."/classes/dataobject.inc.php";
include PATH_FRAMEWORK."/classes/data_display.inc.php";


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>