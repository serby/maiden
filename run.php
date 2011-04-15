<?php
/**
 * CLI entry point for Main
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once "MaidRunner.php";
require_once "Logger.php";

$maidRunner = new MaidRunner(new Logger(Logger::LEVEL_DEBUG));

// Default target
$target = "";

// Commandline options
$options = array(
	"-l" => function() use ($maidRunner) {
		$maidRunner->listTargets();
		return false;
	}
);

foreach ($argv as $arg) {
	if (isset($options[$arg])) {
		if (!$options[$arg]()) {
			exit;
		}
	} else {
		$target = $arg;
	}
}
// Run the chosen target
$maidRunner->run($target);
