<?php

class SparkInit extends SparkAppInit {

	public function hook_Init() {

		$this->db = $this->spark->loadLibrary("SparkSQL");
		

	}
	
}