<?php

class ExpressionTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Test Laravel\Expression 
	 *
	 * @group laravel
	 */
	public function testConstructReturnValid()
	{
		$expected = "foobar";
		$actual   = new Laravel\Expression($expected);

		$this->assertInstanceOf('\\Laravel\\Expression', $actual);
		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected, $actual->get());
	}
}