<?php

/**
  * $Id: dataobject.inc.php 30 2011-01-18 23:29:06Z lemathoufou $
  * 
  * Copyright 2008-2011 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * http://sourceforge.net/projects/phpfadframework/
  * Licence : http://www.gnu.org/copyleft/gpl.html  GNU General Public License
  * 
  * Data Objects (agregat)
  * 
  * Object corresponding to a given Datamodel specification.
  * Contains the data fields of the datamodel.
  * Can be
  * - upgraded,
  * - displayed,
  * - etc.
  * 
  */

if (DEBUG_GENTIME == true)
	gentime(__FILE__." [begin]");


/**
 * Agrégats de données
 *
 */
class dataobject
{

/**
 * Datamodel specifications
 * NEEDS to be overloaded !!
 * 
 * @var integer
 */
protected $datamodel_id=0;

/**
 * Data fields
 * 
 * @var array
 */
protected $id=0;
protected $_update = null;

protected $fields = array();
protected $field_values = array();

/**
 * Form, display, etc. options
 * 
 * @var array
 */
protected $options = array();

public function __sleep()
{

// TODO : Find a solution if the update had not to be saved !
foreach ($this->fields as $name=>$field)
	if ($field->value !== $this->field_values[$name])
		$this->field_values[$name] = $field->value;

return array("id", "_update", "field_values");

}
public function __wakeup()
{

$this->datamodel_find();

foreach ($this->field_values as $name=>$value)
{
	$this->fields[$name] = clone datamodel($this->datamodel_id)->{$name};
	$this->fields[$name]->value = $value;
}

}

/**
 * 
 * @param $id
 * @param $fields
 */
function __construct($id=null, $fields=array())
{

$this->datamodel_find();
$this->datamodel_set();
if (is_numeric($id) && $id>0)
{
	//$this->id = $id;
	// $this->fields = $fields;
	//$this->db_retrieve_all();
}

}

/**
 * Correct the problem of fields
 */
function __clone()
{

$this->id = 0;
$this->_update = time();
foreach ($this->fields as $name=>$field)
{
	$this->fields[$name] = clone $field;
}

}

/**
 * Retrieve the datamodel id from the class name
 */
protected function datamodel_find()
{

$datamodel_name = substr(get_called_class(),0,-8);
if ($datamodel=datamodel($datamodel_name))
{
	$this->datamodel_id = $datamodel->id();
}

}
/**
 * Sets required fields of the object from the datamodel informations
 */
public function datamodel_set()
{

$this->fields = array();
$this->field_values = array();
$this->id = 0;
$this->_update = time();

}
public function datamodel()
{

return datamodel($this->datamodel_id);

}

public function __isset($name)
{

return (array_key_exists($name, $this->fields) || array_key_exists($name, $this->field_values) || isset($this->datamodel()->{$name}));

}

/**
 * Unset (to null value) a data field
 */
public function __unset($name)
{

if (array_key_exists($name, $this->fields))
{
	$this->fields[$name]->value = null;
}
elseif (array_key_exists($name, $this->field_values))
{
	$this->fields_values[$name] = null;
}

}

/**
 * Update a data field
 */
public function __set($name, $value)
{

if ($name == "id")
{
	if (is_numeric($value) && $value>0)
		$this->id = (int)$value;
}
elseif ($name == "_update")
{
	if (is_numeric($value) && $value > $this->_update)
		$this->_update = (int)$value;
}
elseif (array_key_exists($name, $this->fields))
{
	$this->fields[$name]->value = $value;
}
elseif (array_key_exists($name, $this->field_values))
{
	$this->fields[$name] = clone $this->datamodel()->{$name};
	$this->fields[$name]->value = $value;
}
elseif (isset($this->datamodel()->{$name}))
{
	$this->fields[$name] = clone $this->datamodel()->{$name};
	$this->field_values[$name] = $this->fields[$name]->value;
	$this->fields[$name]->value = $value;
}

}

public function __get($name)
{

if ($name == "id" || $name == "_update")
	return $this->{$name};
elseif (isset($this->fields[$name]))
{
	return $this->fields[$name];
}
elseif (isset($this->field_values[$name]))
{
	$this->fields[$name] = clone $this->datamodel()->{$name};
	$this->fields[$name]->value = $this->field_values[$name];
	return $this->fields[$name];
}
elseif (isset($this->datamodel()->{$name}))
{
	$this->fields[$name] = clone $this->datamodel()->{$name};
	$this->field_values[$name] = $this->fields[$name]->value;
	return $this->fields[$name];
}

}

/**
 * Default disp value
 * Can (and SHOULD) be overloaded in datamodel library
 * 
 * @return string
 */
function __tostring()
{

return $this->datamodel()->label()." ID#$this->id";

}

/**
 * Returns field list
 */
public function field_list()
{

return $this->fields;

}

/**
 * Set/init all fileds to default value
 * 
 */
public function init()
{

$this->id = 0;
$this->_update = time();
foreach ($this->datamodel()->fields() as $name=>$field)
{
	$this->fields[$name] = clone $field;
	$this->field_values[$name] = $field->value;
}

}
/**
 * Retrieve fields from database
 *
 * @param array $fields
 * @param boolean $force
 * @return boolean
 */
public function db_retrieve($fields, $force=false)
{

if (!$this->id)
	return false;

$params[] = array("name"=>"id", "type"=>"=", "value"=>$this->id);

$params = array();
if (!is_array($fields))
	if (is_string($fields))
		$fields = array($fields);
	else
		$fields = array();

// Delete the fields we already have if we don't want all fields
if (!$force) foreach ($fields as $i=>$name)
	if (isset($this->fields[$name]))
		unset($fields[$i]);

// Effective Query
if (count($fields) && ($list = $this->datamodel()->db_fields($params, $fields)))
{
	if (count($list) == 1)
	{
		foreach($list[0] as $name=>$field)
		{
			if (!isset($this->fields[$name]) && !isset($this->field_values[$name]))
			{
				$this->fields[$name] = $field;
				$this->field_values[$name] = $field->value;
			}
		}
		return true;
	}
	else
	{
		if (DEBUG_DATAMODEL)
			trigger_error("Datamodel '".$this->datamodel()->name()."' agregat : too many objects resulting from query params");
		return false;
	}
}
else
	return false;

}
/**
 * Retrieve all data fields from database
 * @return boolean
 */
public function db_retrieve_all()
{

$fields = array();
foreach ($this->datamodel()->fields() as $name=>$field)
	if (!array_key_exists($name, $this->field_values) && !array_key_exists($name, $this->fields))
		$fields[] = $name;

if (count($fields) > 0)
{
	return $this->db_retrieve($fields);
}
else
	return false;

}

/**
 * Return a view of the object, using a datamodel template
 * @param unknown_type $name
 */
public function view($name="")
{

if (!$name)
	$name = $this->datamodel()->name();

if ($view=template("datamodel/$name"))
{
	$view->object_set($this);
	return $view;
}

}
public function display($name="")
{

return $this->view($name);

}
/**
 * Display
 * @param unknown_type $name
 */
public function disp($name="")
{

echo $this->display($name);

}
/**
 * Return the default form view
 *
 * @param unknown_type $name
 * @return unknown
 */
public function form($name="")
{

if (!$name)
	$name = $this->datamodel()->name();

{
	$view = new datamodel_update_form($this);
}

return $view;

}
public function update_form($name="")
{

if (!$name)
	$name = $this->datamodel()->name();

{
	$view = new datamodel_update_form($this);
}

return $view;

}
public function insert_form($name="")
{

if (!$name)
	$name = $this->datamodel()->name();

{
	$view = new datamodel_insert_form($this);
}

return $view;

}

/**
 * Insert data into database as a new object
 *
 * @param unknown_type $options
 */
public function db_insert($options=array())
{

return $this->datamodel()->db_insert($this);

}

/**
 * Update data fields from database
 *
 * @return unknown
 */
public function update_from_db($fields=array())
{

foreach ($fields as $name=>$value)
{
	if ($name == "id")
	{
		$this->{$name} = (int)$value;
	}
	elseif ($name == "_update")
	{
		$e = explode(" ", $value);
		$d = explode("-", $e[0]);
		$t = explode(":", $e[1]);
		$this->{$name} = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	}
	elseif (array_key_exists($name, $this->fields))
	{
		$this->fields[$name]->value_from_db($value);
		$this->field_values[$name] = $this->fields[$name]->value;
	}
	elseif (array_key_exists($name, $this->field_values) || isset($this->datamodel()->{$name}))
	{
		$this->fields[$name] = clone $this->datamodel()->{$name};
		$this->fields[$name]->value_from_db($value);
		$this->field_values[$name] = $this->fields[$name]->value;
	}
}

}
/**
 * Update the object from a form
 * @param unknown_type $fields
 */
public function update_from_form($fields=array(), $db_update=false)
{

if (is_array($fields) && count($fields) > 0)
{
	foreach($fields as $name=>$value)
	{
		if ($name == "id")
		{
			$this->__set("id", $value);
		}
		elseif ($field=$this->__get($name))
		{
			$field->value_from_form($value);
		}
	}
	// Calculated fields
	// TODO : UPDATE
	if (count($this->datamodel()->fields_calculated()))
	{
		$calculate = array();
		$retrieve = array();
		foreach($this->datamodel()->fields_calculated() as $name=>$list)
		{
			// On parcours les champs utiles dans un calcul
			foreach($list as $value)
				// Si le champ a �t� modifi� on doit le mettre � jour
				if (isset($fields[$value]))
					if (!isset($calculate[$name]))
						$calculate[$name] = $list;
		}
		// Récupération des champs manquant
		foreach($calculate as $name=>$list)
		{
			foreach ($list as $value)
				if (!isset($fields[$value]) && !in_array($value, $retrieve))
					$retrieve[] = $value;
			if (!isset($fields[$value]) && !in_array($name, $retrieve))
				$retrieve[] = $name;
		}
		if (count($retrieve)>0)
		{
			//print_r($retrieve);
			$this->db_retrieve($retrieve);
		}
		// Calculs
		foreach($calculate as $name=>$list)
		{
			$function = "calculate_$name";
			$this->$function();
		}
	}
	// Mise à jour en base de donnée
	if ($db_update)
		$this->db_update();
}
	
}
/**
 * Update data into database
 *
 * @param unknown_type $options
 */
public function db_update($options=array())
{

// Permission verification
if (false)
{
	die("NOT ALLOWED TO UPDATE !");
}

$fields = array();
foreach ($this->fields as $name=>$field)
{
	if ($this->field_values[$name] !== $field->value)
		$fields[$name] = $field;
}

if (!count($fields))
	return false;

if ($this->datamodel()->db_update(array(array("name"=>"id", "value"=>$this->id)), $fields))
{
	foreach ($fields as $name=>$field)
		$this->field_values[$name] = $field->value;
	$this->_update = time();
	db()->query("INSERT INTO `_datamodel_update` (`datamodel_id`, `dataobject_id`, `account_id`, `action`, `datetime`) VALUES ('".$this->datamodel()->id()."', '".$this->id."', '".login()->id()."', 'u', NOW())");
	if (CACHE)
		cache::store("dataobject_".$this->datamodel_id."_".$this->id, $this, CACHE_DATAOBJECT_TTL);
	return true;
}

return false;

}

/**
 * Returns the datamodel action list
 */
public function action_list()
{

return $this->datamodel()->action_list();

}

/**
 * Execute an action
 * @param unknown_type $method
 * @param unknown_type $params
 */
public function action($method, $params)
{

// TODO : great potential with this concept !
// datamodel()::action_exists()
// datamodel()::action_get()
// etc.

$action_list = &$this->datamodel()->action_list();
if (isset($action_list[$method]) && $action=$action_list[$method]["method"])
{
	$this->$action($params);
}

}

/**
 * The most simple action...
 * Create an (almost) empty linked object
 * @param string $datamodel_name
 * @return dataobject
 */
public function ref_create($datamodel_name)
{

$name = $this->datamodel()->name();

if ($datamodel=datamodel($datamodel_name) && isset($datamodel->{$name}))
{
	$object = $datamodel->create();
	$object->{$name} = $this->id;
	return $object;
}

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>