<?php

use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Neuron\Data\Filter\Cookie;

class CookieTest extends TestCase
{
	use PHPMock; // Enables php-mock functionality.

	public function testFilterScalar()
	{
		// Mock the built-in filter_input function.
		$filterInputMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input');
		$filterInputMock->expects($this->once())
							 ->with(INPUT_COOKIE, 'testCookie')
							 ->willReturn('testValue'); // Mock return value.

		// Call the method.
		$result = Cookie::filterScalar('testCookie');

		// Assert the mocked result.
		$this->assertEquals('testValue', $result);
	}

	public function testFilterArray()
	{
		// Mock the built-in filter_input_array function.
		$filterInputArrayMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input_array');
		$filterInputArrayMock->expects($this->once())
									->with(INPUT_COOKIE, [
										'key1' => FILTER_DEFAULT,
										'key2' => FILTER_DEFAULT,
									])
									->willReturn([
														 'key1' => 'value1',
														 'key2' => 'value2',
													 ]); // Mock return value.

		// Call the method.
		$result = Cookie::filterArray([
													'key1' => FILTER_DEFAULT,
													'key2' => FILTER_DEFAULT,
												]);

		// Assert the mocked result.
		$this->assertEquals([
									  'key1' => 'value1',
									  'key2' => 'value2',
								  ], $result);
	}
}
