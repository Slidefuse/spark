<?php

class Error {

	//TODO: get forwarding (if any) from the main file to allow us to forward to error handling services. (LIKE FISHERMAN)
	private $parent; //ex. MySQL
	private $message; //ex. Could not connect
	private $details; //ex. Server replied: username invalid.

	function __consturct($parent, $message, $details) {
		$this->parent = $parent;
		$this->message = $message;
		$this->details = $details;
	}

}

SparkLibrary::register(new SparkError());

?>