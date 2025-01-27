<?php

namespace Neuron\Tests\Filter;

use Neuron\Data\Filter\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
	protected function setUp(): void
	{
		// Mock the $_SESSION array without starting a session.
		$_SESSION = [];
	}

	protected function tearDown(): void
	{
		// Clear the $_SESSION array without destroying the session.
		$_SESSION = [];
	}

	public function testFilterScalar()
	{
		// Simulate a session variable.
		$_SESSION['testKey'] = '<script>alert("XSS")</script>';

		// Call the method and assert the filtered value.
		$result = Session::filterScalar('testKey');
		$this->assertEquals('<script>alert("XSS")</script>', $result); // Default behavior leaves input unchanged.
	}

	public function testFilterScalarWithNonExistentKey()
	{
		// Call the method with a key that doesn't exist in $_SESSION.
		$result = Session::filterScalar('missingKey');
		$this->assertNull($result); // A missing key should return null.
	}

	public function testFilterArray()
	{
		// Simulate some data for filtering.
		$data = [
			'name' => '<b>John</b>',
			'email' => 'john.doe@example.com',
			'age' => '29',
		];

		// Call the method and check the outputs.
		$result = Session::filterArray($data);

		$this->assertEquals($data, $result); // No filters specified, so the data remains unchanged.
	}

	public function testFilterArrayWithInvalidInput()
	{
		// Simulate invalid input.
		$data = [
			'name' => '<b>John</b>',
			'age' => null, // Simulate a null value.
		];
		$result = Session::filterArray($data);

		$this->assertEquals($data, $result); // Ensure array structure and values are preserved.
	}
}
