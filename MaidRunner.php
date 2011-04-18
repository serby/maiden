<?php
require_once "lib/Logger.php";
require_once "lib/FileLineContentReplacer.php";
require_once "lib/PhpTokenReplacer.php";
require_once "MaidDefault.php";

/**
 * Main Maid class that handles the reading of the target files and running the target.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class MaidRunner {

	/**
	 * Default location of custom build files
	 * @var string
	 */
	protected $defaultMaidFile = "Maid.php";

	/**
	 * All output should be set to the logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * The full path of the Maid.php
	 * @var string
	 */
	protected $realpath;

	public function __construct($logger) {
		$this->logger = $logger;
		$this->realPath = realpath($this->defaultMaidFile);
		set_exception_handler(array($this, "exceptionHandler"));
	}

	/**
	 * List all the targets in the current build file.
	 */
	public function listTargets() {

		echo "Below are all the available Maid targets for: {$this->realPath}\n";

		$maidClasses = $this->getMaidClasses();

		if (count($maidClasses) < 1) {
			return false;
		}
		foreach ($maidClasses as $maidClass) {

			$class = new ReflectionClass($maidClass);
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

			// If there is one method this it is just the constructor so we can ignore this class
			if (count($methods) <= 1) {
				continue 1;
			}

			$description = $this->cleanComment($class->getDocComment());

			echo "\n\t" . $this->splitWords($class->getName()) .($description == "" ? "" : " - " . $description) . "\n\n";

			foreach ($methods as $method) {
				$name = $method->getName();
				$description = $this->cleanComment($method->getDocComment());
				if (!$method->isConstructor() && !$method->isDestructor()) {
					echo "\t\t" . $name . ($description == "" ? "" : " - " . $description) . "\n";
				}
			}
		}
		echo "\n";
	}

	protected function splitWords($value) {
		return preg_replace("/([A-Z])/", " $1", $value);
	}

	protected function cleanComment($comment) {
		$comment = preg_replace("#^.*@.*$#m", "", $comment);
		$comment = preg_replace("#^\s*\**\s*#m", "", $comment);
		$comment = preg_replace("#^\s*/*\**\s*#m", "", $comment);
		$comment = str_replace(array("\r", "\n"), "", $comment);
		return $comment;
	}

	public function run($target, $arguments = array()) {

		$startTime = microtime(true);

		$this->logger->log("Starting Maid target '$target'", Logger::LEVEL_INFO);

		$maidClasses = $this->getMaidClasses();
		if (count($maidClasses) > 0) {
			foreach ($maidClasses as $maidClass) {

				$reflectionClass = new ReflectionClass($maidClass);

				if ($reflectionClass->hasMethod($target)) {
					$reflectionMethod = $reflectionClass->getMethod($target);
					$parameters = $reflectionMethod->getParameters();

					$argCount = count($arguments);

					for ($i =  $argCount; $i < count($parameters); $i++) {
						$arguments[] = readline("Enter value for \$" . ($parameters[$i]->getName()) . ": ");
					}

					$maidObject = new $maidClass($this->logger);

					call_user_method_array($target, $maidObject, $arguments);
					break;
				}
			}
		} else {
			$this->logger->log("Unable to find a Maid class. Execution of target '$target' failed.", Logger::LEVEL_INFO);
		}

		$endTime = microtime(true) - $startTime;

		$totalTime = number_format($endTime, 2);

		$this->logger->log("Maid has finished in: {$totalTime}s", Logger::LEVEL_INFO);
	}

	/**
	 * Finds all the custom maid classes
	 */
	protected function getMaidClasses() {

		if (!file_exists($this->defaultMaidFile)) {
			throw new Exception("Unable to find Maid file '$this->defaultMaidFile'");
		}

		$definedClasses = get_declared_classes();
		include $this->defaultMaidFile;
		return array_diff(get_declared_classes(), $definedClasses);
	}

	/**
	 * Exceptions are also sent to the defined logger.
	 */
	public function exceptionHandler($exception) {
		$this->logger->log($exception, Logger::LEVEL_ERROR);
		exit(1);
	}
}
