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

class Spark {

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

		$this->calculateRoute();
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
		$this->data = $data;
		if ($viewPath = $this->views[$name]) {
			include($viewPath);
		}
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
		if (isset($pathArgs[0])) {
			$baseName = strtolower(array_shift($pathArgs));
		}

		$methodName = "index";
		if (isset($pathArgs[0])) {
			$methodName = strtolower(array_shift($pathArgs));
		}

		$data = $pathArgs;


		if (isset($this->controllers[$baseName]) and $controllerPath = $this->controllers[$baseName]) {
			$this->startController($baseName, $methodName, $data);
		} else {
			$this->startController("spark_error", "e404");
		}

	}

}

$SF = new Spark();