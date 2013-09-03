<?php

session_start();

/* 
 _____                  _    
/  ___|                | |   
\ `--. _ __   __ _ _ __| | __
 `--. \ '_ \ / _` | '__| |/ /
/\__/ / |_) | (_| | |  |   < 
\____/| .__/ \__,_|_|  |_|\_\
      | |                    
      |_|                    
*/

switch (ENVIORNMENT) {
	case "development":
		ini_set('display_errors', 1); 
		error_reporting(E_ALL);
		break;

	case "production":
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
		ini_set("display_errors", 0);

	default:
		header("HTTP/1.1 503 Service Unavailable.", true, 503);
		echo "The Spark Application ENVIORNMENT variable is not set.";
		exit(1);
}

class SparkLibrary {
	function __construct(&$spark) {
		$this->spark = $spark;
		if (isset($this->spark->db)) {
			$this->db = &$this->spark->db;
		}
	}
}

class SparkController {
	function __construct(&$spark) {
		$this->spark = $spark;
	}
}

class SparkAppInit {
	function __construct(&$spark) {
		$this->spark = $spark;
	}
}


class SparkRouter {
	private $error = false;
	private $server;
	private $pathInfo;
	private $clientInfo;

	function __construct($base_url) {
		$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		if(strpos($uri, $base_url) == 0) {
			//The baseurl is in the uri. Substring it out and then use the rest.
			$uri = substr($uri, strlen($base_url));
		}
		$this->server = $_SERVER['SERVER_NAME'];
		$this->pathInfo = explode("/",$uri);
		$this->clientInfo['ip'] = $_SERVER['REMOTE_ADDR'];
	}

	public function routeInfo() {
		$items = count($this->pathInfo)-1;
		$arr = array("server" => $this->server, "controller" => $this->pathInfo[0]);

		if($items > 0) {
			$arr2 = array();

			for ($i = 0; $i <= $items; $i++) {
				$arr2[$i] = $this->pathInfo[$i];
			}

			$arr['args'] = $arr2;
		} else {
			$arr['args'] = Array();
		}

		return $arr;
	}

	public function getIP() {
		return $this->clientInfo['ip'];
	}

	public function clientInfo() {
		return $this->clientInfo;
	}
}


/*
	Main Spark Class
*/
class Spark {

	public $config;

	// Spark Cosntruct Function
	function __construct() {

		SparkPath::$SF = $this;

		$this->config = array();

		$this->libraries = array();
		$this->controllers = array();
		$this->views = array();
		$this->libraryStorage = array();

		$this->apps = array();

		$this->controller = null;

		$this->navbar = array();

		$this->sparkPath = realpath(__DIR__);
		$this->appPath = realpath(__DIR__."/../");

		//Load Base Spark App
		$this->loadApp($this->sparkPath);

		//Check if the child App exists, load that too
		$this->loadApp($this->appPath);

		//Load Applications & configs first so we can pass the base_url.
		$this->router = new SparkRouter($this->config["base_url"]);
		
		$this->ip = $this->router->getIP();

		// Tell our apps we're READY!
		$this->callHook("Init");

		$this->calculateRoute();	
	}

	// Just a function to get a library instance.
	public function getLibrary($name) {
		return $this->libraryStorage[$name];
	}

	// A function to get an instance of a library.
	public function loadLibrary($name) {
		$name = strtolower($name);
		if ($libraryPath = $this->libraryExists($name)) {
			if (!isset($this->libraryStorage[$name])) {
				require($libraryPath);
				$this->libraryStorage[$name] = new $name($this);
				$handler = array($this->libraryStorage[$name], "LibraryInit");
				if (is_callable($handler)) {
					$this->libraryStorage[$name]->LibraryInit();
				}
			} 
			return $this->libraryStorage[$name];
		} else {
			echo "Error: Unknown Library `" . $name . "`";
		}

	}

	// A function to return the router object.
	public function getRouter() {
		return $this->router;
	}

	// A function that loads all the files within an app.
	private function loadApp($path) {

		$configFile = file_get_contents($path."/config.json");
		$configTable = json_decode($configFile, true);

		$this->config = array_merge($this->config, $configTable);

		// Enviornment Config
		if (file_exists($path."/config_".ENVIORNMENT.".json")) {
			$configFile = file_get_contents($path."/config_".ENVIORNMENT.".json");
			$configTable = json_decode($configFile, true);
			$this->config = array_merge($this->config, $configTable);
		}
		// /x/ End

		foreach (glob($path."/includes/*.php") as $filename) {
 			require($filename);
		}

		foreach (glob($path."/libraries/*.php") as $filename) {
 			$baseName = strtolower(basename($filename, ".php"));
			$this->libraries[$baseName] = $filename;
		}

		foreach (glob($path."/controllers/*.php") as $filename) {
			$baseName = strtolower(basename($filename, ".php"));
			$this->controllers[$baseName] = $filename;
		}

		foreach (glob($path."/views/*.php") as $filename) {
			$baseName = strtolower(basename($filename, ".php"));
			$this->views[$baseName] = $filename;
		}

		if (file_exists($path."/init.php")) {
			include($path."/init.php");

			$tokens = token_get_all(file_get_contents($path."/init.php"));
			$ctoken = false;
			$cname = "";
			foreach ($tokens as $token) {
				if (is_array($token)) {
					if ($token[0] == T_CLASS) {
						$ctoken = true;
					} elseif ($ctoken and $token[0] == T_STRING) {
						$cname = $token[1];
						break;
					}
				}
			}

			$this->apps[$cname] = new $cname($this);
		}
	}

	// A function to call a hook
	public function callHook($method) {
		$arguments = func_get_args();
		$method = "hook_" . array_shift($arguments);

		$retValue = null;
		foreach ($this->apps as $app) {
			$handler = array($app, $method);
			if (is_callable($handler)) {
				if ($ret = call_user_func_array($handler, $arguments) and $ret !== null) {
					$retValue = $ret;
				}
			}
		}
		return $retValue;
	}

	// A function to render a view.
	public function renderView($name, $data = array()) {
		$name = strtolower($name);

		if (empty($data) and isset($this->controller->data) and !empty($this->controller->data)) {
			$data = $this->controller->data;
		}

		$oldData = isset($this->data) ? $this->data : $data;
		$this->data = $data;

		if ($viewPath = $this->views[$name]) {
			$SF = &$this;
			$CN = &$this->controller;
			include($viewPath);
			$CN = null;
			$SF = null;
		}
		$this->data = $oldData;
	}

	// A helper function to render the header.
	public function renderHeader($title = "SparkTitle") {
		$headerData = array("title" => $title);
		$this->callHook("HeaderData", $headerData);
		$this->renderView("header", $headerData);
	}

	// A helper function to render the footer.
	public function renderFooter() {
		$footerData = array();
		$this->callHook("FooterData", $footerData);
		$this->renderView("footer", $footerData);
	}	

	// A function that ensures a library exists.
	public function libraryExists($name) {
		return isset($this->libraries[$name]) ? $this->libraries[$name] : false;
	}

	// A function that ensures a controller exists.
	public function controllerExists($name) {
		return isset($this->controllers[$name]) ? $this->controllers[$name] : false;
	}

	// A function to start a controller, method, and send the data.
	public function startController($name, $method = "index", $data = array()) {
		if ($controllerPath = $this->controllerExists($name)) {
			// Require the file containing our controller functions
			require($controllerPath);

			// Lets make sure our class exists
			if (class_exists($name)) {

				// Construct our new class.
				$controller = new $name($this);
				$this->callHook("ControllerInit", $controller);
				$handler = array($controller, $method);
				if (is_callable($handler)) {
					$this->controller = $controller;
					call_user_func_array($handler, $data);
				} else {
					$this->startController("spark_error", "e404");
				}
			} else {
				$this->startController("spark_error", "e404");
			}
		} else {
			$this->startController("spark_error", "e404");
		}
	}

	// A function to determine the controller and start it, given the route.
	private function calculateRoute() {
		$routeInfo = $this->router->routeInfo();
		$pathArgs = $routeInfo['args'];

		$baseName = "home";
		if (!empty($pathArgs[0])) {
			$baseName = strtolower(array_shift($pathArgs));
		}

		$methodName = "index";
		if (!empty($pathArgs[0])) {
			$methodName = strtolower(array_shift($pathArgs));
		}

		$data = $pathArgs;

		$this->startController($baseName, $methodName, $data);
	}

	// A utility function to get a case-insensitive item from an array.
	public function arrayGetElement($array, $lookup) {
		foreach ($array as $k => $v) {
			if (strtolower($k) == strtolower($lookup)) {
				return $v;
			}
		}
		return null;
	}

	// A utility function to convert an array to an object.
	public function arrayToObject($array) {
		return json_decode(json_encode($array), false);
	}

	// A function to get the IP address of the client.
	public function getIP() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public function randString($length = 32) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

	// A function to get the $SF Object
	public static function Get() {
		return $GLOBALS['SF'];
	}

}

//Initiate Spark!
$SF = new Spark();

$ROUTER = $SF->getRouter();

/*
	SparkPath
*/
class SparkPath {

	static $SF = false;

	public static function getSF() {
		global $SF;
		return $SF;
	}

	public static function url($path = "") {
		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = $protocol.$_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$url .= ":".$_SERVER['SERVER_PORT'];
		}
		if (!empty($path)) {
			$url .= "/".$path;
		}
		return $url;
	}

	public static function active($link) {
		$args = explode("/", $link);
		$active = ($args[0] == "") ? "home" : $args[0];
		$route = self::$SF->getRouter()->routeInfo();
		$controller = ($route["controller"] == "") ? "home" : $route["controller"];
		return ($active == $controller);
	}

	public static function listItem($name, $controller, $path = "") {
		echo "<li ";
		if (self::active($controller)) {
			echo "class=\"active\"";
		}
		$url = $controller;
		if (!empty($path)) {
			$url .= "/".$path;
		}

		echo "><a href=\"".self::url($url)."\">";
		echo $name; 
		echo "</a></li>";
	}

	public static function redirect($url = "") {
		header("Location: ".self::url($url));
	}
}
