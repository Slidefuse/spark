<?php
	
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


class SparkLoader() {

	private $libBuffer = array();
	private $libLookup = array();

	public $sharedVars = array();

	function __construct() {
		//Load the Spark Base first, then the client App (up a directory)
		
		$this->loadApp(realpath(__DIR__));
		$this->loadApp(realpath(__DIR__."/../"));

		$this->callHook("SparkAppInit");
	}

	function __get($name) {
		if (array_key_exists($name, $this->sharedVars)) {
			return $this->sharedVars[$name];
		}
		$trace = debug_backtrace();
		trigger_error("Undefined ".$name." in SparkLoader. File ".$trace[0]['file']." Line ".$trace[0]['line']);
	}

	function loadApp($path) {
		//Find the libraries and initialize them!
		foreach (glob($path."/lib/*.php") as $fileName) {
 			$baseName = strtolower(basename($fileName, ".php"));
 			$lib = new $baseName($this, $baseName);
 			$index = array_push($this->libStorage, $lib);
			$this->libLookup[$baseName] = $index;
			$lib->baseName = $baseName;
			$this->setGlobal($baseName, $this->libLookup[$baseName]);
		}
	}

	function setGlobal($name, &$object) {
		$this->sharedVars[$name] = $object;
	}

	function callHook() {
		$arguments = func_get_args();
		$method = array_shift($arguments);
		$methodLib = "";

		if (stristr($method, ":")) {
			$split = explode(":", $method);
			$methodLib = $split[0];
			$method = $split[1];
		}

		$retValue = null;
		foreach ($this->libBuffer as $lib) {
			if (!empty($lib) and $lib->baseName != $methodLib) { continue; }
			$handler = array($lib, $method);
			if (is_callable()) {
				if ($ret = call_user_func_array($handler, $arguments) and $ret !== null) {
					$retValue = $ret;
				}
			}
		}
		return $retValue;
	}

}

/* Skeleton Classes */

class SparkClass {

	public $baseName;

	function __construct($spark, $baseName = "") {
		$this->baseName = $baseName;
		$this->spark = $spark;

		if (is_callable(array($this, "SparkConstruct"))) {
			$this->SparkConstruct();
		}
	}

	function __get($name) {
		if (array_key_exists($name, $this->spark->sharedVars)) {
			return $this->spark->sharedVars[$name];
		}
		$trace = debug_backtrace();
		trigger_error("Undefined ".$name." in SparkClass Instance. File ".$trace[0]['file']." Line ".$trace[0]['line']);
	}

	function __call($method, $args) {
		if (!isset($this->spark)) { return false; }
		if (!empty($this->spark->sharedVars[$method])) {
			return $this->spark->sharedVars[$method]
		}
		if (!is_callable($handler)) { return false; }
		return call_user_func_array($handler, $args);
	}

}

class SparkController extends SparkClass {

}

class SparkLibrary extends SparkClass {

}

?>
