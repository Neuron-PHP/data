<?php

namespace Tests\Data\Encryption;

use Neuron\Data\Encryption\OpenSSLEncryptor;
use PHPUnit\Framework\TestCase;

class OpenSSLEncryptorTest extends TestCase
{
	private OpenSSLEncryptor $encryptor;

	protected function setUp(): void
	{
		parent::setUp();
		$this->encryptor = new OpenSSLEncryptor();
	}

	/**
	 * Test that a hex key is properly converted to binary before use
	 */
	public function testHexKeyIsConvertedToBinary(): void
	{
		$hexKey = 'a1b2c3d4e5f67890a1b2c3d4e5f67890a1b2c3d4e5f67890a1b2c3d4e5f67890';
		$plaintext = 'This is a test message';

		// Encrypt with hex key
		$encrypted = $this->encryptor->encrypt( $plaintext, $hexKey );
		$this->assertNotEmpty( $encrypted );

		// Decrypt should work with the same hex key
		$decrypted = $this->encryptor->decrypt( $encrypted, $hexKey );
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that a binary key works correctly
	 */
	public function testBinaryKeyWorksCorrectly(): void
	{
		// 32 bytes binary key
		$binaryKey = random_bytes( 32 );
		$plaintext = 'This is another test message';

		$encrypted = $this->encryptor->encrypt( $plaintext, $binaryKey );
		$this->assertNotEmpty( $encrypted );

		$decrypted = $this->encryptor->decrypt( $encrypted, $binaryKey );
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that mixed case hex keys are handled correctly
	 */
	public function testMixedCaseHexKeyIsConverted(): void
	{
		$hexKeyUpper = 'A1B2C3D4E5F67890A1B2C3D4E5F67890A1B2C3D4E5F67890A1B2C3D4E5F67890';
		$hexKeyLower = strtolower( $hexKeyUpper );
		$hexKeyMixed = 'a1B2c3D4e5F67890A1b2C3d4E5f67890a1B2c3D4e5F67890A1b2C3d4E5f67890';
		$plaintext = 'Test message for case sensitivity';

		// All three should produce the same result
		$encrypted1 = $this->encryptor->encrypt( $plaintext, $hexKeyUpper );
		$encrypted2 = $this->encryptor->encrypt( $plaintext, $hexKeyLower );

		// Decrypt with mixed case should work
		$decrypted = $this->encryptor->decrypt( $encrypted1, $hexKeyMixed );
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test key generation produces valid hex keys
	 */
	public function testGenerateKeyProducesValidHexKey(): void
	{
		$key = $this->encryptor->generateKey();

		// Should be 64 hex characters (32 bytes * 2)
		$this->assertEquals( 64, strlen( $key ) );

		// Should be valid hex
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/i', $key );

		// Should work for encryption
		$plaintext = 'Test with generated key';
		$encrypted = $this->encryptor->encrypt( $plaintext, $key );
		$decrypted = $this->encryptor->decrypt( $encrypted, $key );
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that encryption produces different ciphertext for same plaintext (due to random IV)
	 */
	public function testEncryptionProducesDifferentCiphertextWithSamePlaintext(): void
	{
		$key = $this->encryptor->generateKey();
		$plaintext = 'Same message encrypted twice';

		$encrypted1 = $this->encryptor->encrypt( $plaintext, $key );
		$encrypted2 = $this->encryptor->encrypt( $plaintext, $key );

		// Ciphertexts should be different due to random IVs
		$this->assertNotEquals( $encrypted1, $encrypted2 );

		// But both should decrypt to the same plaintext
		$this->assertEquals( $plaintext, $this->encryptor->decrypt( $encrypted1, $key ) );
		$this->assertEquals( $plaintext, $this->encryptor->decrypt( $encrypted2, $key ) );
	}

	/**
	 * Test that tampering with ciphertext is detected
	 */
	public function testTamperedCiphertextIsDetected(): void
	{
		$key = $this->encryptor->generateKey();
		$plaintext = 'Sensitive data';

		$encrypted = $this->encryptor->encrypt( $plaintext, $key );

		// Tamper with the encrypted data
		$tampered = $encrypted;
		// Change a character in the middle
		$midpoint = intval( strlen( $tampered ) / 2 );
		$tampered[$midpoint] = ( $tampered[$midpoint] === 'A' ) ? 'B' : 'A';

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MAC verification failed' );

		$this->encryptor->decrypt( $tampered, $key );
	}

	/**
	 * Test that wrong key fails decryption
	 */
	public function testWrongKeyFailsDecryption(): void
	{
		$key1 = $this->encryptor->generateKey();
		$key2 = $this->encryptor->generateKey();
		$plaintext = 'Secret message';

		$encrypted = $this->encryptor->encrypt( $plaintext, $key1 );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'MAC verification failed' );

		$this->encryptor->decrypt( $encrypted, $key2 );
	}

	/**
	 * Test empty plaintext encryption
	 */
	public function testEmptyPlaintextEncryption(): void
	{
		$key = $this->encryptor->generateKey();
		$plaintext = '';

		$encrypted = $this->encryptor->encrypt( $plaintext, $key );
		$this->assertNotEmpty( $encrypted ); // Should still have IV and MAC

		$decrypted = $this->encryptor->decrypt( $encrypted, $key );
		$this->assertEquals( '', $decrypted );
	}

	/**
	 * Test that non-hex keys of correct length work as binary
	 */
	public function testNonHexKeyOfCorrectLength(): void
	{
		// 32-byte key that's not hex (contains 'g', 'z', etc)
		$nonHexKey = 'this_is_not_a_hex_key_but_is_32b';
		$this->assertEquals( 32, strlen( $nonHexKey ) );

		$plaintext = 'Test with non-hex key';

		$encrypted = $this->encryptor->encrypt( $plaintext, $nonHexKey );
		$decrypted = $this->encryptor->decrypt( $encrypted, $nonHexKey );

		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that invalid length keys throw exception
	 */
	public function testInvalidKeyLengthThrowsException(): void
	{
		$shortKey = 'too_short';
		$plaintext = 'Test message';

		$this->expectException( \Exception::class );

		$this->encryptor->encrypt( $plaintext, $shortKey );
	}

	/**
	 * Test very long plaintext encryption
	 */
	public function testLongPlaintextEncryption(): void
	{
		$key = $this->encryptor->generateKey();
		// Create a 10KB plaintext
		$plaintext = str_repeat( 'Lorem ipsum dolor sit amet. ', 400 );

		$encrypted = $this->encryptor->encrypt( $plaintext, $key );
		$decrypted = $this->encryptor->decrypt( $encrypted, $key );

		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that the IV is properly extracted from ciphertext
	 */
	public function testIVExtractionFromCiphertext(): void
	{
		$key = $this->encryptor->generateKey();
		$plaintext = 'Testing IV extraction';

		$encrypted = $this->encryptor->encrypt( $plaintext, $key );

		// The encrypted format is: IV (16 bytes) + encrypted data + MAC (32 bytes)
		// After base64 decode, first 16 bytes should be the IV
		$decoded = base64_decode( $encrypted );
		$this->assertGreaterThanOrEqual( 48, strlen( $decoded ) ); // At least IV + MAC

		// Should decrypt successfully
		$decrypted = $this->encryptor->decrypt( $encrypted, $key );
		$this->assertEquals( $plaintext, $decrypted );
	}

	/**
	 * Test that MAC is correctly generated and verified
	 */
	public function testMACGenerationAndVerification(): void
	{
		$key = $this->encryptor->generateKey();
		$plaintext = 'Testing MAC';

		$encrypted = $this->encryptor->encrypt( $plaintext, $key );

		// The MAC is the last 32 bytes of the decoded ciphertext
		$decoded = base64_decode( $encrypted );
		$macLength = 32;
		$mac = substr( $decoded, -$macLength );

		$this->assertEquals( $macLength, strlen( $mac ) );

		// Should decrypt successfully with valid MAC
		$decrypted = $this->encryptor->decrypt( $encrypted, $key );
		$this->assertEquals( $plaintext, $decrypted );
	}
}