<?php

/**
  * $Id$
  * 
  * Copyright 2008-2011 Mathieu Moulin - lemathou@free.fr
  * 
  * This file is part of PHP FAD Framework.
  * http://sourceforge.net/projects/phpfadframework/
  * 
  * Licence : http://www.gnu.org/copyleft/gpl.html  GNU General Public License
  * 
  */

include PATH_INCLUDE."/header.inc.php";

// Logged as super-admin
if (login()->perm(2))
{

define("ADMIN_OK",true);
// Display admin panel
include PATH_ADMIN."/index.inc.php";

}

// Otherwise... bye bye!
else
{

header("HTTP/1.0 401 Unavailable");
// Display admin login
include PATH_ADMIN."/index_login.inc.php";

}


?>