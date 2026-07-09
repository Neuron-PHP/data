<?php

namespace Data\Setting;

use Neuron\Data\Settings\SettingManager;
use Neuron\Data\Settings\Source;
use Neuron\Data;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public SettingManager $Manager;

	protected function setUp(): void
	{
		$this->Manager = new SettingManager(
			new Source\Ini( 'examples/test.ini' )
		);

		parent::setUp();
	}

	public function testGetSetting()
	{
		$Value = $this->Manager->get( 'test', 'name' );

		$this->assertEquals( 'value', $Value );
	}

	public function testSetSetting()
	{
		$this->Manager->set( 'test', 'newname', 'value' );

		$Value = $this->Manager->get( 'test', 'newname' );

		$this->assertEquals( 'value', $Value );
	}

	public function testFallback()
	{
		$Source = new Source\Ini( 'examples/test.ini' );
		$Fallback = new Source\Env( Data\Env::getInstance('examples/.env' ) );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$Value = $Manager->get( 'test', 'not_there' );

		$this->assertEquals( 'no', $Value );
	}

	public function testGetSectionNames()
	{
		$Sections = $this->Manager->getSectionNames();

		$this->assertEquals( 'test', $Sections[ 0 ] );
	}

	public function testGetSectionSettingNames()
	{
		$Names = $this->Manager->getSectionSettingNames( 'test' );

		$this->assertEquals( 'name', $Names[ 0 ] );
	}

	public function testGetSection()
	{
		$Section = $this->Manager->getSection( 'test' );

		$this->assertIsArray( $Section );
		$this->assertArrayHasKey( 'name', $Section );
		$this->assertEquals( 'value', $Section[ 'name' ] );
	}

	public function testGetSectionNotFound()
	{
		$Section = $this->Manager->getSection( 'nonexistent' );

		$this->assertNull( $Section );
	}

	public function testGetSectionWithFallback()
	{
		$Source = new Source\Ini( 'examples/test.ini' );
		$Fallback = new Source\Env( Data\Env::getInstance('examples/.env' ) );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		// Test getting section from fallback when not in primary source
		$Section = $Manager->getSection( 'test' );

		$this->assertIsArray( $Section );
		$this->assertArrayHasKey( 'name', $Section );
	}

	public function testEmptyStringFallsThroughToFallback()
	{
		$Source = new Source\Memory();
		$Source->set( 'payments', 'secret_key', '' );

		$Fallback = new Source\Memory();
		$Fallback->set( 'payments', 'secret_key', 'sk_test_123' );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$this->assertEquals( 'sk_test_123', $Manager->get( 'payments', 'secret_key' ) );
	}

	public function testEmptyStringReturnedWhenNoSourceHasValue()
	{
		$Source = new Source\Memory();
		$Source->set( 'payments', 'secret_key', '' );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( new Source\Memory() );

		$this->assertSame( '', $Manager->get( 'payments', 'secret_key' ) );
	}

	public function testNonEmptyPrimaryValueStillWinsOverFallback()
	{
		$Source = new Source\Memory();
		$Source->set( 'site', 'name', 'From Config' );

		$Fallback = new Source\Memory();
		$Fallback->set( 'site', 'name', 'From Env' );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$this->assertEquals( 'From Config', $Manager->get( 'site', 'name' ) );
	}

	public function testFalseValueIsNotTreatedAsUnset()
	{
		$Source = new Source\Memory();
		$Source->set( 'pages', 'show_dates', false );

		$Fallback = new Source\Memory();
		$Fallback->set( 'pages', 'show_dates', true );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$this->assertFalse( $Manager->get( 'pages', 'show_dates' ) );
	}

	public function testGetSectionMergesFallbackIntoPrimary()
	{
		$Source = new Source\Memory();
		$Source->set( 'payments', 'provider', 'stripe' );
		$Source->set( 'payments', 'currency', 'usd' );
		$Source->set( 'payments', 'secret_key', '' );

		$Fallback = new Source\Memory();
		$Fallback->set( 'payments', 'secret_key', 'sk_test_123' );
		$Fallback->set( 'payments', 'webhook_secret', 'whsec_456' );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$Section = $Manager->getSection( 'payments' );

		$this->assertIsArray( $Section );
		$this->assertEquals( 'stripe', $Section['provider'] );
		$this->assertEquals( 'usd', $Section['currency'] );
		$this->assertEquals( 'sk_test_123', $Section['secret_key'] );
		$this->assertEquals( 'whsec_456', $Section['webhook_secret'] );
	}

	public function testGetSectionPrimaryOverridesFallback()
	{
		$Source = new Source\Memory();
		$Source->set( 'site', 'name', 'From Config' );

		$Fallback = new Source\Memory();
		$Fallback->set( 'site', 'name', 'From Env' );
		$Fallback->set( 'site', 'url', 'https://env.example.com' );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$Section = $Manager->getSection( 'site' );

		$this->assertEquals( 'From Config', $Section['name'] );
		$this->assertEquals( 'https://env.example.com', $Section['url'] );
	}

	public function testGetSectionOnlyInFallback()
	{
		$Manager = new SettingManager( new Source\Memory() );

		$Fallback = new Source\Memory();
		$Fallback->set( 'payments', 'secret_key', 'sk_test_123' );
		$Manager->setFallback( $Fallback );

		$Section = $Manager->getSection( 'payments' );

		$this->assertIsArray( $Section );
		$this->assertEquals( 'sk_test_123', $Section['secret_key'] );
	}
}
