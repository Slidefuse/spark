<?php

class Spark_Error extends SparkController {

	function e404() {
		$this->data['errorName'] = "404";
		$this->data['errorMessage'] = "Page Not Found";

		$this->spark->renderView("sparkerror");
	}
		
}

?>