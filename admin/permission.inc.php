<?php

/**
  * $Id: permission.inc.php 21 2010-12-17 15:48:39Z lemathoufou $
  * 
  * Copyright 2008 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

if (!defined("ADMIN_OK"))
	die("ACCES NON AUTORISE");

$_type = "permission";
$_label = "Permission";

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

$_type()->retrieve_objects();

?>
<form method="get" class="page_form">
<input type="submit" value="<?php echo $_label; ?>" />
<select name="id" onchange="this.form.submit()">
	<option value=""></option>
<?php
foreach ($_type()->list_get() as $id=>$object)
{
	if (isset($_GET["id"]) && ($id==$_GET["id"]))
		echo "	<option value=\"$id\" selected>[$id] ".$object->label()."</option>\n";
	else
		echo "	<option value=\"$id\">[$id] ".$object->label()."</option>\n";
}
?>
</select>
<a href="?list">Liste</a>
<a href="?add">Ajouter</a>
</form>

<?php

if (isset($_GET["id"]) && $_type()->exists($id=$_GET["id"]))
{

$_type($id)->update_form();

}

elseif (isset($_GET["add"]))
{

$_type()->insert_form();
	
}

else
{

$_type()->table_list();

}

?>
