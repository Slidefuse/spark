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
 					include($fileName);
	 				$controller = new $baseName($this, $baseName);
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

	function view($viewName, $data = array()) {
		foreach ($this->spark->appDirs as $path) {
			foreach (glob($path."/view/*.php") as $fileName) {
				$this->data = $data;
 				require($fileName);
			}
		}
		return false;
	}
	
}
?>
