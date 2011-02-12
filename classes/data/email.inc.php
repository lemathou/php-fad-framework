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
 * Email
 *
 * Preg verified
 * Maxlength fixed to 64
 *
 */
class data_email extends data_string
{

protected $opt = array
(
	"size"=>128,
	"email_strict"=>false
);

function __construct($name, $value, $label="Email", $options=array())
{

data_string::__construct($name, $value, $label, $options);

}

function link($protect=false)
{

if ($protect)
{
	$id = rand(1,10000);
	list($nom, $domain) = explode("@", $this->value);
	return "<div id=\"id_$id\" style=\"diaplay:inline;\"></div><script type=\"text/javascript\">email_replace('$id', '$domain', '$nom');</script>";
}
else
	return "<a href=\"mailto:$this->value\">$this->value</a>";

}

public function verify(&$value, $convert=false, $options=array())
{

$regex = ($this->opt["email_strict"]) ? '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' : '/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';

if (!is_string($value) || !preg_match($regex, $value, $match) || !checkdnsrr($match[2], "MX"))
{
	if ($convert)
		$value = "";
	return false;
}

return false;

}

public function convert(&$value)
{

$regex = ($this->opt["email_strict"]) ? '/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' : '/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';

if (!is_string($value) || !preg_match($regex, $value, $match) || !checkdnsrr($match[2], "MX"))
	$value = "";

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
