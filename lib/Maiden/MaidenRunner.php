<?php
namespace Maiden;

/**
 * Main Maiden class that handles the reading of the target files and running the target.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class MaidenRunner {

	/**
	 * Default location of custom build files
	 * @var string
	 */
	protected $defaultMaidenFile = "Maiden.php";

	/**
	 * If the duration of the target should be output after execution.
	 *
	 * @var boolean
	 */
	protected $displayDuration = true;

	/**
	 * All output should be set to the logger.
	 *
	 * @var \Piton\Log\DefaultLogger
	 */
	protected $logger;

	/**
	 * The full path of the Maiden.php
	 * @var string
	 */
	protected $realpath;

	public function __construct(\Piton\Log\DefaultLogger $logger) {
		$this->logger = $logger;
		$this->realPath = realpath($this->defaultMaidenFile);
		set_exception_handler(array($this, "exceptionHandler"));
		set_error_handler(array($this, "exceptionErrorHandler"));
	}

	/**
	 * List all the targets in the current build file.
	 */
	public function listTargetDescriptions() {

		echo "Below are all the available Maiden targets for: {$this->realPath}\n";

		$maidenClasses = $this->getMaidenClasses();

		if (count($maidenClasses) < 1) {
			return false;
		}
		foreach ($maidenClasses as $maidenClass) {

			$reflectionClass = new \ReflectionClass($maidenClass);
			$methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

			// If there is one method this it is just the constructor so we can ignore this class
			if (count($methods) <= 1) {
				continue 1;
			}

			$description = $this->cleanComment($reflectionClass->getDocComment());

			echo "\n\t" . $this->splitWords($reflectionClass->getName()) .($description == "" ? "" : " - " . $description) . "\n\n";
			$this->listMethodDescription($reflectionClass);
		}
		echo "\n";
	}

	protected function listMethodDescription(\ReflectionClass $reflectionClass) {
		$methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			$name = $method->getName();
			$description = $this->cleanComment($method->getDocComment());
			if ($method->getDeclaringClass() == $reflectionClass && !$method->isConstructor() && !$method->isDestructor()) {
				echo "\t\t" . $name . ($description == "" ? "" : " - " . $description) . "\n";
			}
		}
	}

	public function listTargets() {
		$maidenClasses = $this->getMaidenClasses();

		if (count($maidenClasses) < 1) {
			return false;
		}
		foreach ($maidenClasses as $maidenClass) {

			$class = new \ReflectionClass($maidenClass);
			$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

			// If there is one method this it is just the constructor so we can ignore this class
			if (count($methods) <= 1) {
				continue 1;
			}

			foreach ($methods as $method) {
				$name = $method->getName();
				if ($method->getDeclaringClass() == $class && !$method->isConstructor() && !$method->isDestructor()) {
					echo $name . "\n";
				}
			}
		}
	}

	protected function splitWords($value) {
		return preg_replace(array("/([A-Z])/", "/\\\/"), array(" $1", " -"), $value);
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

		$this->logger->log("Starting Maiden target '$target'");

		$found = false;

		$maidenClasses = $this->getMaidenClasses();

		// Reversing the sort to force the top level class to checked first for methods.
		//TODO: This might not always ensure the correct result
		rsort($maidenClasses);
		if (count($maidenClasses) > 0) {
			foreach ($maidenClasses as $maidenClass) {

				$reflectionClass = new \ReflectionClass($maidenClass);

				if ($reflectionClass->hasMethod($target)) {
					$reflectionMethod = $reflectionClass->getMethod($target);
					$parameters = $reflectionMethod->getParameters();

					$argCount = count($arguments);

					for ($i =  $argCount; $i < count($parameters); $i++) {
						$arguments[] = readline("Enter value for \$" . ($parameters[$i]->getName()) . ": ");
					}

					$maidenObject = new $maidenClass($this->logger);

					call_user_func_array(array($maidenObject, $target), $arguments);
					$found = true;
					break;
				}
			}
		} else {
			$this->logger->log("Unable to find a Maiden class. Execution of target '$target' failed.");
		}
		if (!$found) {
			echo "Unable to find target '$target'\n";
			exit(1);
		}

		if ($this->displayDuration) {
			$endTime = microtime(true) - $startTime;

			$totalTime = number_format($endTime, 2);

			$this->logger->log("Maiden has finished running target {$target} in: {$totalTime}");
		} else {
			$this->logger->log("Maiden has finished running target {$target}");
		}
	}

	/**
	 * Finds all the custom maiden classes
	 */
	protected function getMaidenClasses() {
		if (!file_exists($this->defaultMaidenFile)) {
			throw new \Exception("Unable to find Maiden file '$this->defaultMaidenFile'");
		}

		$definedClasses = get_declared_classes();
		include $this->defaultMaidenFile;
		return array_diff(get_declared_classes(), $definedClasses);
	}

	public function setDisplayDuration($displayDuration) {
		$this->displayDuration = $displayDuration;
	}

	public function exceptionErrorHandler($number, $message, $filename, $lineNumber ) {
		throw new \ErrorException($message, 0, $number, $filename, $lineNumber);
	}

	/**
	 * Exceptions are also sent to the defined logger.
	 */
	public function exceptionHandler($exception) {
		$this->logger->log($exception, \Piton\Log\DefaultLogger::LEVEL_ERROR);
	}
}
