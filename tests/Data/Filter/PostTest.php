<?php

namespace Neuron\Tests\Filter;

use Neuron\Data\Filter\Post;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class PostTest extends TestCase
{
	use PHPMock;

	public function testFilterScalar()
	{
		// Mock filter_input for INPUT_POST.
		$filterInputMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input');
		$filterInputMock->expects($this->once())
							 ->with(INPUT_POST, 'testKey')
							 ->willReturn('testValue'); // Simulate returning the POST value.

		// Call the method and assert the result.
		$result = Post::filterScalar('testKey');
		$this->assertEquals('testValue', $result);
	}

	public function testFilterScalarWithNull()
	{
		// Mock filter_input to return null for a non-existent key.
		$filterInputMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input');
		$filterInputMock->expects($this->once())
							 ->with(INPUT_POST, 'missingKey')
							 ->willReturn(null);

		// Call the method and verify it returns null.
		$result = Post::filterScalar('missingKey');
		$this->assertNull($result);
	}

	public function testFilterArray()
	{
		// Mock filter_input_array for INPUT_POST.
		$filterInputArrayMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input_array');
		$filterInputArrayMock->expects($this->once())
									->with(
										INPUT_POST,
										[
											'key1' => FILTER_DEFAULT,
											'key2' => FILTER_DEFAULT,
										]
									)
									->willReturn(
										[
											'key1' => 'value1',
											'key2' => 'value2',
										]
									); // Simulate returning an array of POST values.

		// Call the method and assert the result.
		$result = Post::filterArray(
			[
				'key1' => FILTER_DEFAULT,
				'key2' => FILTER_DEFAULT,
			]
		);

		$this->assertEquals(
			[
				'key1' => 'value1',
				'key2' => 'value2',
			],
			$result
		);
	}

	public function testFilterArrayWithNullValues()
	{
		// Mock filter_input_array to return some null values.
		$filterInputArrayMock = $this->getFunctionMock('Neuron\Data\Filter', 'filter_input_array');
		$filterInputArrayMock->expects($this->once())
									->with(
										INPUT_POST,
										[
											'key1' => FILTER_DEFAULT,
											'key2' => FILTER_DEFAULT,
										]
									)
									->willReturn(
										[
											'key1' => 'value1',
											'key2' => null, // Simulate a missing or null POST value.
										]
									);

		// Call the method and verify the result.
		$result = Post::filterArray(
			[
				'key1' => FILTER_DEFAULT,
				'key2' => FILTER_DEFAULT,
			]
		);

		$this->assertEquals(
			[
				'key1' => 'value1',
				'key2' => null,
			],
			$result
		);
	}
}
