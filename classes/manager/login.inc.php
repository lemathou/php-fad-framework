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


/**
 * Account
 * 
 */
class _account
{

protected $_db = DB_FW_PROJECT_BASE;

protected $id = 0;
protected $email = ""; // Account identifier, UNIQUE
protected $password_crypt = ""; // TODO : verify and supress it if possible

protected $type;
protected $perm = array();
protected $perm_list = array();

protected $actif = "";

protected $lang_id = SITE_LANG_DEFAULT_ID;
protected $sid = "";

protected $create_datetime = "";
protected $update_datetime = "";

public function __construct($id, $load=true, $infos=array())
{

$query = db()->query("SELECT * FROM `_account` WHERE `id` = '".db()->string_escape($id)."'");
if ($query->num_rows())
{
	$account = $query->fetch_assoc($query);
	foreach ($account as $i=>$j)
		$this->{$i} = $j;
}

}

function __get($name)
{

if (in_array($name, array("id", "email", "lang", "sid", "os")))
	return $this->{$name};
elseif (LOG_ERROR)
	error()->add("login", "Undefined variable '$info'");

}

/**
 * Retrieve usefull info
 */
public function id()
{

return $this->id;

}
public function lang()
{

return $this->lang_id;

}
public function info($name)
{

return $this->__get($name);

}

function __set($name, $value)
{

/*
if (!login()->perm(6))
	die("NOT Authorized to update an user account directly !");
*/
if (isset($this->{$name}))
{
	$this->{$name} = $value;
	return true;
}
else
{
	if (DEBUG_LOGIN)
		trigger_error("DEBUG : account(ID#$this->id)->__get('$name') : does not exists  ");
	return false;
}

}

function query()
{

$query = db()->query("SELECT * FROM `account` WHERE `id`='$this->id'");
if ($infos = $query->fetch_assoc())
{
	foreach($infos as $i=>$j)
		$this->{$i} = $j;
}

}

function update($infos)
{

}

function update_form()
{

}

function password_create()
{

$liste = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";

$password= "";
for ($i=1;$i<8;$i++)
{
	$password .= $liste[rand(0,34)];
}

return $password;
	
}

function exists(string $email)
{

return (db("SELECT '1' FROM `_account` WHERE `email` LIKE '".db()->string_escape($email)."'")->num_rows()) ? true : false;

}

}

// Login

class _login extends _account
{

/*
 * Stats  : page count
 * @var integer
 */
public $page_count = 0;

/*
 * Reseon if disconnected
 * 0 = normal
 * 1 = invalid username
 * 2 = invalid sid
 * 3 = retrieve account problem
 * 4 = invalid password
 * 5 = inactif
 * @var integer
 */
protected $disconnect_reason = 0;

/*
 * Message to be displayed at startup
 * @var string
 */
protected $login_message = "";

/*
 * Browsing parameters
 * @var string
 */
protected $sid="";
/*
 * Operating system
 * @var string
 */
protected $os="???";

// TODO : Add stats (Database, navigation, etc.) here

/**
 * Generates object
 */
public function __construct()
{

$this->sid = session_id();
$this->client_info_query();

//$this->perm_query();

}

/**
 * Refresh login using $_GET or $_POST special fields
 * @global boolean $_GET["_session_restart"]
 * @global boolean $_GET["_session_kill"]
 * @global string $_GET["_login_activate"]
 * @global string $_POST["_login"]["username"]
 * @global string $_POST["_login"]["password_crypt"]
 * @global string $_POST["_login"]["permanent"]
 */
public function refresh()
{

if (DEBUG_GENTIME == true)
	gentime("login::refresh() [begin]");

$this->disconnect_reason = 0;
$this->login_message = "";

// Volontary session restart (and refresh the page)
if (isset($_GET["_session_restart"]))
{
	$_SESSION = array();
	session_regenerate_id();
	session_destroy();
	header("Location: ".$_SERVER["REDIRECT_URL"]);
	die();
}
// Volontary session destruction
elseif (isset($_GET["_session_kill"]))
{
	$_SESSION = array();
	session_regenerate_id();
	session_destroy();
	die("Session successfully Killed. <a href=\"/".SITE_BASEPATH.SITE_LANG_DEFAULT."/\">Back to Home page</a>");
}
// Usual login by form (reserved var _login)
elseif (isset($_POST["_login"]["username"]) && isset($_POST["_login"]["password_crypt"]))
{
	$this->connect($_POST["_login"]["username"], $_POST["_login"]["password_crypt"], (isset($_POST["_login"]["permanent"]))?array("permanent"):array());
}
// Volontary disconnection
elseif (isset($_GET["_login_activate"]))
{
	$this->activate($_GET["_login_activate"]);
}
// Volontary disconnection
elseif (isset($_POST["_login"]["disconnect"]))
{
	$this->disconnect();
}
// Connexion by permanent cookie
elseif (!$this->id && isset($_COOKIE["sid"]) && is_string($_COOKIE["sid"]) && strlen($_COOKIE["sid"]) == 32)
{
	//echo $_COOKIE["sid"];
	$this->connect_sid($_COOKIE["sid"]);
}

if (DEBUG_GENTIME == true)
	gentime("login::refresh() [end]");

}

/**
 * Connect using email and password
 * @param string $email
 * @param string $password_crypt
 * @param array $options
 * @return boolean
 */
protected function connect($email, $password_crypt, $options=array())
{

//echo "SELECT id , actif , password , lang_id , email FROM _account WHERE email LIKE '".db()->string_escape($email)."'";
$query = db()->query("SELECT `id`, `actif`, `password`, `lang_id`, `email` FROM `_account` WHERE `email` LIKE '".db()->string_escape($email)."' ");

if (!$query->num_rows() || !($account = $query->fetch_assoc()))
{
	if (DEBUG_LOGIN)
		echo "<p>EMAIL NOT FOUND</p>\n";
	$this->disconnect(1);
	return false;
}
elseif (!$account["actif"])
{
	if (DEBUG_LOGIN)
		echo "<p>INACTIVE ACCOUNT</p>\n";
	$this->disconnect(5);
	return false;
}
elseif (md5($this->sid."".$account["password"]) != $password_crypt)
{
	if (DEBUG_LOGIN)
		echo "<p>PASSWORD ERROR : ".$account["password"]." : $password_crypt != ".md5($this->sid."".$account["password"])."</p>\n";
	$this->disconnect(4);
	return false;
}
else
{
	if (DEBUG_LOGIN)
		echo "<p>LOGIN OK</p>\n";
	$this->id = $account["id"];
	$this->lang_id = $account["lang_id"];
	$this->email = $account["email"];
	$this->perm_query();
	// Memorize
	if (in_array("permanent", $options))
	{
		db()->query("UPDATE `_account` SET `sid`='".($sid=md5(rand()))."' WHERE id='$this->id' ");
		setcookie("sid", $sid, time()+60*60*24*30); // Durée 30 jours
	}
	return true;
}

}

/**
 * Connect using cookie session ID
 * @param string $sid
 * @return boolean
 */
protected function connect_sid($sid)
{

$query = db()->query("SELECT `id`, `actif`, `password`, `lang_id`, `email` FROM `_account` WHERE `sid` LIKE '".db()->string_escape($sid)."' ");

if (!$query->num_rows() || !($account = $query->fetch_assoc()))
{
	$this->disconnect(2);
	return false;
}
elseif (!$account["actif"])
{
	$this->disconnect(5);
	return false;
}
else
{
	$this->id = $account["id"];
	$this->lang_id = $account["lang_id"];
	$this->email = $account["email"];
	$this->perm_query();
	return true;
}

}

/**
 * Activate an account
 * @param string $hash
 * @return boolean
 */
public function activate($hash)
{

$query = db()->query("SELECT `id`, `actif`, `password`, `lang_id`, `email` FROM `_account` WHERE `actif_hash`='".db()->string_escape($hash)."' AND `actif`='0'");
if ($query->num_rows())
{
	$account = $query->fetch_assoc();
	$this->id = $account["id"];
	$this->lang_id = $account["lang_id"];
	$this->email = $account["email"];
	db()->query("UPDATE `_account` SET `actif`='1', `actif_hash`='' WHERE `id`='$this->id'");
	$this->perm_query();
	$this->login_message = "Votre compte TOP GONES a bien été activé";
	return true;
}
else
	return false;

}

/**
 * Display login messages
 * @return string HTML
 */
public function message_show()
{

if ($this->login_message)
{
?>
<script type="text/javascript">
$(document).ready(function(){
	notify('<?=addslashes($this->login_message)?>');
});
</script>
<?
}

}

protected function reconnect()
{

// VOIR si ça vaut le coup de reconsidérer les permissions.
// Au pire pourquoi pas vérifier si elles n'ont pas changé !

//$this->perm_query();
//databank()->query();

}

/**
 * Disconnect, specifying a reason
 * @param string $dr reason
 */
protected function disconnect($dr=0)
{

if ($dr==1)
	$this->login_message="<div class=\"text\"><h3>Echec de la connexion.</h3><p>Ce compte ne figure pas dans nos bases.</p></div><p><img align=\"center\" src=\"/img/picto/login_3.png\" /></p>";
elseif ($dr==2)
	$this->login_message="<div class=\"text\"><h3>Echec de la connexion.</h3><p>Votre session a expiré, veuillez vous reconnecter.</p></div><p><img align=\"center\" src=\"/img/picto/login_3.png\" /></p>";
elseif ($dr==4)
	$this->login_message="<div class=\"text\"><h3>Echec de la connexion.</h3><p>Mot de passe invalide.</p></div><p><img align=\"center\" src=\"/img/picto/login_3.png\" /></p>";
elseif ($dr==5)
	$this->login_message="<div class=\"text\"><h3>Echec de la connexion.</h3><p>Compte temporairement désactivé, veuillez nous contacter pour plus d&rsquo;information.</p></div><p><img align=\"center\" src=\"/img/picto/login_3.png\" /></p>";
/*
else
	$this->login_message="Erreur d'authentification.";
*/

// Destroy cookie references
if ($this->id)
	db()->query("UPDATE _account SET `sid`='' WHERE id='$this->id' ");
if (isset($_COOKIE["sid"]))
	setcookie ("sid", "", time()-3600);

$this->id = 0;

$this->type = "";
$this->lang_id = 0;
$this->email = "";

$this->perm_query();

$this->disconnect_reason = $dr;

}

/**
 * Query cilent information (OS, browser, etc.)
 */
private function client_info_query()
{

// Liste mobiles : (iPhone|BlackBerry|Android|HTC|LG|MOT|Nokia|Palm|SAMSUNG|SonyEricsson)

if (strstr($_SERVER["HTTP_USER_AGENT"],"Windows") !== FALSE)
	$this->os = "WIN";
elseif (strstr($_SERVER["HTTP_USER_AGENT"],"Mac") !== FALSE)
	$this->os = "MAC";
elseif (strstr($_SERVER["HTTP_USER_AGENT"],"Linux") !== FALSE)
	$this->os = "LIN";
elseif (strstr($_SERVER["HTTP_USER_AGENT"],"BSD") !== FALSE)
	$this->os = "BSD";
elseif (strstr($_SERVER["HTTP_USER_AGENT"],"iPhone") !== FALSE)
	$this->os = "IPH";
else
	$this->os = "???";

}

function language_query()
{



}

function HttpAcceptLanguage($str=NULL)
{
	global $lang_list;
	// getting http instruction if not provided
	$str = $str?$str:$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	// exploding accepted languages 
	$langs = explode(',',$str);
	// creating output list
	$accepted = array();
	foreach ($langs as $lang)
	{
		// parsing language preference instructions
		// 2_digit_code[-longer_code][;q=coefficient]
		ereg('([a-z]{1,2})(-([a-z0-9]+))?(;q=([0-9\.]+))?', $lang, $found);
		// 2 digit lang code
		$code = $found[1];
		// lang code complement
		$morecode=$found[3];
		if (isset($lang_list[$code]) && !in_array($code, $accepted))
			$accepted[sprintf('%3.1f',$found[5]?$found[5]:'1')] = $code;
		elseif (isset($lang_list[$morecode]) && !in_array($code, $accepted))
			$accepted[sprintf('%3.1f',$found[5]?$found[5]:'1')] = $morecode;
	}
	// sorting the list by coefficient desc
	ksort($accepted);
	if (count($accepted)>0)
		return array_pop($accepted);
	else
		return "";
}

private function perm_query()
{

// Specific perms
$this->perm = array();
$query = db()->query("SELECT `type`, `id`, `perm` FROM `_account_perm` WHERE `account_id`='$this->id'");
while(list($type, $id, $perm)=$query->fetch_row())
	$this->perm[$type][$id] = $perm;

// Global perms
$this->perm_list = array();
$query = db()->query("SELECT `perm_id` FROM `_account_perm_ref` WHERE `account_id` = '".$this->id."'");
while (list($perm_id) = $query->fetch_row())
	$this->perm_list[] = $perm_id;

// Régénération du menu puisque nouvelles permissions
//page()->query_info();

// Régénération databank
//datamodel()->query();

}

// Global perm
public function perm($perm)
{

return in_array($perm, $this->perm_list);

}
public function perm_list()
{

return $this->perm_list;

}
// Specific perm
public function user_perm($type, $id)
{

if (isset($this->perm[$type][$id]))
	return $this->perm[$type][$id];
else
	return null;

}

}


if (DEBUG_GENTIME == true)
	gentime(__FILE__." [end]");

?>
