<?php
/**
 * CLI entry point for Main
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */

require_once "MaidRunner.php";
require_once "lib/Logger.php";

$maidRunner = new MaidRunner($logger = new Logger(Logger::LEVEL_INFO));

// Default target
$target = "";

// Commandline options
$options = array(
	"-l" => function($args) use ($maidRunner) {
		$maidRunner->listTargets();
		return false;
	},
	"-v" => function($args) use ($maidRunner, $logger) {
		$logger->setLevel(Logger::LEVEL_DEBUG);
	}
);
if (count($argv) == 1) {
	$argv[] = "-l";
}
foreach ($argv as $arg) {
	if (isset($options[$arg])) {
		if ($options[$arg]($argv) === false) {
			exit;
		}
	} else {
		$target = $arg;
	}
}
// Run the chosen target
$maidRunner->run($target);
