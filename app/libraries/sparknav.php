<?php

class SparkNav extends SparkLibrary {

	function LibraryInit() {
		$this->elements = array();
	}

	function renderNavbar() {
		$viewData = array('elements' => array());

		foreach ($this->elements as $id => $data) {
			$name = $data["name"];

			if ($data["type"] == "single") {

				$viewInfo = array('type' => "single", 'name' => $name, 'link' => $data['link']);
				if (!isset($data['checkFunc'])) {
					$viewData['elements'][] = $viewInfo;
				}

				if (isset($data['checkFunc']) and $data['checkFunc']()) {
					$viewData['elements'][] = $viewInfo;
				}

			} elseif ($data["type"] == "dropdown") {

				$viewInfo = array('type' => "dropdown", 'name' => $name, 'objects' => array());

				if ((isset($data['checkFunc']) and $data['checkFunc']() !== false) or !isset($data['checkFunc'])) {
					foreach ($data['objects'] as $title => $link) {
						if (gettype($link) == "array") {
							if ($link[1]() !== false) {
								$viewInfo['objects'][$name] = $link[0];
							}
						} else {
							$viewInfo['objects'][$name] = $link;
						}
					}

					$viewData['elements'][] = $viewInfo;
				} 

				
			}
		}

		$this->spark->renderView("sparknavbar", $viewData);
	}

	function addElement($name, $link, $checkFunc = null) {
		$this->elements[] = array('type' => 'single', 'name' => $name, 'link' => $link, 'checkFunc' => $checkFunc);
	}

	function addDropdown($name, $objects, $checkFunc = null) {
		$this->elements[] = array('type' => 'dropdown', 'name' => $name, 'objects' => $objects, 'checkFunc' => $checkFunc);
	}

	function removeElement($name) {
		$this->elements[$name] = null;
	}

}

?>