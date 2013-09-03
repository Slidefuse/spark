<?
class SparkRouter {
	private $error = false;
	private $server;
	private $pathInfo;
	private $clientInfo;

	function __construct($base_url) {
		$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		if(strpos($uri, $base_url) == 0) {
			//The baseurl is in the uri. Substring it out and then use the rest.
			$uri = substr($uri, strlen($base_url));
		}
		//echo $_SERVER['REQUEST_URI'];
		$this->server = $_SERVER['SERVER_NAME'];
		$this->pathInfo = explode("/",$uri);
		$this->clientInfo['ip'] = $_SERVER['REMOTE_ADDR'];
	}

	public function routeInfo() {
		$items = count($this->pathInfo)-1;
		$arr = array("server" => $this->server, "controller" => $this->pathInfo[0]);

		if($items > 0) {
			$arr2 = array();

			for ($i = 0; $i <= $items; $i++) {
				$arr2[$i] = $this->pathInfo[$i];
			}

			$arr['args'] = $arr2;
		} else {
			$arr['args'] = Array();
		}

		return $arr;
	}

	public function getIP() {
		return $this->clientInfo['ip'];
	}

	public function clientInfo() {
		return $this->clientInfo;
	}
}