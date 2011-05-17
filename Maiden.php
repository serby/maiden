<?php
namespace Maiden;

/**
 * Build for the Maiden tool itself. This is only really used to install and uninstall.
 */
class MaidenProject extends MaidenDefault {

	protected $symlinkPath = "/usr/bin/maiden";
	protected $bashCompletionPath = "/etc/bash_completion.d/";
	protected $completionSymlink = "/etc/bash_completion.d/maiden";

	/**
	 * Installs Maiden on your system
	 */
	public function install() {

		$this->logger->log("Installing maiden.");
		$maidenPath = realpath("./maiden");

		if (!file_exists($this->symlinkPath)) {
			symlink($maidenPath, $this->symlinkPath);
		}

		if (file_exists($this->bashCompletionPath)) {
			$this->logger->log("Installing bash completion");
			file_exists($this->completionSymlink) && unlink($this->completionSymlink);
			symlink(realpath("./maiden-completion.sh"), $this->completionSymlink);
		}
		$this->logger->log("Install complete. Type maiden -h to ensure you have '{$this->symlinkPath}' in your path.");
	}

	/**
	 * Looks for messy code using PHPMD
	 */
	public function checkForMess() {
		$this->exec("phpmd lib text codesize,unusedcode,naming,design");
	}

	/**
	 * Create mess report
	 */
	public function createMessReport() {
		$this->clean();
		$this->exec("phpmd lib xml codesize,unusedcode,naming,design --reportfile build/log/pmd.xml");
	}

	/**
	 * Runs all the phpunit tests
	 */
	public function test() {
		$this->clean();
		chdir("test");
		$this->exec("phpunit --bootstrap PitonBootstrap.php Piton");
	}

	/**
	 * Creates phpunit test reports
	 */
	public function createTestReports() {
		$this->clean();
		chdir("test");
		$this->exec("phpunit --log-junit ../build/log/phpunit.xml --coverage-clover ../build/coverage/coverage.xml --bootstrap PitonBootstrap.php Piton");
	}

	/**
	 * Cleans for build directory and recreates it.
	 */
	public function clean() {
		$this->exec("rm -rf build");
		mkdir("build/coverage", 0775, true);
		mkdir("build/log", 0775, true);
	}

	/**
	 * Validates all the PHP code with php -l
	 */
	public function validate() {
		$path = ".";
		$iterator = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)), "/\.php$/i");
		foreach ($iterator as $filePath) {
			$this->exec("php -l {$filePath}");
		}
	}
}
