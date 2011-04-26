<?php
require_once "IFileContentReplacer.php";

/**
 * Use the given IReplacer to replace on the contents of a file searching a line at a time
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class FileLineContentReplacer implements IFileContentReplacer {

	/**
	 *
	 * @var IReplacer
	 */
	protected $replacer;

	public function __construct(IReplacer $replacer) {
		$this->replacer = $replacer;
	}

	public function replace(array $replacements, $filename) {

		if (!file_exists($filename)) {
			throw Exception("File not found '{$filname}'");
		}

		$tempName = tempnam(sys_get_temp_dir(), "Maid");
		$tempHandle = fopen($tempName, "w");
		$handle = fopen($filename, "r");

		while (($data = fgets($handle)) !== false) {
			$data = $this->replacer->replace($replacements, $data);
			fwrite($tempHandle, $data);
		}

		fclose($tempHandle);
		fclose($handle);

		copy($tempName, $filename);
		unlink($tempName);
	}
}
