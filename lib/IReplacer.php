<?php
/**
 * Interface that all classes that wish to replace content must implement
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
interface IReplacer {
	public function replace(array $replacements, $content);
}
