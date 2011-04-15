<?php
require_once "Logger.php";
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

		$this->logger->log("Below are all the available Maid targets for: {$this->realPath}\n", Logger::LEVEL_INFO);

		$maidClasses = $this->getMaidClasses();
		
		if (count($maidClasses) < 1) {
			return false;
		}
		foreach ($maidClasses as $maidClass) {

			$reflection = new ReflectionClass($maidClass);

			$this->logger->log("\t" . $reflection->getName(), Logger::LEVEL_INFO);

			$methods = $reflection->getMethods(); 
			foreach ($methods as $method) {
				$name = $method->getName();
				$description = $method->getDocComment();
				$description = preg_replace("#^\s*\**\s*#m", "", $description);
				$description = preg_replace("#^\s*/*\**\s*#m", "", $description);
				$description = str_replace(array("\r", "\n"), "", $description);
				if (!$method->isConstructor() && !$method->isDestructor() && (!in_array($name, array("init", "log")))) {
					$this->logger->log("\t\t" . $name . ($description == "" ? "" : " - " . $description) , Logger::LEVEL_INFO);
				}
			}
		}
		$this->logger->log("\n", Logger::LEVEL_INFO);
	}

	public function run($target) {
		
		$this->logger->log("Starting Maid...", Logger::LEVEL_INFO);
		
				
		$maidClasses = $this->getMaidClasses();

		if (count($maidClasses) > 0) {
			foreach ($maidClasses as $maidClass) {

				$maidObject = new $maidClass($this->logger);
				$maidObject->init();
				$maidObject->{$target}();

			}
		} else {
			$this->logger->log("Unable to find a Maid class. Execution of target '$target' failed.", Logger::LEVEL_INFO);
		}

		$this->logger->log("Maid finished", Logger::LEVEL_DEBUG);
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
