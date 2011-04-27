<?php
namespace Maiden;
/**
 * Base class that all custom Maiden.php should extend.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class MaidenDefault {

	/**
	 * @var \Piton\Log\DefaultLogger 
	 */
	protected $logger;

	public function __construct(\Piton\Log\DefaultLogger $logger) {
		$this->logger = $logger;
		$this->init();
	}

	/**
	 * Override this method to do general setup instead of the construtor
	 */
	protected function init() {
	}

	/**
	 * Proxy the log call
	 */
	protected function log($message, $level = \Piton\Log\DefaultLogger ::LEVEL_INFO) {
		$this->logger->log($message, $level);
	}

	protected function loadJson($filename) {
		$this->log("Loading JSON from '$filename'", \Piton\Log\DefaultLogger ::LEVEL_DEBUG);

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

	protected function exec($command, $failOnError = true, $supressOutput = false) {
		$this->log("Exec: $command", \Piton\Log\DefaultLogger ::LEVEL_INFO);
		if ($supressOutput) {
			exec($command, $out, $return);
		} else {
			passthru($command, $return);
		}
		if ($failOnError && ($return !== 0)) {
			throw new Exception("exec unsuccessful return code: $return");
		}
	}
}
