<?php
/**
 * Basic logging to stdout 
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class Logger {

	const LEVEL_DEBUG = 1;
	const LEVEL_INFO = 2;
	const LEVEL_WARNING = 3;
	const LEVEL_ERROR = 4;

	protected $level;

	public function __construct($level = self::LEVEL_INFO) {
		$this->level = $level;
	}

	public function log($message, $level = self::LEVEL_INFO) {
		if ($level >= $this->level) {
			echo $message . "\n";
		}
	}
}
