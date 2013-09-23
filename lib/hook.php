<?php

class Hook extends SparkLibrary {
	
	public function call() {
		$arguments = func_get_args();
		$method = array_shift($arguments);
		$methodLib = "";

		if (stristr($method, ":")) {
			$split = explode(":", $method);
			$methodLib = $split[0];
			$method = $split[1];
		}

		$retValue = null;

		foreach ($this->libBuffer as $lib) {
			if (!empty($methodLib) and $lib->baseName != $methodLib) { continue; }
			$handler = array($lib, $method);
			if (is_callable($handler)) {
				if ($ret = call_user_func_array($handler, $arguments) and $ret !== null) {
					$retValue = $ret;
				}
			}
		}
		return $retValue;
	}
	
}
?>
