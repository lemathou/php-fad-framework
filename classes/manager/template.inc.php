<?php

/**
  * $Id$
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


class __template_manager extends _manager
{

protected $type = "template";

protected $info_detail = array
(
	"type"=>array("label"=>"Type", "type"=>"select", "lang"=>false, "default"=>"page", "select_list"=> array("container"=>"Conteneur principal","inc"=>"Inclusion fréquente","page"=>"Contenu de page","datamodel"=>"Vue de requête de datamodel (liste)","dataobject"=>"Vue de dataobject")),
	"name"=>array("label"=>"Nom (unique par type)", "type"=>"string", "size"=>32, "lang"=>false),
	"label"=>array("label"=>"Label", "type"=>"string", "size"=>128, "lang"=>true),
	"description"=>array("label"=>"Description", "type"=>"text", "lang"=>true),
	"mime"=>array("label"=>"Type de contenu (MIME)", "type"=>"string", "size"=>128, "lang"=>false, "default"=>"text/html"),
	"cache_mintime"=>array("label"=>"Durée minimum du cache", "type"=>"integer", "lang"=>false, "default"=>TEMPLATE_CACHE_MIN_TIME),
	"cache_maxtime"=>array("label"=>"Durée maximum du cache", "type"=>"integer", "lang"=>false, "default"=>TEMPLATE_CACHE_MAX_TIME),
	"login_dependant"=>array("label"=>"Dépendant du login", "type"=>"boolean", "lang"=>false, "default"=>"0"),
	//"library_list"=>array("label"=>"Librairies", "type"=>"object_list", "object_type"=>"library", "db_table"=>"_template_library_ref", "db_id"=>"template_id", "db_field"=>"library_id"),
	"tplfile"=>array("label"=>"Template", "type"=>"script", "folder"=>PATH_TEMPLATE, "filename"=>"{type}/{name}.tpl.php"),
	"script"=>array("label"=>"Script", "type"=>"script", "folder"=>PATH_TEMPLATE, "filename"=>"{type}/{name}.inc.php")
);

protected $info_required = array("name", "type");

protected $retrieve_details = false;

protected function list_update()
{

$this->list_name = array();

foreach($this->list_detail as $id=>$info)
{
	if ($info["type"])
		$this->list_name[$info["type"]."/".$info["name"]] = $id;
	else
		$this->list_name[$info["name"]] = $id;
}

}

protected function construct_object($id)
{

if ($this->retrieve_details)
{
	$query = false;
	$info = $this->list_detail[$id];
}
else
{
	$query = true;
	$info = array();
}

if ($this->list_detail[$id]["type"] == "container")
	return new _template_container($id, $query, $info);
elseif ($this->list_detail[$id]["type"] == "dataobject")
	return new _template_dataobject($id, $query, $info);
elseif ($this->list_detail[$id]["type"] == "datamodel")
	return new _template_datamodel($id, $query, $info);
else
	return new _template($id, $query, $info);

}

protected function query_info_more()
{

// Params
$this->query_params();

}

/**
 * Retrieve parameters from database
 * @param boolean $reset
 */
protected function query_params($reset=false)
{

if ($reset)
{
	foreach($this->list_detail as $id=>&$info)
	{
		$info["param_list"] = null;
	}
}

$query = db()->query("SELECT t1.`template_id`, t1.`name`, t1.`datatype`, t1.`value`, t2.`label` FROM `_template_params` as t1 LEFT JOIN `_template_params_lang` as t2 ON t1.template_id=t2.template_id AND t1.name=t2.name AND t2.lang_id='".SITE_LANG_DEFAULT_ID."' ORDER BY t1.template_id, t1.`order` ASC");
while ($param = $query->fetch_assoc())
{
	$this->list_detail[$param["template_id"]]["param_list"][$param["name"]] = array
	(
		"datatype"=>$param["datatype"],
		"value"=>json_decode($param["value"], true),
		"label"=>$param["label"],
		"opt"=>array()
	);
}
$query_opt = db()->query("SELECT `template_id`, `name`, `optname`, `optvalue` FROM `_template_params_opt`");
while ($opt = $query_opt->fetch_assoc())
{
	if (isset($this->list_detail[$opt["template_id"]]))
		$this->list_detail[$opt["template_id"]]["param_list"][$opt["name"]]["opt"][$opt["optname"]] = json_decode($opt["optvalue"], true);
}

}

}


/**
 * Defines the display of the page, based on database infos and a template file
 * 
 */
class __template extends _object
{

protected $_type = "template";

protected $type = "";
protected $name = "";

protected $mime = "";

/*
 * Surely depreacated, except if I implement function libraries
 */
//protected $library_list = array();

/*
 * Complete list of params
 * @var array
 */
protected $param_list = array();
/*
 * Effective params, using data fields
 * @var array
 */
protected $param = array();

/*
 * Filename of the PHP source template file
 * @var string
 */
protected $tpl_filename = "";
/*
 * Filename of the PHP script
 * @var string
 */
protected $script_filename = "";
/*
 * Subtemplate list
 * @var array
 */
protected $subtemplate = array();

/*
 * Cache related infos
 * @var integer
 */
protected $cache_mintime = 0;
/*
 * @var integer
 */
protected $cache_maxtime = 0;
/*
 * @var integer
 */
protected $login_dependant = 0;
/*
 * Unique cache ID to store and retrieve each filled template
 */
protected $cache_id = "";
/*
 * TODO : use distinct functions as for object cache, if possible
 * Cache folder name
 * @var string
 */
protected $cache_folder = "";
/*
 * Cache filename
 * @var string
 */
protected $cache_filename = "";

function __sleep()
{

//"library_list",
return array("id", "name", "label", "mime", "description", "type", "cache_mintime", "cache_maxtime", "login_dependant", "param_list");

}
function __wakeup()
{

$this->tpl_filename_update();
$this->params_construct();

}

protected function construct_more($infos)
{

$this->tpl_filename_update();
$this->params_construct();

}

protected function tpl_filename_update()
{

if ($this->type)
{
	$this->tpl_filename = PATH_TEMPLATE."/".$this->type."/".$this->name.".tpl.php";
	$this->script_filename = PATH_TEMPLATE."/".$this->type."/".$this->name.".inc.php";
}
else
{
	$this->tpl_filename = PATH_TEMPLATE."/".$this->name.".tpl.php";
	$this->script_filename = PATH_TEMPLATE."/".$this->name.".inc.php";
}

}

protected function params_construct()
{

$this->param = array();
foreach ($this->param_list as $name=>$param)
{
	$datatype = "data_".$param["datatype"];
	$this->param[$name] = new $datatype($name, $param["value"], $param["label"]);
	foreach ($param["opt"] as $i=>$j)
		$this->param[$name]->opt_set($i, $j);
}

}

protected function query_info_more()
{

$this->query_params();

}
/**
 * Retrieve parameter list from database
 */
protected function query_params()
{

// Params
$this->param_list = array();
$query = db()->query("SELECT t1.`name`, t1.`datatype`, t1.`value`, t2.`label` FROM `_template_params` as t1 LEFT JOIN `_template_params_lang` as t2 ON t1.template_id=t2.template_id AND t1.name=t2.name AND t2.lang_id='".SITE_LANG_DEFAULT_ID."' WHERE t1.`template_id`='".$this->id."' ORDER BY t1.`order` ASC");
while ($param = $query->fetch_assoc())
{
	$this->param_list[$param["name"]] = array("datatype"=>$param["datatype"], "value"=>json_decode($param["value"], true), "label"=>$param["label"], "opt"=>array());
}
$query_opt = db()->query("SELECT `name`, `optname`, `optvalue` FROM `_template_params_opt` WHERE `template_id`='".$this->id."'");
while ($opt = $query_opt->fetch_assoc())
{
	$this->param_list[$opt["name"]]["opt"][$opt["optname"]] = json_decode($opt["optvalue"], true);
}
$this->params_construct();

}

/**
 * Returns the list of params
 * @return array
 */
public function param_list()
{

return $this->param;

}
/**
 * Returns the list of params details
 * @return array
 */
public function param_list_detail()
{

return $this->param_list;

}

/**
 * Usage of params from the page or parent template
 * @param string $name
 */
function __isset($name)
{

return (is_string($name) && array_key_exists($name, $this->param));

}
function __get($name)
{

if (is_string($name) && array_key_exists($name, $this->param))
	return $this->param[$name];

}
public function __set($name, $value)
{

if (is_string($name) && array_key_exists($name, $this->param))
{
	if (DEBUG_TEMPLATE)
		echo "<p>DEBUG : template(ID#$this->id)::__set() : $name : ".json_encode($value)."</p>\n";
	$this->param[$name]->value_set($value);
}

}

/**
 * Display template with headers
 */
public function disp()
{

/*
 * TODO : Faire le cumul des last-modified sur l'ensemble des templates marqués comme intervenant dans ce calcul.
 * Nécessite une refonte de la génération des templates
 * TODO : Utiliser une fonction de génération de header à partir du type mime, du last-modified, etc.
 * Utiliser une liste d'options possibles et générer un header consistant
 */
//header('Status: 304 Not Modified', false, 304);
header("Content-type: $this->mime; charset=".SITE_CHARSET);
//header('Last-Modified: '.gmdate('D, d M Y H:i:s',$tpl["regentime"]).' GMT');
//header('Expires: '.gmdate('D, d M Y H:i:s',$tpl["regentime"]+TEMPLATE_CACHE_MINTIME).' GMT');
//header('Content-Length: '.strlen($tpl["html"]));

echo $this->__tostring();

}

/**
 * Returns the displayed template, applying params, etc.
 */
public function __tostring()
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__tostring() [begin]");
//echo "<p>template($this->id)::__tostring()</p>\n";

$this->params_check();

/*
 * Verify if :
 * - cache is enables
 * - template is cacheable
 * - template is not login_dependant
 */
if (TEMPLATE_CACHE && ($this->cache_maxtime > 0) && !($this->login_dependant && login()->id()))
{
	$this->cache_id_set();
	if (isset($_GET["_page_regen"]) || !$this->cache_check())
	{
		$this->cache_generate();
	}
	$return = $this->cache_return();
}
else
{
	$return = $this->execute();
}

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__tostring() [end]");

return $return;

}

/**
 * Reset the template
 * - Params to default value,
 * - empty the calculated template.
 */
function reset()
{

//echo "<p>template(ID#$this->id)::reset()</p>\n";
$this->tpl = "";
$this->cache_id = "";
$this->params_reset();

}
/**
 * Reset params to default value
 */
function params_reset()
{

foreach ($this->param_list as $name=>$param)
{
	$this->param[$name]->value = $param["value"];
}

}

/**
 * Execute optionnal script with given params
 */
protected function params_check()
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::params_check() [begin]");

if (file_exists($this->script_filename))
{
	// Including references !
	extract($this->param);
	//echo "<!-- Template Script : template/$this->name.inc.php -->\n";
	include $this->script_filename;
}

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::params_check() [end]");

}

/**
 * Returns the list of subtemplates of a given template
 * TODO : move this function in datamodel !
 * @tpl string
 * @return array
 */
public static function subtemplates($tpl)
{

$return = array();

if (preg_match_all("/\<!--INCLUDE:([a-zA-Z_\/]*)(,(.*))*--\>/", $tpl, $matches, PREG_SET_ORDER))
{
	$list_name = template()->list_name_get();
	foreach($matches as $match)
	{
		if (template()->exists_name($match[1]))
		{
			$id = $list_name[$match[1]];
			if (isset($match[3]))
			{
				$return[] = array("id"=>$id, "params"=>json_decode($match[3], true));
			}
			else
			{
				$return[] = array("id"=>$id);
			}
		}
	}
}

return $return;

}

/**
 * Set parameters for a subtemplate
 * TODO : protection so that we cannot set random templates...
 * Better to lookup in the page object ..?
 * @param integer $nb
 * @param array $info
 */
public function subtemplate_set($nb, $info)
{

//echo "<p>template(ID#$this->id)::subtemplate_set() : $nb</p>\n";

$this->subtemplate[$nb] = $info;

}

/**
 * Retrieve parameters of a subtemplate
 * @param int $nb
 * @return string
 */
protected function subtemplate($nb)
{

//var_dump($this->subtemplate);

if (array_key_exists((int)$nb, $this->subtemplate) && is_array($subtemplate=$this->subtemplate[(int)$nb]))
{
	return $subtemplate[0].",".json_encode($subtemplate[1]).",".json_encode($subtemplate[2]);
}

}

/**
 * Apply subtemplates to a executed template.
 * @param array $tpl
 * @return string
 */
protected function subtemplates_apply($tpl)
{

//var_dump($this->param);

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::subtemplates_apply() [begin]");

if (preg_match_all("/\<!--INCLUDE:([a-zA-Z_\/]+)(,(true|null|(\{.+\}))){0,1}(,(true|null|(.+))){0,1}--\>/", $tpl, $matches, PREG_SET_ORDER))
{
	$replace_from = $replace_to = array();
	foreach($matches as $match) if ($template=template($match[1]))
	{
		//var_dump($match);
		if (DEBUG_TEMPLATE)
			echo "<p>DEBUG : template(ID#$this->id)->cache_return() sending params to (sub)template ID#".$template->id()." ".$match[1]."</p>\n";
		$template = clone $template;
		// Passage des paramètres
		if (isset($match[3]) && ($params=json_decode($match[3], true)))
		{
			if ($template->info("type") == "dataobject")
			{
				if (is_array($params))
					foreach($params as $name=>$value)
						$template->__set($name, $value);
			}
			elseif ($params === true) // On tente de passer tous les paramètres
			{
				foreach($template->param_list_detail() as $name=>$param)
				{
					if (DEBUG_TEMPLATE)
						echo "<p>--> Looking for param \"$name\" ?</p>\n";
					if (array_key_exists($name, $this->param))
					{
						if (DEBUG_TEMPLATE)
							echo "<p>--> Param : \"$name\"</p>\n";
						$template->__set($name, $this->param[$name]->value);
					}
				}
			}
			elseif (is_array($params))
			{
				foreach($params as $name=>$name_from)
				{
					if (DEBUG_TEMPLATE)
						echo "<p>--> Looking for param \"$name\" ?</p>\n";
					if (array_key_exists($name_from, $this->param))
					{
						if (DEBUG_TEMPLATE)
							echo "<p>--> $name_from : $name</p>\n";
						$template->__set($name, $this->param[$name_from]->value);
					}
				}
			}
		}
		$replace_from[] = $match[0];
		$replace_to[] = (string) $template;
	}
	if (count($replace_from))
	{
		$tpl = str_replace($replace_from, $replace_to, $tpl);
	}
}

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::subtemplates_apply() [end]");

return $tpl;

}

/**
 * Execute a template
 */
protected function execute()
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::execute() [begin]");

//echo "<p>template(#ID$this->id:$this->name)::execute()</p>\n";
extract($this->param);
ob_start();
include $this->tpl_filename;
$return = $this->subtemplates_apply(ob_get_contents());
ob_end_clean();

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::execute() [end]");

return $return;

}

/*
 * Set template hash from params (cache ID) and folder to save/retrieve it
 */
protected function cache_id_set()
{

if ($this->login_dependant)
	$params_str = "$this->id,".login()->id();
else
	$params_str = "$this->id,0";

foreach($this->param as $name=>$param)
{
	$params_str .= ",$name=".json_encode($param->value);
}

/*
 * Set variables
 */
$this->cache_id = md5($params_str);
if (TEMPLATE_CACHE_TYPE == "file")
{
	$this->cache_folder = PATH_CACHE."/".substr($this->cache_id,0,1);
	$this->cache_filename = "$this->cache_folder/$this->cache_id";
}

}

/**
 * Regenerate cache file
 * @protected // TODO
 */
public function cache_generate()
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::cache_generate() [begin]");

if (!file_exists($this->tpl_filename))
	return;

//echo "<p>template(ID#$this->id)::cache_generate() $this->tpl_filename : $this->cache_id</p>\n";
ob_start();
extract($this->param);
include $this->tpl_filename;
if (TEMPLATE_CACHE_TYPE == "apc")
{
	$_time = time();
	apc_store("tpl_$this->cache_id", "<!--GENTIME:$_time-->".ob_get_contents(), TEMPLATE_CACHE_MAX_TIME);
}
else
{
	fwrite(fopen($this->cache_filename,"w"), ob_get_contents());
}
ob_end_clean();

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::cache_generate() [end]");

//fwrite(fopen(PATH_ROOT."/log/cache.txt","a"), date("Y-m-d H:i:s", $_time)." : Write tpl($this->id) params $this->cache_id\n");

}

/**
 * Verify if cache file is up to date
 */
protected function cache_check()
{

$time = time();

if (TEMPLATE_CACHE_TYPE == "apc")
{
	//echo "<p>Fetching tpl_$this->cache_id : ".substr(apc_fetch("tpl_$this->cache_id"), 5, 10)."\n";
	if (!($this->tpl=apc_fetch("tpl_$this->cache_id")))
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Failed retrieving cache file</p>\n";
		return false;
	}
	// Fichier en cache trop récent
	elseif (!($cache_datetime=substr($this->tpl, 5, 10)) || ($cache_datetime+TEMPLATE_CACHE_MIN_TIME) < $time)
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Cache file recently updated -> TRUE</p>\n";
		return true;
	}
	// Fichier template plus récent que le cache
	elseif (($tpl_datetime=filemtime($this->tpl_filename)) > $cache_datetime)
	{
		if (true || DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Template file recently updated ($tpl_datetime) -> $cache_datetime</p>\n";
		return false;
	}
	else
	{
		$return = true;
		reset($this->param_list);
		while($return && (list($name, $param)=each($this->param_list)))
		{
			if ($param["datatype"] == "dataobject" && ($datamodel=datamodel($param["opt"]["datamodel"])))
			{
				if ($datamodel->info("dynamic"))
				{
					if ($datamodel->get($this->param[$name]->value)->_update > $cache_datetime)
						$return=false;
				}
			}
		}
		if (DEBUG_CACHE)
			if ($return)
				echo "<p>template($this->id)::cache_check() : Params not updated -> TRUE</p>\n";
			else
				echo "<p>template($this->id)::cache_check() : Params updated -> FALSE</p>\n";
		return $return;
	}
}
else // (TEMPLATE_CACHE_TYPE == "file")
{
	// Pas de fichier en cache
	if (!file_exists($this->cache_filename))
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Cache file does not exists -> FALSE</p>\n";
		return false;
	}
	// Fichier en cache trop récent
	elseif ((($cache_datetime=filemtime($this->cache_filename))+TEMPLATE_CACHE_MIN_TIME) > ($time=time()))
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Cache file recently updated -> TRUE</p>\n";
		return true;
	}
	// Fichier template plus récent que le cache
	elseif (($tpl_datetime=filemtime($this->tpl_filename)) > $cache_datetime)
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Template file recently updated -> FALSE</p>\n";
		return false;
	}
	// Fichier en cache trop vieux 
	elseif (($cache_datetime+TEMPLATE_CACHE_MAX_TIME) < $time)
	{
		if (DEBUG_CACHE)
			echo "<p>template($this->id)::cache_check() : Cache filename too old -> FALSE</p>\n";
		return false;
	}
	// Paramètres du template modifiés
	{
		$return = true;
		reset($this->param_list);
		while($return && (list($name, $param)=each($this->param_list)))
		{
			if ($param["datatype"] == "dataobject" && ($datamodel=datamodel($param["opt"]["datamodel"])))
			{
				if ($datamodel->info("dynamic") && ($object=$datamodel->get($this->param[$name]->value)))
				{
					if ($object->_update > $cache_datetime)
						$return=false;
				}
			}
			// TODO : dataobject_list
		}
		if (DEBUG_CACHE)
			if ($return)
				echo "<p>template($this->id)::cache_check() : Params not updated -> TRUE</p>\n";
			else
				echo "<p>template($this->id)::cache_check() : Params updated -> FALSE</p>\n";
		return $return;
	}
}

}

/**
 * Returns cached template file using $cache_id
 */
protected function cache_return()
{

if ($this->tpl)
{
	return $this->subtemplates_apply($this->tpl);
}
else
{
	if (TEMPLATE_CACHE_TYPE == "apc")
	{
		//echo "<p>template($this->id)::cache_return() using APC $this->cache_id</p>\n";
		return $this->subtemplates_apply(apc_fetch("tpl_$this->cache_id"));
	}
	else
	{
		//echo "<p>template($this->id)::cache_return() using cache file $this->cache_filename</p>\n";
		$filesize = filesize($this->cache_filename);
		$tpl = ($filesize>0) ? fread(fopen($this->cache_filename, "r"), $filesize) : "";
		return $this->subtemplates_apply($tpl);
	}
}

}

}


/*
 * Specific classes for admin
 */
if (ADMIN_LOAD == true)
{
	include PATH_CLASSES."/manager/admin/template.inc.php";
}
else
{
	class _template_manager extends __template_manager {};
	class _template extends __template {};
}


/**
 * Specific configuration for container (alias primary) templates
 * @author mathieu
 * 
 */
class _template_container extends _template
{

public function __set($name, $value)
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__set() : $name [begin]");

if (DEBUG_TEMPLATE)
	echo "<p>DEBUG : template_container(ID#$this->id)::__set() : $name : ".json_encode($value)."</p>\n";

if (array_key_exists($name, $this->param))
	$this->param[$name]->value = $value;
else
	$this->param[$name] = new data($name, $value, $name);

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__set() : $name [end]");

}

public function cache_id_set()
{

$params_str = "$this->id,".PAGE_ID;

// Each param sent to the template is used, with precision of its name
foreach($this->param as $name=>$param)
{
	$params_str .= ",$name=".json_encode($param->value);
}

/*
 * Set variables
 */
$this->cache_id = md5($params_str);
if (TEMPLATE_CACHE_TYPE == "file")
{
	$this->cache_folder = PATH_CACHE."/".substr($this->cache_id,0,1);
	$this->cache_filename = "$this->cache_folder/$this->cache_id";
}

}

public function params_reset()
{

// We need to delete all params because in this type of template we don't know how many there are
$this->param = array();
foreach($this->param_list as $name=>$param)
{
	$this->param[$name] = new data($name, $param["value"], $param["label"]);
}

}

}


/**
 * Specific configuration for datamodel templates.
 * @author mathieu
 * 
 */
class _template_dataobject extends _template
{

protected $datamodel_id = null;
protected $object_id = null;
protected $object = null;

function __set($name, $value)
{

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__set() : $name [begin]");

// TODO : Not clear, because we need here to configure first the datamodel...

if ($name == "datamodel_id" && ($datamodel=datamodel($value)))
{
	$this->datamodel_id = $datamodel->id();
}
elseif ($name == "object_id" && ($datamodel=datamodel()->get($this->datamodel_id)) && ($object=$datamodel->get($value)))
{
	$this->object_id = $object->id;
	$this->object = $object;
	$this->object_retrieve_values();
}

if (DEBUG_GENTIME == true)
	gentime("template(ID#$this->id)::__set() : $name [end]");

}

function object_set(dataobject $object)
{

$this->object_id = $object->id;
$this->datamodel_id = $object->datamodel()->id;
$this->object = $object;

$this->object_retrieve_values();

}

function object()
{

return $this->object;

}

function datamodel()
{

return datamodel()->get($this->datamodel_id);

}

function object_retrieve_values()
{

if (DEBUG_TEMPLATE)
	echo "<p>[DEBUG] template_datamodel::object_retrieve_values()</p>\n";

$this->param = array();
$this->param["id"] = new data_id();
$this->param["id"]->value = $this->object_id;
foreach(datamodel($this->datamodel_id)->fields() as $field)
{
	$this->param[$field->name] = $this->object->{$field->name};
}

}

protected function cache_id_set()
{

if ($this->object)
	$params_str = "'$this->id','".$this->datamodel_id."','".$this->object_id."'";
else
	$params_str = "'$this->id','0','0'";

/*
 * Set variables
 */
$this->cache_id = md5($params_str);
if (TEMPLATE_CACHE_TYPE == "file")
{
	$this->cache_folder = "cache/".substr($this->cache_id,0,1);
	$this->cache_filename = "$this->cache_folder/$this->cache_id";
}

}

/**
 * Vérifie l'obsolescence du fichier en cache
 */
protected function cache_check()
{

if (TEMPLATE_CACHE_TYPE == "apc")
{
	if (!($this->tpl=apc_fetch("tpl_$this->cache_id")))
	{
		return false;
	}
	// Fichier en cache trop récent
	elseif (!($cache_datetime=substr($this->tpl, 5, 10)) || ($cache_datetime+TEMPLATE_CACHE_MIN_TIME) < $time)
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Cache file recently updated -> TRUE</p>\n";
		return true;
	}
	// Fichier template plus récent que le cache
	elseif (($tpl_datetime=filemtime($this->tpl_filename)) > $cache_datetime)
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Template file recently updated -> FALSE</p>\n";
		return false;
	}
	else
	{
		$return = true;
		if ($this->datamodel()->info("dynamic"))
		{
			if ($this->object->_update > $cache_datetime)
				$return=false;
		}
		if (DEBUG_CACHE)
			if ($return)
				echo "<p>CACHE_CHECK : Params not updated -> TRUE</p>\n";
			else
				echo "<p>CACHE_CHECK : Params updated -> FALSE</p>\n";
		return $return;
	}
}
else
{
	// Pas de fichier en cache
	if (!file_exists($this->cache_filename))
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Cache file does not exists -> FALSE</p>\n";
		return false;
	}
	// FIchier en cache trop récent
	elseif ((($cache_datetime=filemtime($this->cache_filename))+TEMPLATE_CACHE_MIN_TIME) > ($time=time()))
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Cache file recently updated -> TRUE</p>\n";
		return true;
	}
	// Fichier template plus récent que le cache
	elseif (($tpl_datetime=filemtime($this->tpl_filename)) > $cache_datetime)
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Template file recently updated -> FALSE</p>\n";
		return false;
	}
	// Fichier en cache trop vieux 
	elseif (($cache_datetime+TEMPLATE_CACHE_MAX_TIME) < $time)
	{
		if (DEBUG_CACHE)
			echo "<p>CACHE_CHECK : Cache filename too old -> FALSE</p>\n";
		return false;
	}
	// Paramètres du template modifiés
	else
	{
		$return = true;
		if ($this->datamodel()->info("dynamic"))
		{
			if ($this->object->_update > $cache_datetime)
				$return=false;
		}
		if (DEBUG_CACHE)
			if ($return)
				echo "<p>CACHE_CHECK : Params not updated -> TRUE</p>\n";
			else
				echo "<p>CACHE_CHECK : Params updated -> FALSE</p>\n";
		return $return;
	}
}

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
