<?php
namespace Maiden;

use Piton\Log\DefaultLogger as Logger;
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

	public function __construct(Logger $logger) {
		$this->logger = $logger;
		$this->init();
	}

	/**
	 * Override this method to do general setup instead of the construtor
	 */
	protected function init() {
	}

	protected function loadJson($filename) {
		$this->logger->log("Loading JSON from '$filename'", Logger::LEVEL_DEBUG);
		
		if (!file_exists($filename)) {
			throw new \Exception("Loading '{$filename}' - File does not exist");
		}

		$return = json_decode(file_get_contents($filename));

		switch (json_last_error()) {
			case JSON_ERROR_DEPTH:
				throw new \Exception("Parsing '{$filename}' - Maximum stack depth exceeded");
			break;
			case JSON_ERROR_CTRL_CHAR:
				throw new \Exception("Parsing '{$filename}' - Unexpected control character found");
			break;
			case JSON_ERROR_SYNTAX:
				throw new \Exception("Parsing '{$filename}' - Syntax error, malformed JSON");
			break;
			case JSON_ERROR_NONE:
				break;
		}

		return $return;
	}

	protected function exec($command, $failOnError = true, $returnOutput = false) {
		$this->logger->log("Exec: $command", Logger::LEVEL_INFO);
		$out = "";
		if ($returnOutput) {
			exec($command, $out, $return);
			$out = implode("\n", $out);
		} else {
			passthru($command, $return);
		}
		if ($failOnError && ($return !== 0)) {
			throw new \Exception("exec unsuccessful return code: $return");
		}
		return $out;
	}

	protected function fail($message, $exitCode = 1) {
		$this->logger->log("Failed: " . $message, Logger::LEVEL_ERROR);
		exit($exitCode);
	}
}
