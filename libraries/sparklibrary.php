<?
class SparkLibrary {
	function __construct(&$spark) {
		$this->spark = $spark;
		if (isset($this->spark->db)) {
			$this->db = &$this->spark->db;
		}
	}
}