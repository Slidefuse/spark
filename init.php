<?php

class SparkInit extends SparkAppInit {

	public function hook_Init() {

		$this->spark->db = $this->spark->loadLibrary("SparkSQL");
		$this->spark->nav = $this->spark->loadLibrary("SparkNav");

		$this->spark->nav->addElement("Home", "/");
		
		$this->spark->nav->addDropdown("Dropdown Test", "test", array(
			"Hey" => "test/hey",
			"Logged In" => array("test/loggedin", function() { return true; })
		));

		//$this->spark->nav->removeElement("Dropdown Test");

	}
	
}