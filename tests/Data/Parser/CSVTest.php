<?php

use Neuron\Data\Parser\CSV;

class CSVTest extends PHPUnit\Framework\TestCase
{
	public function testCSV()
	{
		$Parser = new CSV;

		$Headers = array( 'col1', 'col2', 'col3', 'col4' );

		$Result = $Parser->parse( "text1,text2,text3,text4", $Headers );

		$this->assertEquals( 'text1', $Result[ 'col1' ] );
		$this->assertEquals( 'text2', $Result[ 'col2' ] );
		$this->assertEquals( 'text3', $Result[ 'col3' ] );
		$this->assertEquals( 'text4', $Result[ 'col4' ] );
	}

	public function testCSVFail()
	{
		$Parser = new CSV;

		$Headers = array( 'col1', 'col2', 'col3', 'col4' );

		$this->assertNull( $Parser->parse( "text1,text2,text3", $Headers ) );
	}

}
