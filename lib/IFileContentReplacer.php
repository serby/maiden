<?php
/**
 * Interface that all classes that wish to replace file contents must implement
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
interface IFileContentReplacer {

	/**
	 *
	 * Replace any replacements values in file with the given value.
	 *
	 * @param array $replacements An associative array where key is the search string and value is the replacement string
	 * @param string $filename
	 */
	public function replace(array $replacements, $filename);
}
