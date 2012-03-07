<?php
/**
 * An sample Maid file with three general targets.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class BasicTargets extends MaidDefault {

	/**
	 * Sets up the project
	 */
	public function build() {
		$this->log("Setup", Logger::LEVEL_INFO);
	}

	/**
	 * Installs the project
	 */
	public function install() {
		$this->log("Install", Logger::LEVEL_INFO);
	}

	/**
	 * Cleans the project
	 */
	public function clean() {
		$this->log("Clean", Logger::LEVEL_INFO);
	}
}
