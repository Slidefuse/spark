<?php

class Render extends SparkLibrary {

	//Called after the router has processed the route!
	function PostProcessRoute($data) {
		$controller = !empty($data["args"][0]) ? $data["args"][0] : "home";
		$method = !empty($data["args"][1]) ? $data["args"][1] : "home";
		$params = array_slice($data["args"], 2);
		$this->loadController($controller, $method, $params);
	}

	function loadController($controllerName, $method, $parameters) {
		if ($controller = $this->getController($controllerName)) {
			$handler = array($controller, $method);
			if (is_callable($handler)) {
				call_user_func_array($handler, $parameters);
			}
		} else {
			$this->show404();
		}
	}

	function getController($controllerName) {
		foreach ($this->spark->appDirs as $path) {
			foreach (glob($path."/src/*.php") as $fileName) {
 				$baseName = strtolower(basename($fileName, ".php"));
 				if ($baseName == $controllerName) {
 					//Include File
 					require($fileName);
 					//Precache our libraries :)
 					$libs = $this->spark->getLibraries();
 					//Set our default classname.
 					$className = $baseName;
 					//If our custom app class exists, use that!
 					if (class_exists("sf".$baseName)) {
 						$className = "sf".$baseName;
 					}
 					//Initialize the controller
 					$controller = new $className($this, $baseName);
 	 				//Inject our lib array!
	 				$controller->injectLibraries($libs);
	 				//Bam.
	 				return $controller;
	 			}
			}
		}
		return false;
	}

	function show404() {
		$this->showError("404", "Page not found");
	}

	function showError($title, $message) {
		$data = array();
		$data["title"] = $title;
		$data["message"] = $message;
		$this->view("error", $data);
	}

	public function view($viewName, $data = array()) {
		foreach ($this->spark->appDirs as $path) {
			foreach (glob($path."/view/*.php") as $fileName) {
				$baseName = strtolower(basename($fileName, ".php"));
				if ($baseName == $viewName) {
					$this->data = $data;
 					require($fileName);
 					return true;
				}
			}
		}
		return false;
	}

	public function header($title, $data = array()) {
		$data["pageTitle"] = $title;
		$this->view("header", $data);
	}

	public function footer($data = array()) {
		$this->view("footer", $data);
	}

	public function baseurl($path = "") {
		return $this->router->getBaseURL().$path;
	}
	
}
?>
