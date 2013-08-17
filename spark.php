<?php

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

class SparkController {
	function __construct($spark) {
		$this->spark = $spark;
	}
}


class SparkRouter {
	private $error = false;
	private $server;
	private $pathInfo;
	private $clientInfo;

	function __construct() {
		$this->server = $_SERVER['SERVER_NAME'];
		$this->pathInfo = explode("/",$_SERVER['REQUEST_URI']);
		$this->clientInfo['ip'] = $_SERVER['REMOTE_ADDR'];
	}

	public function routeInfo() {
		$items = count($this->pathInfo)-1;

		$arr = array("server" => $this->server, "controller" => $this->pathInfo[1]);

		if($items > 0) {
			$arr2 = array();

			for ($i = 1; $i <= $items; $i++) {
				$arr2[$i-1] = $this->pathInfo[$i];
			}

			$arr['args'] = $arr2;
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
	Mostly Static SparkLibrary
*/
class SparkLibrary {

	public static $libraries = array();

	public static function register($object) {
		self::$libraries[get_class($object)] = $object;
	}

	public static function get($name) {
		return self::$libraries[$name];
	}

	public static function call($method) {
		$arguments = func_get_args();
		$method = array_shift($arguments);

		$retValue = null;
		foreach (self::$libraries as $library) {
			$handler = array($library, $method);
			if (is_callable($handler)) {
				if ($ret = call_user_func_array($handler, $arguments) and $ret !== null) {
					$retValue = $ret;
				}
			}
		}
		return $retValue;
	}

}

/*
	Main Spark Class
*/
class Spark {

	public $config;

	// Spark Cosntruct Function
	function __construct() {

		$this->config = array();

		$this->libraries = array();
		$this->controllers = array();
		$this->views = array();

		$this->libraryStorage = array();

		$this->sparkPath = realpath(__DIR__);
		$this->appPath = realpath(__DIR__."/..");

		$this->router = new SparkRouter();
		//$this->routeInfo = $this->router->routeInfo(); Efficiency
		$this->ip = $this->router->getIP();

		//Load Base Spark App
		$this->loadApp($this->sparkPath);

		//Check if the child App exists, load that too
		if (file_exists($this->appPath."/app")) {
			$this->loadApp($this->appPath);
		}

		$this->calculateRoute();		
	}

	// Just a function to get a library instance.
	public function getLibrary($name) {
		return $this->libraryStorage[$name];
	}

	// A function to get an instance of a library.
	public function loadLibrary($name) {
		if ($libraryPath = $this->libraryExists($name)) {
			if (!isset($this->libraryStorage[$name])) {
				require($libraryPath);
				$lib = new $name();
			} 
			return $this->libraryStorage[$name];
		}

	}

	// A function to return the router object.
	public function getRouter() {
		return $this->router;
	}

	// A function that loads all the files within an app.
	private function loadApp($path) {
		$appPath = $path."/app";

		$configFile = file_get_contents($path."/app/config.json");
		$configTable = json_decode($configFile, true);

		$this->config = array_merge($this->config, $configTable);

		// Enviornment Config
		if (file_exists($path."/app/config_".ENVIORNMENT.".json")) {
			$configFile = file_get_contents($path."/app/config_".ENVIORNMENT.".json");
			$configTable = json_decode($configFile, true);
			$this->config = array_merge($this->config, $configTable);
		}
		// /x/ End

		foreach (glob($path."/app/includes/*.php") as $filename) {
 			require($filename);
		}

		foreach (glob($path."/app/libraries/*.php") as $filename) {
 			$baseName = strtolower(basename($filename, ".php"));
			$this->libraries[$baseName] = $filename;
		}

		foreach (glob($path."/app/controllers/*.php") as $filename) {
			$baseName = strtolower(basename($filename, ".php"));
			$this->controllers[$baseName] = $filename;
		}

		foreach (glob($path."/app/views/*.php") as $filename) {
			$baseName = strtolower(basename($filename, ".php"));
			$this->views[$baseName] = $filename;
		}


	}

	// A function to render a view.
	public function renderView($name, $data = array()) {
		$name = strtolower($name);
		$this->data = $this->arrayToObject($data);
		if ($viewPath = $this->views[$name]) {
			$SF = $this;
			include($viewPath);
			$SF = null;
		}
		$this->data = null;
	}

	// A helper function to render the header.
	public function renderHeader($title = "SparkTitle") {
		$headerData = array("title" => $title);
		SparkLibrary::call("SparkHeaderData", $headerData);
		$this->renderView("header", $headerData);
	}

	// A helper function to render the footer.
	public function renderFooter() {
		$footerData = array();
		SparkLibrary::call("SparkFooterData", $footerData);
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
				$handler = array($controller, $method);
				if (is_callable($handler)) {
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

	// A utility function to convert an array to an object.
	public function arrayToObject($array) {
		return json_decode(json_encode($array), false);
	}

	// A function to get the $SF Object
	public static function Get() {
		return $GLOBALS['SF'];
	}

}

//Initiate Spark!
$SF = new Spark();

/*
	SparkPath
*/
class SparkPath {
	public static function url($path) {
		$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = $protocol.$_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$url .= ":".$_SERVER['SERVER_PORT'];
		}
		$url .= "/".$path;
		return $url;
	}

	public static function active($controller) {
		//Find a way to get the $SF variable in here so I can access the router thing.
		return false;
	}

	public static function listItem($name, $controller, $path = "") {
		echo "<li ";
		if (self::active($controller)) {
			echo "class=\"active\"";
		}
		echo "><a href=\"".self::url($controller."/".$path)."\">";
		echo $name; 
		echo "</a></li>";
	}
}
