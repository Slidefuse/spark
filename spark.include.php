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

	public $libBuffer = array();
	public $libLookup = array();

	public $appDirs = array();

	function __construct() {
		//Load the Spark Base first, then the client App (up a directory)
		
		$this->loadApp(realpath(__DIR__));
		$this->loadApp(realpath(__DIR__."/../"));

		$this->appDirs = array_reverse($this->appDirs);

		$libArray = $this->getLibraries();
		$this->hook->injectLibraries($libArray);

		$this->hook->call("SetupLibraryInstances", $libArray);

		$this->hook->call("SparkAppInit", $this->appDirs);
		$this->hasInitialized = true;
	}

	public function __get($name) {
		$libs = $this->getLibraries();
		if (isset($libs[$name])) {
			return $libs[$name];
		}
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
		}
	}
	
	public function getLibraries() {
		$ret = array();
		foreach ($this->libBuffer as &$lib) {
			$ret[$lib->baseName] = $lib;
		}
		return $ret;
	}

}

/* Skeleton Classes */

class SparkClass {

	public $baseName;
	public $libBuffer;

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

	function injectLibraries($libs) {
		$this->libBuffer = $libs;
	}

	function __get($name) {
		$libs = $this->libBuffer;
		if (isset($libs[$name])) {
			return $libs[$name];
		}
	}

	function SetupLibraryInstances($libs) {
		$this->libBuffer = $libs;
	}
}

class SparkController extends SparkClass {

}

class SparkLibrary extends SparkClass {

}

$Spark = new SparkLoader();

?>
