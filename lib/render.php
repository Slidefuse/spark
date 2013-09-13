<?php

class Render extends SparkLibrary {

	//Called after the router has processed the route!
	function PostProcessRoute($data) {
		$controller = $data["args"][0];
		$method = $data["args"][1];
		$params = array_slice($data["args"], 2);
		$this->loadController($controller, $method, $parameters);
	}

	function loadController($controller, $method, $parameters) {

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
	
}
?>
