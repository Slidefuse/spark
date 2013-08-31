<?php

class SparkInit extends SparkAppInit {

	public function hook_Init() {

		$this->spark->db = $this->spark->loadLibrary("SparkSQL");
		$this->spark->nav = $this->spark->loadLibrary("SparkNav");
	
	}
	
}