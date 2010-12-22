<?php

/**
  * $Id$
  * 
  * Copyright 2008 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  */

if (DEBUG_GENTIME ==  true)
	gentime(__FILE__." [begin]");

// Langues
$lang = array("fr"=>2);

if (is_numeric($i=strpos($_SERVER["REQUEST_URI"], "?")))
	$_SERVER["REDIRECT_URL"] = substr($_SERVER["REQUEST_URI"], 0, $i);
else
	$_SERVER["REDIRECT_URL"] = $_SERVER["REQUEST_URI"];
unset($i);

if (substr($_SERVER["REDIRECT_URL"], -5) == ".html")
	$url = substr($_SERVER["REDIRECT_URL"], 1, -5);
else
	$url = $_SERVER["REDIRECT_URL"];
$url_e = explode("/",$url);
if (!$url_e[0])
        unset($url_e[0]);
$l = array_shift($url_e);

if (!$l || !isset($lang[$l]))
{
	define("SITE_LANG",SITE_LANG_DEFAULT);
	define("SITE_LANG_ID",SITE_LANG_DEFAULT_ID);
	define("REDIRECT",true);
}
else
{
	define("SITE_LANG",$l);
	define("SITE_LANG_ID",$lang[$l]);
	define("REDIRECT",false);
}

unset($l);

if (DEBUG_GENTIME ==  true)
	gentime(__FILE__." [end]");

?>