<?php
require_once "IReplacer.php";

/**
 * Replace token starting with @ and replacement them with valid PHP output.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */
class PhpTokenReplacer implements IReplacer{

	public function replace(array $replacements, $content) {
		foreach ($replacements as $key => $replacement) {
			// This replacer will insert an php array syntax the replacement value is an array
			if (is_array($replacement) || is_object($replacement)) {
				$content = str_replace("@{$key}@", var_export($replacement, true), $content);
			} else {
				$content = str_replace("@{$key}@", $replacement, $content);
			}
		}
		return $content;
	}
}
