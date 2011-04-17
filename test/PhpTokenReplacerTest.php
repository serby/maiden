<?php

require_once "../lib/PhpTokenReplacer.php";

class PhpTokenReplacerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var IReplacer
	 */
	protected $replacer;

	public function setup() {
		$this->replacer = new PhpTokenReplacer();
	}

	public function testReplaceHandlesEmptyReplacementsAndEmptyStrings() {
		$this->assertEmpty($this->replacer->replace(array(), ""));
	}

	public function testReplaceHandlesEmptyStrings() {
		$this->assertEmpty($this->replacer->replace(array("Test" => "Hello"), ""));
	}

	public function testReplaceReplacesSingleInstances() {
		$this->assertEquals("Hello", $this->replacer->replace(array("Test" => "Hello"), "@Test@"));
	}

	public function testReplaceReplacesMultipleInstances() {
		$this->assertEquals("HelloHello", $this->replacer->replace(array("Test" => "Hello"), "@Test@@Test@"));
	}

	public function testReplaceReplacesArrayReplacementsInstances() {
		$this->assertEquals(var_export(array(1, 2), true), $this->replacer->replace(array("Test" => array(1, 2)), "@Test@"));
	}
}