<?php

include("zebra_database.class.php");

class Mysql {

	public $debug = false;
	private $connection;

	function __consturct($host, $user, $password, $database) { 

		if (ENVIORNMENT == "development") {
			$this->debug = true;
		}

		$this->connect($host, $user, $password, $database);
	}

	private function connect($host, $user, $password, $databse) {

    	$this->connection = mysql_connect($host, $user, $password);

    	if ($this->connection) {
    		if(!mysql_select_db($database, $this->connection))
    			new SparkError

    	} else {
      		return false;
    	}

  }

}

?>