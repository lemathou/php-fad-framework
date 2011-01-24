<?php

/**
  * $Id: datamodel.inc.php 27 2011-01-13 20:58:56Z lemathoufou $
  * 
  * Copyright 2008-2011 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

class datamodel_gestion extends _datamodel_gestion
{

protected function del_more($id)
{

// Fields
db()->query("DELETE FROM `_datamodel_fields` WHERE `datamodel_id`='$id'");
db()->query("DELETE FROM `_datamodel_fields_lang` WHERE `datamodel_id`='$id'");
db()->query("DELETE FROM `_datamodel_fields_opt` WHERE `datamodel_id`='$id'");
db()->query("DELETE FROM `_datamodel_fields_opt_lang` WHERE `datamodel_id`='$id'");

}

}

class datamodel extends _datamodel
{

/**
 * Delete a data field
 * @param unknown_type $name
 */
public function __unset($name)
{

$this->field_delete($name);

}

/*
 * Add a data field
 */
public function __set($name, data $field)
{

$this->field_add($field);

}

public function field_add($field, $options="")
{

// TODO : security : send an email with complete info about the one who tried to do this and blacklist him if possible ?
if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE A DATAMODEL");

if (!is_array($field))
	die("datamodel(ID#$this->id)::field_add() : Invalid parameters");

if (!isset($field["type"]) || !($field["type"]) || !isset(data()->{$field["type"]}))
	die("datamodel(ID#$this->id)::field_add() : Invalid parameters : type");

if (!isset($field["name"]) || !preg_match("/([a-z])+/", $field["name"]))
	die("datamodel(ID#$this->id)::field_add() : Invalid parameters : name");

if (!isset($field["opt"]) || !isset($field["query"]) || !isset($field["lang"]) || !isset($field["label"]))
	die("datamodel(ID#$this->id)::field_add() : Invalid parameters : opt, query, lang, label");

if (!isset($field["pos"]) || !is_numeric($field["pos"]) || $field["pos"] < 0)
	die("datamodel(ID#$this->id)::field_add() : Invalid parameters : pos");

$defaultvalue = "'".db()->string_escape($field["defaultvalue"])."'";
$pos_max = count($this->fields) + 1;

db()->query("INSERT INTO _datamodel_fields (datamodel_id, pos, name, type, defaultvalue, opt, `query`, lang) VALUES ('$this->id', '$pos_max', '".$field["name"]."', '".$field["type"]."', $defaultvalue, '".$field["opt"]."', '".$field["query"]."', '".$field["lang"]."')");
db()->query("INSERT INTO _datamodel_fields_lang (datamodel_id, lang_id, fieldname, label) VALUES ('$this->id', '".SITE_LANG_ID."', '".$field["name"]."', '".db()->string_escape($_POST["label"])."')");

// Gestion du repositionnement
if ($field["pos"] < $pos_max)
{
	db()->query("UPDATE `_datamodel_fields` SET `pos` = pos+1 WHERE `datamodel_id` = '$this->id' AND `pos` >= '".(int)$field["pos"]."'");
	db()->query("UPDATE `_datamodel_fields` SET `pos` = '".(int)$field["pos"]."' WHERE `datamodel_id` = '$this->id' AND `name` = '".$field["name"]."'");
}

$this->query_fields();

if (OBJECT_CACHE)
{
	object_cache_store("datamodel_$this->id", $this, OBJECT_CACHE_GESTION_TTL);
}

// Insertion du champ dans la tables associée
list($db_sync) = db()->query("SELECT `db_sync` FROM `_datamodel` WHERE `id`='$this->id'")->fetch_row();
if ($db_sync)
{
	if (isset($_POST["lang"]) && $_POST["lang"])
		db()->field_create($this->name."_lang", $field["name"], $this->fields[$field["name"]]->db_field_create());
	else
		db()->field_create($this->name, $field["name"], $this->fields[$field["name"]]->db_field_create());
}

}

/**
 * Delete a field
 * @param string $name
 */
public function field_delete($name)
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE A DATAMODEL");

if (!isset($this->fields[$name]))
	die("datamodel(ID#$this->id)::field_delete() : Field $name does not exists");

db()->query("DELETE FROM `_datamodel_fields` WHERE `name`='$name' AND `datamodel_id`='$this->id'");
db()->query("DELETE FROM `_datamodel_fields_lang` WHERE `fieldname`='$name' AND `datamodel_id`='$this->id'");
db()->query("DELETE FROM `_datamodel_fields_opt` WHERE `fieldname`='$name' AND `datamodel_id`='$this->id'");
db()->field_delete($this->name, $name);

$this->query_fields();

if (OBJECT_CACHE)
{
	object_cache_store("datamodel_$this->id", $this, OBJECT_CACHE_GESTION_TTL);
}

}

/**
 * Update a field
 * @param string $name
 * @param array $field
 */
function field_update($name, $field)
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

if (!isset($this->fields[$name]))
	die("datamodel(ID#$this->id)::field_update() : Field $name does not exists");

if (!is_array($field))
	die("datamodel(ID#$this->id)::field_update() : Invalid parmeters updating field $name");

if (!isset($field["type"]) || !($field["type"]) || !isset(data()->{$field["type"]}))
	die("datamodel(ID#$this->id)::field_update() : Invalid parmeters updating field $name");

if (!isset($field["name"]) || !preg_match("/([a-z])+/", $field["name"]))
	die("datamodel(ID#$this->id)::field_update() : Invalid parmeters updating field $name");

if (!isset($field["opt"]) || !isset($field["lang"]) || !isset($field["label"]))
	die("datamodel(ID#$this->id)::field_update() : Invalid parmeters updating field $name");

// Query infos
$query_str = "SELECT `db_sync` FROM `_datamodel` WHERE `id`='$this->id'";
list($db_sync) = db()->query($query_str)->fetch_row();
$query_str = "SELECT `pos` FROM `_datamodel_fields` WHERE `datamodel_id`='$this->id' AND name='$name'";
list($pos) = db()->query($query_str)->fetch_row();

// Création objet (par défaut)
$fieldtype = "data_$field[type]";
$datafield = new $fieldtype("field", null, null);

// Default value
if (isset($field["defaultvalue_null"]) || $field["defaultvalue"] === null || $field["defaultvalue"] == "")
	$defaultvalue = "NULL";
elseif (isset($field["defaultvalue"]))
	$defaultvalue = "'".db()->string_escape($field["defaultvalue"])."'";
else
	$defaultvalue = "''";

// Update old values
$query_str = "UPDATE `_datamodel_fields` SET `name`='".$field["name"]."', `type`='".$field["type"]."', `defaultvalue`=$defaultvalue, `opt`='".db()->string_escape($field["opt"])."', `query`='".db()->string_escape($field["query"])."', `lang`='".db()->string_escape($field["lang"])."' WHERE `datamodel_id`='$this->id' AND name='$name'";
db()->query($query_str);
$query_str = "UPDATE `_datamodel_fields_lang` SET `fieldname`='".$field["name"]."', `label`='".db()->string_escape($field["label"])."' WHERE `datamodel_id`='$this->id' AND `fieldname`='$name'";
db()->query($query_str);

// Delete old option values
$query_str = "DELETE FROM `_datamodel_fields_opt` WHERE `fieldname`='$name' AND `datamodel_id`='$this->id'";
db()->query($query_str);
// Options réellement à sauver (<> opt par défaut)
if (isset($field["optlist"]))
{
	$query_opt_list = array();
	$opt_list = $datafield->opt_list;
	foreach($field["optlist"] as $optname=>$optvalue)
	{
		if (in_array($optname, $opt_list) && ($datafield->opt[$optname] !== json_decode($optvalue, true)))
		{
			$query_opt_list[] = "('$this->id', '".$field["name"]."', '$optname' , '".db()->string_escape($optvalue)."' )";
		}
	}
	if (count($query_opt_list))
	{
		$query_str = "INSERT INTO `_datamodel_fields_opt` (`datamodel_id`, `fieldname`, `opt_name`, `opt_value`) VALUES ".implode(", ", $query_opt_list);
		db()->query($query_str);
	}
}

// Gestion du repositionnement
if ($field["pos"] < $pos)
{
	db()->query("UPDATE _datamodel_fields SET pos=pos+1 WHERE datamodel_id='$this->id' AND pos >= ".($field["pos"])." AND pos < $pos");
	db()->query("UPDATE _datamodel_fields SET pos=".($field["pos"])." WHERE datamodel_id='$this->id' AND name='".$field["name"]."'");
}
elseif ($pos < $field["pos"])
{
	db()->query("UPDATE _datamodel_fields SET pos=pos-1 WHERE datamodel_id='$this->id' AND pos > $pos AND pos <= ".($field["pos"]));
	db()->query("UPDATE _datamodel_fields SET pos=".($field["pos"])." WHERE datamodel_id='$this->id' AND name='".$field["name"]."'");
}

$this->query_fields();

if (OBJECT_CACHE)
{
	object_cache_store("datamodel_$this->id", $this, OBJECT_CACHE_GESTION_TTL);
}

// Mise à jour du champ dans la table associée
if ($db_sync)
{
	$this->query_info();
	if (isset($field["lang"]) && $field["lang"])
		db()->field_update($this->name."_lang", $name, $field["name"], $datafield->db_field_create());
	else
		db()->field_update($this->name, $name, $field["name"], $datafield->db_field_create());
}

}

/**
 * Create the associated database
 *
 * @return unknown
 */
public function db_create()
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

$options = array();
$fields = array();
$fields_lang = array();
$fields_ref = array();
// Key ID
//$data_id = new data_id();
$fields["id"] = array
(
	"type"=>"integer",
	"size"=>10,
	"null"=>false,
	"key"=>true,
	"auto_increment"=>true
);
// Other fields
foreach ($this->fields as $name=>$field)
{
	if ($field->type != "dataobject_list")
	{
		$f = $field->db_field_create();
		if (in_array($name, $this->fields_required))
		{
			$f["null"] = false;
		}
		elseif (!isset($f["null"]) || $f["null"])
		{
			$f["null"] = true;
		}
		if (isset($field->opt["lang"]))
			$fields_lang[$name] = $f;
		else
			$fields[$name] = $f;
	}
	else
	{
		$fields_ref[] = $field->db_create();
	}
}

db()->table_create($this->name, $fields, $options);

if (count($fields_lang)>0)
{
	$fields_lang["id"] = $fields["id"];
	unset($fields_lang["id"]["auto_increment"]);
	$fields_lang["lang_id"] = array("type"=>"integer", "size"=>"3", "null"=>true, "key"=>true);
	db()->table_create($this->name."_lang", $fields_lang, $options);
}

if (count($fields_ref))
{
	foreach($fields_ref as $table)
	{
		db()->table_create($table["name"], $table["fields"], $table["options"]);
	}
}

db()->query("ALTER TABLE `".$this->name."` DROP INDEX `FULLTEXT`");
if (count($this->fields_index))
{
	db()->query("ALTER TABLE `".$this->name."` ADD FULLTEXT `FULLTEXT` (`".implode("`, `",$this->fields_index)."`)");
}

}

/**
 * Update the associated database in case of updating data fields
 * or general structure
 *
 * @return unknown
 */
public function db_alter()
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

//return db()->table_update("$this->name","");

db()->query("ALTER TABLE `".$this->name."` DROP INDEX `FULLTEXT`");
if (count($this->fields_index))
{
	db()->query("ALTER TABLE `".$this->name."` ADD FULLTEXT `FULLTEXT` (`".implode("`, `",$this->fields_index)."`)");
}

}

/**
 * Drop the associated database
 *
 * @return unknown
 */
public function db_drop()
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

return db()->table_drop($this->name);

}

/**
 * Empty the associated database
 *
 * @return unknown
 */
public function db_empty()
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

return db()->table_empty($this->name);

}

/**
 * A fignoler, il faudra par ailleurs ajouter la gestion du positionnement au datamodel...
 * 
 * @param unknown_type $fieldname
 * @param unknown_type $position
 */
public function db_field_move($fieldname, $position)
{

if (!login()->perm(6))
	die("ONLY ADMIN CAN UPDATE DATAMODEL");

db()->query("ALTER TABLE `".$this->name."` MODIFY COLUMN ".db()->db_field_struct($fieldname, $this->fields[$fieldname]->db_field_create())." AFTER `$position`");

}
function table_list($params=array(), $fields=array(), $sort=array(), $page=1, $page_nb=10)
{

?>
<form name="zeform" action="" method="get">
<input name="datamodel_id" value="<?php echo $this->id; ?>" type="hidden" />
<p style="margin: 5px;"><a href="javascript:;" onclick="databank_params_aff()">Paramètres de sélection</a></p>
<div id="databank_select_form">
<table cellspacing="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
	<td class="label" width="250" valign="top"><h3>Sélection :</h3></td>
	<td><table id="databank_params"><?php
	foreach ($this->fields as $field)
	{
		$field = clone $field;
		echo "<tr>\n";
		echo "<td class=\"label\"><label for=\"$field->name\">$field->label</label></td>\n";
		echo "<td>";
		if (isset($params[$field->name]))
			$field->value_from_form($params[$field->name]["value"]);
		$field->form_field_select_disp(true);
		echo "<td>\n";
		echo "</tr>";
	}
	?></table></td>
</tr>
<tr> <td colspan="2"><hr /></td> </tr>
<tr>
	<td valign="top"><h3>Afficher les colonnes :</h3></td>
	<td><select name="_fields[]" title="Champs" multiple class="data_fromlist"><?php
	foreach ($this->fields as $name=>$field) if ($name != "id")
		if (in_array($name, $fields))
		{
			echo "<option value=\"$name\" selected>".$field->label."</option>";
		}
		else
		{
			echo "<option value=\"$name\">".$field->label."</option>";
		}
	?></select></td>
</tr>
<tr> <td colspan="2"><hr /></td> </tr>
<tr>
	<td valign="top"><h3>Trier par :</h3></td>
	<td><select name="_sort[0]"><?php
	$sort_url = "";
	foreach ($this->fields as $name=>$field)
	{
		if (isset($sort[$name]))
		{
			echo "<option value=\"$name\" selected>".$field->label."</option>";
			$sort_url = "&amp;_sort[0]=$name";
		}
		else
			echo "<option value=\"$name\">".$field->label."</option>";
	}
	?></select>
	<select name="_sort[1]"><?php
	foreach (array("ASC"=>"Croissant", "DESC"=>"Décroissant") as $name=>$value)
	{
		if (is_array($sort) && in_array($name, $sort))
		{
			echo "<option value=\"$name\" selected>$value</option>";
			if ($sort_url)
				$sort_url .= "&amp;_sort[1]=$name";
		}
		else
			echo "<option value=\"$name\">$value</option>";
	}
	?></select></td>
</tr>
<tr> <td colspan="2"><hr /></td> </tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="Afficher" /></td>
</tr>
</table>
</div>
</form>
<div><p>Pages de résultats : <?php
$nbmax = $this->db_count($params);
$page_list = new page_listing($nbmax, array("10", "20", "50"), 10, 1, "?datamodel_id=$this->id$sort_url");
$page_list->page_nb_set($page_nb);
$page_list->page_set($page);
echo implode(" ", $page_list->link_list());
?></p></div>
<div><table cellspacing="0" cellpadding="2" border="1" width="100%">
<?php

$list = $this->db_get($params, $fields, $sort, $page_list->page_nb, $page_list->nb_start());
foreach($list as $nb=>$object)
{
	// First line
	if (!$nb)
	{
		echo "<tr>\n";
			echo "<td><b><a href=\"javascript:;\" onclick=\"databank_list_sort('zeform','id')\">ID</a></b></td>";
			echo "<td>Default display</td>";
		foreach($object->field_list() as $field) if (in_array($field->name, $fields))
		{
			echo "<td><b><a href=\"javascript:;\" onclick=\"databank_list_sort('zeform','$field->name')\">".$field->label."</a></b></td>";
		}
		echo "</tr>\n";
	}
	echo "<tr>\n";
			echo "<td width=\"20\"><a href=\"?datamodel_id=$this->id&object_id=$object->id\">$object->id</a></td>";
			echo "<td>$object</td>";
	foreach($object->field_list() as $field) if (in_array($field->name, $fields))
	{
		if ($field->type == "dataobject" && $field->nonempty())
			echo "<td><a href=\"?datamodel_id=".$field->structure_opt("databank")."&object_id=$field->value\">$field</a></td>";
		elseif ($field->type == "dataobject_list" && $field->nonempty())
		{
			$fi=array();
			foreach($field->object_list() as $o)
				$fi[] = "<a href=\"?datamodel_id=".$field->structure_opt("databank")."&object_id=$o->id\">$o</a>";
			echo "<td>".implode(", ", $fi)."</td>";
		}
		else
			echo "<td>$field</td>";
	}
	echo "<td width=\"15\"><a href=\"?datamodel_id=$this->id&object_id=$object->id\"><img src=\"".SITE_BASEPATH."/img/icon/icon-view.gif\" alt=\"View\" /></a></td>";
	echo "<td width=\"15\"><a href=\"?datamodel_id=$this->id&object_id=$object->id\"><img src=\"".SITE_BASEPATH."/img/icon/icon-edit.gif\" alt=\"Update\" /></a></td>";
	echo "<td width=\"15\"><a href=\"javascript:;\" onclick=\"if (window.confirm('Etes-vous certain de supprimer ?')) location.href='?datamodel_id=$this->id&object_del=$object->id'\"><img src=\"".SITE_BASEPATH."/img/icon/icon-delete.gif\" alt=\"Delete\" /></a></td>";
	echo "</tr>\n";
}
?>
</table></div>
<?php

}

}

?>