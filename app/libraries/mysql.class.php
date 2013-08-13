<?php

include("zebra_database.class.php");

class SparkSQL extends Zebra_Database {

	function __consturct($host, $user, $password, $database) { 
		parent::__construct();

		if (ENVIORNMENT == "development") {
			$this->debug = true;
		}

		$this->connect($host, $user, $password, $database);
	}

}

?>