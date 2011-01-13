<?php

/**
  * $Id$
  * 
  * Copyright 2008 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

// Page list
$admin_menu = array
(
	"info" => "Informations",
	"account" => "Comptes utilisateur",
	"globals" => "Parametres généraux",
	"lang" => "Langues",
	"permission" => "Permissions",
	//"module" => "Modules",
	"library" => "Librairies",
	"datamodel" => "Datamodel",
	"data" => "Données",
	//"databank" => "Databank",
	"template" => "Templates",
	"page" => "Pages",
	"menu" => "Menus",
	//"widget" => "Widgets",
);

// Default page
$admin_page = "template";

function admin_select()
{

global $admin_menu;
global $admin_page;

if (isset($_GET["_page"]) && isset($admin_menu[$_GET["_page"]]))
{
	$admin_page = $_GET["_page"];
}

}

function admin_disp()
{

global $admin_page;

include PATH_ADMIN."/$admin_page.inc.php";

}

admin_select();

header("Content-type: text/html; charset=".SITE_CHARSET);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=<?=SITE_CHARSET?>" />
<meta http-equiv="content-Language" content="<?=SITE_LANG?>" />

<meta name="robots" content="noindex,nofollow" />

<title><?php echo $admin_menu[$admin_page]; ?> - ADMINISTRATION</title>

<link rel="stylesheet" type="text/css" href="/css/jquery-ui-1.8.6.custom.css" />
<link rel="stylesheet" type="text/css" href="/css/jquery.ui.timepicker.css" />
<link rel="stylesheet" type="text/css" href="/css/jquery.asmselect.css" />
<link rel="stylesheet" type="text/css" href="/css/common.css" />
<link rel="stylesheet" type="text/css" href="/css/admin.css" />

<script type="text/javascript" language="Javascript" src="/js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" language="javascript" src="/js/jquery-ui-1.8.6.custom.min.js"></script>
<script type="text/javascript" language="javascript" src="/js/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" language="javascript" src="/js/jquery.uidatepicker-fr.js"></script>
<script type="text/javascript" language="Javascript" src="/js/jquery.asmselect.js"></script>
<script type="text/javascript" language="javascript" src="/js/jquery.autogrowtextarea.js"></script>

<!--<script type="text/javascript" language="javascript" src="/js/json2.js"></script>-->

<script type="text/javascript" language="Javascript" src="/js/edit_area/edit_area_full.js"></script>
<script type="text/javascript" language="javascript" src="/js/ckeditor/ckeditor.js"></script>
<script type="text/javascript" language="javascript" src="/js/ckeditor/adapters/jquery.js"></script>

<script type="text/javascript" language="javascript" src="/js/common.js"></script>
<script type="text/javascript" language="javascript" src="/js/admin.js"></script>

<!--[if lt IE 7.]>
<script defer type="text/javascript" language="javascript" src="/js/pngfix.js"></script>
<![endif]-->

</head>

<body>

<div class="admin_menu"><?php
foreach ($admin_menu as $_page => $_name)
{
	if ($admin_page == $_page)
		echo "<a href=\"/admin/$_page\" class=\"selected\">$_name</a>";
	else
		echo "<a href=\"/admin/$_page\">$_name</a>";
}
?></div>

<div class="page_content">
<?php admin_disp(); ?>
</div>

</body>

</html>
