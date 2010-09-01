<?

/**
  * $Id: session_start.inc.php 76 2009-10-15 09:24:20Z mathieu $
  * 
  * Copyright 2008 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * 
  */

if (DEBUG_GENTIME)
	gentime(__FILE__." [begin]");

session_start();

login()->refresh();

if (DEBUG_GENTIME)
	gentime(__FILE__." [end]");

?>