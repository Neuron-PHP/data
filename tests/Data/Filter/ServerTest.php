<?php

namespace Neuron\Tests\Filter;

use Neuron\Data\Filter\Server;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class ServerTest extends TestCase
{
	use PHPMock;

	public function testFilterScalar()
	{
		// Mock filter_input to return a value for INPUT_SERVER.
		$filterInputMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input');
		$filterInputMock->expects($this->once())
							 ->with(INPUT_SERVER, 'HTTP_HOST')
							 ->willReturn('example.com'); // Simulating the value of $_SERVER['HTTP_HOST'].

		// Call the method and assert the result.
		$result = Server::filterScalar('HTTP_HOST');
		$this->assertEquals('example.com', $result);
	}

	public function testFilterScalarWithNull()
	{
		// Mock filter_input to return null for a missing INPUT_SERVER key.
		$filterInputMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input');
		$filterInputMock->expects($this->once())
							 ->with(INPUT_SERVER, 'MISSING_KEY')
							 ->willReturn(null);

		// Call the method and assert the result.
		$result = Server::filterScalar('MISSING_KEY');
		$this->assertNull($result);
	}

	public function testFilterArray()
	{
		// Mock filter_input_array to return an array of values for INPUT_SERVER.
		$filterInputArrayMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input_array');
		$filterInputArrayMock->expects($this->once())
									->with(
										INPUT_SERVER,
										[
											'HTTP_USER_AGENT' => FILTER_DEFAULT,
											'REMOTE_ADDR' => FILTER_DEFAULT
										]
									)
									->willReturn(
										[
											'HTTP_USER_AGENT' => 'Mozilla/5.0',
											'REMOTE_ADDR' => '127.0.0.1',
										]
									);

		// Call the method and assert the result.
		$result = Server::filterArray(
			[
				'HTTP_USER_AGENT' => FILTER_DEFAULT,
				'REMOTE_ADDR' => FILTER_DEFAULT,
			]
		);

		$this->assertEquals(
			[
				'HTTP_USER_AGENT' => 'Mozilla/5.0',
				'REMOTE_ADDR' => '127.0.0.1',
			],
			$result
		);
	}

	public function testFilterArrayWithNullValues()
	{
		// Mock filter_input_array to return some null values for INPUT_SERVER.
		$filterInputArrayMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input_array');
		$filterInputArrayMock->expects($this->once())
									->with(
										INPUT_SERVER,
										[
											'HTTP_ACCEPT_LANGUAGE' => FILTER_DEFAULT,
											'UNDEFINED_KEY' => FILTER_DEFAULT
										]
									)
									->willReturn(
										[
											'HTTP_ACCEPT_LANGUAGE' => 'en-US',
											'UNDEFINED_KEY' => null, // Simulating missing or null values.
										]
									);

		// Call the method and assert the result.
		$result = Server::filterArray(
			[
				'HTTP_ACCEPT_LANGUAGE' => FILTER_DEFAULT,
				'UNDEFINED_KEY' => FILTER_DEFAULT,
			]
		);

		$this->assertEquals(
			[
				'HTTP_ACCEPT_LANGUAGE' => 'en-US',
				'UNDEFINED_KEY' => null,
			],
			$result
		);
	}
}
