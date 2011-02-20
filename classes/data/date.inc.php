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
 * Date
 * 
 * using strftime() for the displaying
 * Stored in french format but can be changed
 * Associated to a jquery datepickerUI form and a date DB field
 * 
 */
class data_date extends data_datetime
{

protected $empty_value = "0000-00-00"; // stored as Y-m-d

protected $opt = array
(
	"disp_format" => "%A %d %B %G", // Defined for strftime()
	"form_format" => "d/m/Y", // Defined for date()
	"db_format" => "Y-m-d", // Defined for date()
);

public function db_field_create()
{

return array("type" => "date");

}

public function verify(&$value, $convert=false, $options=array())
{

if (!preg_match("/([0-2][0-9]{3})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/", $value))
{
	if ($convert)
		$this->convert($value, $options);
	return false;
}
else
	return true;

}
function value_to_form()
{

if ($this->nonempty())
{
	return str_replace(array("Y", "m", "d"), explode("-", $this->value), $this->opt["form_format"]);
}
else
	return "";

}

function value_to_db()
{

if ($this->nonempty())
{
	return str_replace(array("Y", "m", "d"), explode("-", $this->value), $this->opt["db_format"]);
}
else
	return "";

}

public function form_field_disp($options=array())
{

return "<input type=\"text\" name=\"".$this->name."\" value=\"".$this->value_to_form()."\" size=\"10\" maxlength=\"10\" class=\"".get_called_class()."\" />";

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
