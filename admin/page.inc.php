<?

/**
  * $Id$
  * 
  * Copyright 2008 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

if (!defined("ADMIN_OK"))
	die("ACCES NON AUTORISE");

$_type = "page";
$_label = "Page";

if (isset($_POST["_insert"]))
{
	$_type()->add($_POST);
}

if (isset($_POST["_update"]) && isset($_POST["id"]) && $_type()->exists($_POST["id"]))
{
	$_type($_POST["id"])->update($_POST);
}

if (isset($_POST["_delete"]) && $_type()->exists($_POST["_delete"]))
{
	$_type()->del($_POST["_delete"]);
}

$_type_list = $_type()->list_detail_get();

?>
<form method="get" class="page_form">
<input type="submit" value="<?php echo $_label; ?>" />
<select name="id" onchange="this.form.submit()">
	<option value=""></option>
<?php
foreach ($_type_list as $id=>$info)
{
	if (isset($_GET["id"]) && ($id==$_GET["id"]))
		echo "	<option value=\"$id\" selected>[$id] $info[name]</option>\n";
	else
		echo "	<option value=\"$id\">[$id] $info[name]</option>\n";
}
?>
</select>
<a href="?list">Liste</a>
<a href="?add">Ajouter</a>
</form>

<div style="padding-top: 30px">
<?

// Permissions
$permission_list = permission()->list_detail_get();
// Permissions
$template_list = template()->list_detail_get();
// Types
$type_list = array
(
	"static_html" => "Page HTML statique",
	"template" => "Utilisation d'un template (valeur par défaut)",
	"redirect" => "Redirection vers une page extérieure",
	"alias" => "Alias d'une autre page du site",
	"static_html" => "Page HTML statique",
	"php" => "Script PHP"
);

// ACTION

if (isset($_GET["id"]) && $_type()->exists($id=$_GET["id"]))
{

$page = $_type()->list_detail_get($id);

?>
<form method="post" style="margin-top: 5px;">
<table width="100%" cellspacing="0" cellpadding="0">
<tr>
	<td class="label"><label for="id">ID</label> :</td>
	<td ><input name="id" value="<?php echo $id; ?>" readonly /></td>
	<td rowspan="8">
	<h3 style="margin-bottom: 0px;">SCRIPT de contrôle (optionnel)</h3>
	<textarea id="script" name="script" class="data_script data_script_php" rows="20"><?php
	$filename = "page/$page[name].inc.php";
	if (file_exists($filename) && filesize($filename))
	{
		echo $content = htmlspecialchars(fread(fopen($filename,"r"),filesize($filename)));
	}
	else
	{
		$content="";
	}
	?></textarea>
	</td>
</tr>
<tr>
	<td class="label"><label for="name">Name</label> :</td>
	<td><input name="name" value="<?php echo $page["name"]; ?>" /></td>
</tr>
<tr>
	<td class="label">Type :</td>
	<td><select name="type">
	<?php
	foreach ($type_list as $type=>$label)
		if ($page["type"] == $type)
			echo "<option value=\"$type\" selected>$label</option>";
		else
			echo "<option value=\"$type\">$label</option>";
	?></select></td>
</tr>
<tr>
	<td class="label"><label for="template_id">Template associé</label> :</td>
	<td><select name="template_id"><?php
	foreach (template()->list_detail_get() as $template)
	{
		if ($template["id"] == $page["template_id"])
			echo "<option value=\"$template[id]\" selected>$template[label]</option>";
		else
			echo "<option value=\"$template[id]\">$template[label]</option>";
	}
	?></select></td>
</tr>
<tr>
	<td class="label"><label for="url">URL (rewriting)</label> :</td>
	<td><input name="url" value="<?php echo $page["url"]; ?>" /></td>
</tr>
<tr>
	<td class="label"><label for="titre_court">Titre court (lien)</label> :</td>
	<td><input name="shortlabel" value="<?php echo $page["shortlabel"]; ?>" /></td>
</tr>
<tr>
	<td class="label"><label for="label">Label/Titre (header de page)</label> :</td>
	<td><input name="label" value="<?php echo $page["label"]; ?>" /></td>
</tr>
<tr>
	<td class="label"><label for="perm_list">Permissions</label> :</td>
	<td><select name="perm_list[]" size="4" multiple>
	<?
	foreach($permission_list as $perm)
	{
		if (in_array($perm["id"], $page["perm_list"]))
			print "<option value=\"$perm[id]\" selected>$perm[label]</option>";
		else
			print "<option value=\"$perm[id]\">$perm[label]</option>";
	}
	?>
	</select></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" name="_update" value="Mettre à jour" /></td>
</tr>
</table>
</form>

<?php

// ADD/Update a param
if (isset($_POST["param"]) && is_array($_POST["param"]))
{
	foreach ($_POST["param"] as $name=>$param)
	{
		//echo "<p>Updating param $name : $value</p>\n";
		if (is_numeric($param["update_pos"]))
			db()->query("REPLACE INTO _page_params (page_id, name, value, update_pos) VALUES ('$id', '$name', '".db()->string_escape($param["value"])."', '".db()->string_escape($param["update_pos"])."')");
		else
			db()->query("REPLACE INTO _page_params (page_id, name, value, update_pos) VALUES ('$id', '$name', '".db()->string_escape($param["value"])."', null)");
	}
}

// Delete a param
if (isset($_POST["param_del"]) && ($name=$_POST["param_del"]))
{
	//echo "<p>Param $name DELETED</p>\n";
	db()->query("DELETE FROM _page_params WHERE page_id='$id' AND name='$name'");
}

// Retrieve param list
$params = array();
$params_ok = array();
$query_params = db()->query("SELECT name, value, update_pos FROM _page_params WHERE page_id='$id'");
while (list($name, $value, $update_pos) = $query_params->fetch_row())
{
	$params[$name] = array("value"=>$value, "update_pos"=>$update_pos);
}

if (isset($page["template_id"]) && (is_a($template=template($page["template_id"]), "template")))
{
	?>
	<h3>Liste des paramètres des templates associés :</h3>
	<form method="post">
	<table cellspacing="0" cellpadding="0" border="0" class="tpl_params">
	<?php
	if (count($template->param_list()))
	{
		?>
		<tr class="title header"> <td colspan="5"><?=$template->title()?> : template ID#<?=$template->id()?></td> </tr>
		<?php
		foreach ($template->param_list() as $name=>$param)
		{
		?>
		<tr>
			<td class="label"><?=$name?></td>
			<td></td>
		</tr>
		<?php
		}
	}
	$tpl_filename = "template/".$template->name().".tpl.php";
	$subtemplates = template::subtemplates(fread(fopen($tpl_filename, "r"), filesize($tpl_filename))); 
	foreach($subtemplates as $tpl)
	{
		$template = template($tpl["id"]);
		?>
		<tr class="separator"> <td>&nbsp;</td> </tr>
		<tr class="tpl_name"> <td colspan="5"><?=$template->title()?> (sub-template ID#<?=$template->id()?>)</td> </tr>
		<tr class="separator"> <td>&nbsp;</td> </tr>
		<tr class="title">
			<td>Name (in template)</td>
			<td>Datatype</td>
			<td>Default value (JSON)</td>
			<td>Name (in page)</td>
			<td>Surcharged value (by page)</td>
		</tr>
		<?php
		foreach ($template->param_list() as $name=>$param)
		{
		?>
		<tr>
			<td class="label"><?=$name?></td>
			<td><?=data()->get_name($param["datatype"])->label?></td>
			<td><? if ($param["value"] === null) echo "<i>NULL</i>"; else echo json_encode($param["value"]); ?></td>
			<?php
			if (isset($tpl["params"]) && $tpl["params"] === true || (isset($tpl["params"][$name]) && $tpl["params"][$name] == $name))
			{
			?>
			<td class="label"><?=$name?></td>
			<td>
			<textarea id="param[<?=$name?>][value]" cols="40" rows="4"><? if (isset($params[$name])) echo $params[$name]["value"]; ?></textarea>
			<input id="param[<?=$name?>][update_pos]" value="<? if (isset($params[$name])) echo $params[$name]["update_pos"]; ?>" size="1" />
			<input type="submit" value="<?php if (isset($params[$name])) echo "Update"; else echo "Add" ?>" onclick="param_update('<?=$name?>')" />
			<?
			if (isset($params[$name]))
			{
				$params_ok[] = $name;
				echo "<input type=\"submit\" value=\"DEL\" style=\"color:red;\" onclick=\"this.name='param_del';this.value='$name';\" />";
			}
			?>
			</td>
			<?
			}
			elseif (isset($tpl["params"][$name]))
			{
			$name = $tpl["params"][$name];
			?>
			<td class="label"><?=$name?></td>
			<td>
			<textarea id="param[<?=$name?>][value]" cols="40" rows="4"><? if (isset($params[$name])) echo $params[$name]["value"]; ?></textarea>
			<input id="param[<?=$name?>][update_pos]" value="<? if (isset($params[$name])) echo $params[$name]["update_pos"]; ?>" size="1" />
			<input type="submit" value="<?php if (isset($params[$name])) echo "Update"; else echo "Add" ?>" onclick="param_update('<?=$name?>')" />
			<?
			if (isset($params[$name]))
			{
				$params_ok[] = $name;
				echo "<input type=\"submit\" value=\"DEL\" style=\"color:red;\" onclick=\"this.name='param_del';this.value='$name';\" />";
			}
			?>
			</td>
			<?
			}
			else
			{
			?>
			Parameter not passed in parent template
			<?	
			}
			?></td>
		</tr>
		<?php
		}
	}
		?>
		<tr class="separator"> <td>&nbsp;</td> </tr>
		<tr class="tpl_name"> <td colspan="5">Paramètres supplémentaires</td> </tr>
		<tr class="separator"> <td>&nbsp;</td> </tr>
		<tr class="title">
			<td colspan="3">&nbsp;</td>
			<td>Name</td>
			<td>Value</td>
		</tr>
		<?
	foreach ($params as $name=>$param)
	{
		if (!in_array($name, $params_ok))
		{
		?>
		<tr>
			<td colspan="3">&nbsp;</td>
			<td class="label"><?=$name?></td>
			<td>
			<textarea id="param[<?=$name?>][value]" cols="40" rows="4"><?=$params[$name]["value"]?></textarea>
			<input id="param[<?=$name?>][update_pos]" value="<?=$param["update_pos"]?>" size="1" />
			<input type="submit" value="Update" onclick="param_update('<?=$name?>')" />
			<input type="submit" value="DEL" style="color:red;" onclick="this.name='param_del';this.value='<?=$name?>';" />
			</td>
		</tr>
		<?
		}
	}
	?>
	</table>
	</form>
	<?php
}

}

// INSERTION
elseif (isset($_GET["add"]))
{

$object = array
(
	"name" => "",
	"type" => "template",
	"template_id" => "0",
	"url" => "",
	"shortlabel" => "",
	"label" => "",
	"description" => "",
	"perm" => array(),
	"script" => ""
);

foreach ($object as $i=>$j)
	if (isset($_POST[$i]))
		$object[$i] = $_POST[$i];

?>
<form action="" method="POST">
<table width="100%">
<tr>
	<td class="label">Name :</td>
	<td><input name="name" value="<?=$object["name"]?>" size="32" /></td>
	<td width="50%" rowspan="9">
	<h3 style="margin-bottom: 0px;">SCRIPT de contrôle (optionnel)</h3>
	<textarea id="script" name="script" class="data_script data_script_php" style="width:100%;" rows="25"><?php echo $object["script"]; ?></textarea>
	</td>
</tr>
<tr>
	<td class="label">Type :</td>
	<td><select name="type">
	<?php
	foreach ($type_list as $type=>$label)
		if ($object["type"] == $type)
			echo "<option value=\"$type\" selected>$label</option>";
		else
			echo "<option value=\"$type\">$label</option>";
	?></select></td>
</tr>
<tr>
	<td class="label">Template :</td>
	<td><select name="template_id"><?php
	foreach ($template_list as $id=>$info)
	{
		if ($object["template_id"] == $id)
			echo "<option value=\"$id\" selected>$info[label]</option>";
		else
			echo "<option value=\"$id\">$info[label]</option>";
	}
	?></select></td>
</tr>
<tr>
	<td class="label">URL (rewriting) :</td>
	<td><input name="url" value="<?=$object["url"]?>" size="32" /></td>
</tr>
<tr>
	<td class="label">Titre court (lien) :</td>
	<td><input name="shortlabel" value="<?=$object["shortlabel"]?>" size="32" /></td>
</tr>
<tr>
	<td class="label">Titre (header de page) :</td>
	<td><input name="label" value="<?=$object["label"]?>" size="32" /></td>
</tr>
<tr>
	<td class="label">Description :</td>
	<td><textarea name="description" cols="32" rows="4"><?=$object["description"]?></textarea></td>
</tr>
<tr>
	<td class="label">Permissions :</td>
	<td><select name="perm[]" size="4" multiple><?
	foreach($permission_list as $perm)
	{
		if (in_array($i, $object["perm"]))
			print "<option value=\"$perm[id]\" selected>$perm[label]</option>";
		else
			print "<option value=\"$perm[id]\">$perm[label]</option>";
	}
	?></select></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td><input type="submit" value="Ajouter" /></td>
</tr>
</table>
</form>
<?php

}

// LISTE
else
{

?>

<h2>Liste et paramétrage des pages disponibles</h2>

<p>Une page est accessible par une url (à l'aide de rewriting).</p>
<p>Une page est de type : "template", "alias" (d'une autre page) ou encore "redirection" (vers une page extérieure au site).</p>
<p>Une page associée à un template se paréamètre en fonction de ce dernier.</p>
<p>Une page peut être associée à un ou plusieurs menus.</p>

<?

page()->table_list(array(), array("label", "description", "type", "template_id", "perm"));

}

?>
</div>
<style type="text/css">
table.tpl_params td
{
	padding: 0px 1px;
	border: 1px #ccc solid;
}
table.tpl_params tr.separator td
{
	border: 0px;
}
table.tpl_params tr.tpl_name td
{
	font-weight: bold;
	border: 0px;
	font-size: 1em;
} 
table.tpl_params tr.title td
{
	font-weight: bold;
	background-color: #ffa;
}
</style>

<script type="text/javascript">
function param_update(name)
{
	var element = document.getElementById('param['+name+'][value]');
	element.name = element.id;
	element = document.getElementById('param['+name+'][update_pos]');
	element.name = element.id;
}
// initialisation
editAreaLoader.init({
	id: "script"	// id of the textarea to transform		
	,start_highlight: true	// if start with highlight
	,allow_resize: "both"
	,allow_toggle: true
	,word_wrap: false
	,language: "fr"
	,syntax: "php"	
});
</script>