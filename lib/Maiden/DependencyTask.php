<?php
require_once "phing/Task.php";

/**
 * Checks for dependencies.
 *
 * @version @VERSION-NUMBER@
 * @package Clock
 * @subpackage Tasks
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2010
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class DependencyTask extends Task {

	/**
	 * Name of the dependency to look for.
	 *
	 * Use for $type 'class', 'function', 'file'
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Type of dependency check to perform.
	 *
	 * Currently 'php', 'class', 'function', 'file', 'network'
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Rquired version number.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * File to explictily include.
	 *
	 * @var string
	 */
	private $includeFile;

	/**
	 * Install instructions.
	 *
	 * @var string
	 */
	private $install;

	/**
	 * String used on some types to check the response contains an expected value.
	 *
	 * @var string
	 */
	private $contains;

	/**
	 * Type of dependency check to perform.
	 *
	 * @param string $type The type to be set
	 *
	 * @return null
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Type of dependency check to perform.
	 *
	 * @param string $type The type to be set
	 *
	 * @return null
	 */
	public function setContains($contains) {
		$this->contains = $contains;
	}

	/**
	 * Sets the version to check for.
	 *
	 * @param string $version The value to be set
	 *
	 * @return null
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * Install instructions if the dependency isn't met.
	 *
	 * @param string $install The value to be set
	 *
	 * @return null
	 *
	 * @see $install
	 */
	public function setInstall($install) {
		$this->install = $install;
	}

	/**
	 * The name to look for.
	 *
	 * @param string $name The value to be set
	 *
	 * @return null
	 *
	 * @see $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Sets a file to include.
	 *
	 * @param string $includeFile The value to be set
	 *
	 * @return null
	 */
	public function setIncludeFile($includeFile) {
		$this->includeFile = $includeFile;
	}

	/**
	 * Check for dependency.
	 *
	 * @return boolean If the dependency is met
	 */
	protected function check() {
		switch ($this->type) {
			case "php":
				$this->log("Checking {$this->type} is at least '{$this->version}': ");
				return version_compare(PHP_VERSION, $this->version) >= 0;
			case "class":
				if ($this->includeFile) {
					@include_once($this->includeFile);
				} else {
					@include_once($this->name . ".php");
				}
				$this->log("Checking {$this->type} '{$this->name}': ");
				return class_exists($this->name, true);
			case "function":
				@include_once($this->includeFile);
				$this->log("Checking {$this->type} '{$this->name}': ");
				return function_exists($this->name);
			case "file":
				$this->log("Checking {$this->type} '{$this->name}': ");
				return file_exists($this->name);
			case "executable":
				$this->log("Checking {$this->type} '{$this->name}' contains '{$this->contains}' ");
				exec($this->name, $response, $status);
				$response = implode("\n", $response);
				return $status == 0 && preg_match($this->contains, $response);
			case "network":
				$this->log("NOT IMPLEMENTED");
				return false;
		}
		return false;
	}

	/**
	 * Phing Task standard entry point
	 *
	 * @return null
	 */
	public function main() {
		if ($this->check()) {
			$this->log("Success");
		} else {
			$this->log("Failed");
			if (isset($this->install)) {
				$this->log("Run the following to install:\n\t{$this->install}\n");
			} else {
				$this->log("Please install the requirement dependency and try again.");
			}
			throw new BuildException("Failed");
		}
	}
}