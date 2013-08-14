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
		error_reporting(-1);
		ini_set("display_errors", 1);
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

	public $router;

	function __construct() {

		$this->controllers = array();
		$this->views = array();

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

		SparkLibrary::call("SparkInit");

		$this->calculateRoute();		
	}

	public function getRouter() {
		return $this->router;
	}

	private function loadApp($path) {
		$appPath = $path."/app";
		foreach (glob($path."/app/libraries/*.php") as $filename) {
 		   require($filename);
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

	public function renderView($name, $data = array()) {
		$name = strtolower($name);
		$this->data = $this->arrayToObject($data);
		if ($viewPath = $this->views[$name]) {
			include($viewPath);
		}
		$this->data = null;
	}

	public function renderHeader($title = "SparkTitle") {
		$headerData = array("title" => $title);
		SparkLibrary::call("SparkHeaderData", $headerData);
		$this->renderView("header", $headerData);
	}

	public function renderFooter() {
		$footerData = array();
		SparkLibrary::call("SparkFooterData", $footerData);
		$this->renderView("footer", $footerData);
	}	

	public function startController($name, $method = "index", $data = array()) {
		if (isset($this->controllers[$name]) and $controllerPath = $this->controllers[$name]) {

			require($controllerPath);
			$controller = new Controller($this);

			$handler = array($controller, $method);

			if (is_callable($handler)) {
				call_user_func_array($handler, $data);
			} else {
				$this->startController("spark_error", "e404");
			}
		} else {

			$this->startController("spark_error", "e404");
		}
	}

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

		if (isset($this->controllers[$baseName])) {
			$this->startController($baseName, $methodName, $data);
		} else {
			$this->startController("spark_error", "e404");
		}

	}

	public function arrayToObject($array) {
		return json_decode(json_encode($array), false);
	}

	public static function Get() {
		return $GLOBALS['SF'];
	}

}

//Initiate Spark!
$SF = new Spark();

/*
	SparkPath
*/

class Path {
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
