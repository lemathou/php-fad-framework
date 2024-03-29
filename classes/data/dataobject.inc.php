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
 * Dataobject/Databank
 * 
 * Dataobjects a specific agregats of class data_bank_agregat,
 * corresponding to a datamodel associated to a database.
 * Thoses objects needs only an ID to be retrieved
 */
class data_dataobject extends data
{

protected $empty_value = 0;

protected $opt = array
(
	"datamodel" => null,
	"ref_field_disp" => "", // field to display if needed
);

function __construct($name, $value, $label="Object", $options=array())
{

data::__construct($name, $value, $label, $options);

}

public function db_fieldname()
{

if (isset($this->opt["db_field"]) && ($fieldname=$this->opt["db_field"]))
	return $fieldname;
else
	return $this->name."_id";

}

public function db_field_create()
{

return array("name"=>$this->db_fieldname(), "type"=>"integer", "size"=>10, "signed"=>false);

}

/* Convert */
public function verify(&$value, $convert=false, $options=array())
{

if (!is_numeric($value) || !datamodel($this->opt["datamodel"])->exists($value))
{
	if ($convert)
		$value = null;
	return false;
}

$value = (int)$value;
return true;

}
function convert(&$value)
{

if (!is_numeric($value) || !datamodel($this->opt["datamodel"])->exists($value))
	$value = null;
else
	$value = (int)$value;

}
function convert_from_db(&$value)
{

if (is_numeric($value))
	$value = (int)$value;

}
function convert_from_form(&$value)
{

// We create an associated object
if (is_array($value) && ($object = datamodel($this->opt["datamodel"])->create()))
{
	$object->update_from_form($value);
	/*if ($this->object_id && $ref_id=$this->opt["db_ref_id"])
		$object->__set($ref_id, $this->object_id);*/
	if ($object->db_insert())
		$value = $object->id;
	else
		$value = 0;
}

}

/* View */
function form_field_disp($options=array())
{

if (!($datamodel=datamodel($this->opt["datamodel"])))
	return;

// Pas beaucoup de valeurs : liste simple
if ((($nb=$datamodel->count()) <= 50))
{
	if (isset($option["order"]))
		$query = $datamodel->query(array(), array(), $option["order"]);
	else
		$query = $datamodel->query();

	$return = "<select name=\"$this->name\" title=\"$this->label\" class=\"".get_called_class()."\">\n";
	$return .= "<option></option>";
	foreach($query as $object)
	{
		if ($this->value == $object->id)
		{
			$return .= "<option value=\"$object->id\" selected=\"selected\">$object</option>";
		}
		else
			$return .= "<option value=\"$object->id\">$object</option>";
	}
	$return .= "</select>";
	$return .= "<div><input type=\"button\" value=\"ADD\" onclick=\"datamodel_insert_form('".$this->opt["datamodel"]."', null, this.parentNode, '$this->name')\" /></div>\n";
}
// Beaucoup de valeurs : liste Ajax complexe
else
{
	$return = "<div style=\"display:inline;\"><input name=\"$this->name\" value=\"$this->value\" type=\"hidden\" class=\"q_id\" />";
	if ($object=$this->object())
		$value = (string)$object;
	else
		$value = "";
	$return .= "<select class=\"q_type\"><option value=\"like\">Approx.</option><option value=\"fulltext\">Precis</option></select><input class=\"q_str\" value=\"$value\" onkeyup=\"object_list_query('".$this->opt["datamodel"]."', [{'type':$('.q_type', this.parentNode).val(),'value':this.value}], $(this).parent().get(0));\" onblur=\"object_list_hide($(this).parent().get(0))\" onfocus=\"this.select();if(this.value) object_list_query('".$this->opt["datamodel"]."', [{'type':$('.q_type', this.parentNode).val(),'value':this.value}], $(this).parent().get(0));\" />";
	$return .= "<div class=\"q_select\"></div>";
	$return .= "<div><input type=\"button\" value=\"ADD\" onclick=\"datamodel_insert_form('".$this->opt["datamodel"]."', null, this.parentNode, '$this->name')\" /></div>\n";
 	$return .= "</div>";
}

return $return;

}
function form_field_disp_all()
{

$return = "<div id=\"".$this->name."_list\">\n";
$return .= "<select name=\"".$this->name."\" title=\"$this->label\" class=\"".get_called_class()."\">\n";

$query = datamodel($this->opt["databank"])->query();
foreach ($query as $object)
{
	if ($this->value == "$object->id")
		$return .= "<option value=\"$object->id\" selected=\"selected\">$object</option>";
	else
		$return .= "<option value=\"$object->id\">$object</option>";
}

$return .= "</select>\n";
$return .= "</div>\n";


return $return;

}
function __tostring()
{

if ($this->nonempty() && ($datamodel=datamodel($this->opt["datamodel"])) && ($object=$datamodel->get($this->value)))
{
	if (($fieldname=$this->opt["ref_field_disp"]) && isset($datamodel->{$fieldname}))
	{
		return (string)$object->{$fieldname};
	}
	else
		return (string)$object;
}
else
	return "";

}

/**
 * Returns the object
 */
function object()
{

if ($this->nonempty())
	return datamodel($this->opt["datamodel"])->get($this->value);
else
	return null;

}

/**
 * Create a new object
 */
function create()
{

$object = datamodel($this->opt["datamodel"])->create();
$object->db_insert();
$this->value = $object->id;
return $object;

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
