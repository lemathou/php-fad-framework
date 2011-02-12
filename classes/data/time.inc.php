<?php

/**
  * $Id: data.inc.php 32 2011-01-24 07:13:42Z lemathoufou $
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
 * Time
 * 
 */
class data_time extends data_string
{

protected $empty_value = "00:00:00";

protected $opt = array("size"=>8);

public function db_field_create()
{

return array("type" => "time");

}

/* Convert */
public function verify(&$value, $convert=false, $options=array())
{

if (!is_string($value) || !preg_match("/^(([01][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])$/",$value))
{
	if ($convert)
		$value = $this->empty_value;
	return false;
}

return true;

}
public function convert(&$value)
{

if (!is_string($value) || !preg_match("/^(([01][0-9])|(2[0-3])):([0-5][0-9]):([0-5][0-9])$/",$value))
	$value = $this->empty_value;

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
