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

	public function checkForMess() {
		$this->exec("phpmd lib text codesize,unusedcode,naming,design");
	}
}
