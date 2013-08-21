<?php

class SparkSQL extends SparkLibrary {

	public function __call($method, $args) {
		if (!isset($this->db)) { return false; }
		$handler = array($this->db, $method);
		if (!is_callable($handler)) { return false; }
		return call_user_func_array($handler, $args);
	}

	function LibraryInit() { 
		$this->db = new Mysqlidb($this->spark->config["db_host"], $this->spark->config["db_user"], $this->spark->config["db_pass"], $this->spark->config["db_name"]);
		$this->spark->db = &$this;
	}

}

?>