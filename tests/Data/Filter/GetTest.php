<?php

namespace Neuron\Tests\Filter;

use Neuron\Data\Filter\Get;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

class GetTest extends TestCase
{
	use PHPMock;

	public function testFilterScalar()
	{
		// Mock filter_input to return a specific value.
		$filterInputMock = $this->getFunctionMock( 'Neuron\Data\Filter', 'filter_input' );
		$filterInputMock->expects( $this->once() )
							 ->with( INPUT_GET, 'testKey' )
							 ->willReturn( 'testValue' ); // Simulating the GET parameter.

		// Call the method and verify the result.
		$result = Get::filterScalar( 'testKey' );
		$this->assertEquals( 'testValue', $result );
	}

	public function testFilterScalarWithNull()
	{
		// Mock filter_input to return null for a non-existent key.
		$filterInputMock = $this->getFunctionMock( 'Neuron\Data\Filter', 'filter_input' );
		$filterInputMock->expects( $this->once() )
							 ->with( INPUT_GET, 'missingKey' )
							 ->willReturn( null );

		// Call the method and verify it returns null.
		$result = Get::filterScalar( 'missingKey' );
		$this->assertNull( $result );
	}

	public function testFilterArray()
	{
		// Mock filter_input_array to return an array of values.
		$filterInputArrayMock = $this->getFunctionMock( 'Neuron\Data\Filter', 'filter_input_array' );
		$filterInputArrayMock->expects( $this->once() )
									->with(
										INPUT_GET,
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
									); // Simulating GET parameters.

		// Call the method.
		$result = Get::filterArray(
			[
				'key1' => FILTER_DEFAULT,
				'key2' => FILTER_DEFAULT,
			]
		);

		// Verify the result.
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
		$filterInputArrayMock = $this->getFunctionMock( 'Neuron\Data\Filter', 'filter_input_array' );
		$filterInputArrayMock->expects( $this->once() )
									->with(
										INPUT_GET,
										[
											'key1' => FILTER_DEFAULT,
											'key2' => FILTER_DEFAULT,
										]
									)
									->willReturn(
										[
											'key1' => 'value1',
											'key2' => null,
											// Simulating a missing or null parameter.
										]
									);

		// Call the method.
		$result = Get::filterArray(
			[
				'key1' => FILTER_DEFAULT,
				'key2' => FILTER_DEFAULT,
			]
		);

		// Verify the result.
		$this->assertEquals(
			[
				'key1' => 'value1',
				'key2' => null,
			],
			$result
		);
	}
}
