<?php
/**
 * Base class that all custom Maid.php should extend. 
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class MaidDefault {

	public function __construct($logger) {
		$this->logger = $logger;
	}

	/**
	 * Override this method to do general setup instead of the construtor
	 */
	protected function init() {
	}

	/**
	 * Proxy the log call
	 */
	protected function log($message, $level = Logger::LEVEL_INFO) {
		$this->logger->log($message, $level);	
	}

	protected function loadJson($filename) {

		$return = json_decode(file_get_contents($filename));
	
		switch (json_last_error()) {
			case JSON_ERROR_DEPTH:
				throw new Exception("Parsing '{$filename}' - Maximum stack depth exceeded");
			break;
			case JSON_ERROR_CTRL_CHAR:
				throw new Exception("Parsing '{$filename}' - Unexpected control character found");
			break;
			case JSON_ERROR_SYNTAX:
				throw new Exception("Parsing '{$filename}' - Syntax error, malformed JSON");
			break;
			case JSON_ERROR_NONE:
				break;
		}

		return $return;
	}
}
