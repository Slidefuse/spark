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


class SparkLoader {

	public $hasInitialized = false;

	private $libBuffer = array();
	private $libLookup = array();

	public $sharedVars = array();

	public $appDirs = array();

	function __construct() {
		//Load the Spark Base first, then the client App (up a directory)
		
		$this->loadApp(realpath(__DIR__));
		$this->loadApp(realpath(__DIR__."/../"));

		$this->callHook("SetupLibraryInstances");

		$this->callHook("SparkAppInit", $appDirs);
		$this->hasInitialized = true;
	}

	function __get($name) {
		if (array_key_exists($name, $this->sharedVars)) {
			return $this->sharedVars[$name];
		}
		$trace = debug_backtrace();
		trigger_error("Undefined ".$name." in SparkLoader. File ".$trace[0]['file']." Line ".$trace[0]['line']);
	}

	function loadApp($path) {

		array_push($this->appDirs, $path);

		//Find the libraries and initialize them!
		foreach (glob($path."/lib/*.php") as $fileName) {
			require($fileName);
 			$baseName = strtolower(basename($fileName, ".php"));
 			$lib = new $baseName($this, $baseName);
 			$index = array_push($this->libBuffer, $lib);
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
			if (!empty($methodLib) and $lib->baseName != $methodLib) { continue; }
			$handler = array($lib, $method);
			if (is_callable($handler)) {
				if ($ret = call_user_func_array($handler, $arguments) and $ret !== null) {
					$retValue = $ret;
				}
			}
		}
		return $retValue;
	}
	
	function getLibraries() {
		$ret = array();
		foreach ($this->libBuffer as $lib) {
			$ret[$lib->baseName] = &$lib;
		}
		return $ret;
	}

}

/* Skeleton Classes */

class SparkClass {

	public $baseName;

	function __construct($spark, $baseName = "") {
		$this->baseName = get_class($this);
		$this->spark = $spark;

		//Manually setup library instances if Spark is already setup!
		if ($this->spark->hasInitialized) {
			$this->SetupLibraryInstances();
		}

		//Call our custom Constructor.
		if (is_callable(array($this, "SparkConstruct"))) {
			$this->SparkConstruct();
		}
	}

	function SetupLibraryInstances() {
		foreach ($this->spark->getLibraries() as $name => $lib) {
			$this->$name = $lib;
		}
	}

	/* Common App Functions */

	function baseurl($path) {
		return $this->router->getBaseUrl() . $path;
	}

	function callHook() {
		$arguments = func_get_args();
		$handler = array($this->spark, "callHook");
		return call_user_func_array($handler, $arguments);
	}

}

class SparkController extends SparkClass {

}

class SparkLibrary extends SparkClass {

}

$Spark = new SparkLoader();

?>
