<?php

class Spark extends SparkController {

	function SparkConstruct() {

	}

	function home() {
		$this->render->view("error", array("title" => "Spark Status", "message" => "I guess it's working"));		
	}

}

?>