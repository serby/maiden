<?php
/**
 * Interface that all classes that wish to replace content must implement
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
interface IReplacer {

	/**
	 * Replace any replacements values in $subject with the given value.
	 *
	 * @param array $replacements An associative array where key is the search string and value is the replacement string
	 * @param mixed $subject The replacement subject
	 */
	public function replace(array $replacements, $subject);
}
