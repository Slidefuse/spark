<?php

class Router extends SparkLibrary {
	
	private $routeInfo = array();
	private $clientInfo = array();
	
	function SparkAppInit($appDirs) {
		$this->processRoute();
	}
	
	function processRoute() {
		$data = array();

		$data["server"] = $this->getServer();
		$data["port"] = $this->getPort();
		$data["args"] = $this->getPath(true);

		$this->hook->callHook("PostProcessRoute", $data);
	}

	function getBaseURL() {
		$uri  = $this->getProtocol();
		$uri .= "://";
		$uri .= $this->getServer();
		if ($this->getPort() != 80) {
			$uri .= ":" . $this->getPort();
		}
		$uri .= "/";
		return $uri;
	}
	
	function getServer() {
		return $_SERVER['SERVER_NAME'];
	}

	function getPort() {
		return $_SERVER['SERVER_PORT'];
	}
	
	function getPath($array = false) {
		$uri = substr($_SERVER['REQUEST_URI'], 1);
		if (!$array) {
			return $uri;
		}
		return explode("/", $this->getPath());
	}
	
	function getProtocol() {
		return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
	}
	
}
?>
