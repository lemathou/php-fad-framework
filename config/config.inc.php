<?

/**
  * $Id: config.inc.php 59 2009-03-03 15:48:26Z mathieu $
  * 
  * « Copyright 2008 Mathieu Moulin - iProspective - lemathou@free.fr »
  * 
  * This file is part of FTNGroupWare.
  * 
  */

// Inclusion des fichiers globaux du FrameWork
//ini_set("include_path",".:/usr/local/phpfadframework:/usr/share/php:/usr/share/pear");

// Base de donnée
define("DB","mysql");
define("DB_VERSION","5.1");
define("DB_PERSISTANT",true);
define("DB_CHARSET","UTF8");
define("DB_ENGINE","MYISAM");
// Identifiants BDD -> A terme les virer dés la première connexion, donc les passer en variable. A voir car ils vont se retrouver dans les fichiers de session...
define("DB_HOST","localhost");
define("DB_PORT","3306");
define("DB_USERNAME","username");
define("DB_PASSWORD","password");
define("DB_BASE","database");

// Server path : root
define("PATH_ROOT","/home/website");
define("PATH_INCLUDE",PATH_ROOT."/include");
define("PATH_LIBRARY",PATH_ROOT."/library");
define("PATH_TEMPLATE",PATH_ROOT."/template");
define("PATH_PAGE",PATH_ROOT."/page");
define("PATH_CACHE",PATH_ROOT."/cache");

// Site URL, Domain name and path
define("SITE_URL","http://website/");
define("SITE_DOMAIN","website");
define("SITE_BASEPATH","/");
define("SITE_SSL_ENABLE",false);
define("SITE_SSL_REDIRECT",false);

// Charset
define("SITE_CHARSET","UTF-8");

// Default lang
define("SITE_MULTILANG", true);
define("SITE_LANG_DEFAULT","fr");
define("SITE_LANG_DEFAULT_ID",2);

// Copyright
define("SITE_COPYRIGHT","");
define("SITE_ORGANISATION","");

// Image options : Jpeg Quality
define("IMG_JPEG_QUALITY", 90);

// Template cache
define("TEMPLATE_CACHE",true);
define("TEMPLATE_CACHE_TIME",300);
define("TEMPLATE_CACHE_MIN_TIME",10);
define("TEMPLATE_CACHE_MAX_TIME",300);

// DEBUG
define("DEBUG_GENTIME",false);
define("DEBUG_LIBRARY",false);
define("DEBUG_LOGIN",false);
define("DEBUG_SESSION",false);
define("DEBUG_MENU",false);
define("DEBUG_TEMPLATE",false);
define("DEBUG_DATAMODEL",false);
define("DEBUG_CACHE",false);

?>
