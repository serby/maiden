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
	public function init() {
	}

	/**
	 * Proxy the log call
	 */
	public function log($message, $level = Logger::LEVEL_INFO) {
		$this->logger->log($message, $level);	
	}
}
