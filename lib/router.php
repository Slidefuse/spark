<?php

class SparkRouter extends SparkLibrary {
	
	private $routeInfo = array();
	private $clientInfo = array();
	
	function SparkAppInit() {
		$this->processRoute();
	}
	
	function processRoute() {
		$uri  = $this->getProtocol();
		$uri .= "://";
		$uri .= $this->getServer();
		if ($this->getPort() != 80) {
			$uri .= ":" . $this->getPort();
		}
		$uri .= $this->getPath();
		
	}
	
	function getServer() {
		return $_SERVER['SERVER_NAME'];
	}
	
	function getPath($array = false) {
		$uri = substr($_SERVER['REQUEST_URI'], 1);)
		if (!$array) {
			return $uri;
		}
		return explode("/", $this->getPath())
	}
	
	function getProtocol() {
		return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
	}
	
	function 

}
?>
