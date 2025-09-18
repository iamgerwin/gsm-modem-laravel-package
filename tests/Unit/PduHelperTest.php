<?php

namespace Iamgerwin\GsmModem\Tests\Unit;

use Iamgerwin\GsmModem\Helpers\PduHelper;
use PHPUnit\Framework\TestCase;

class PduHelperTest extends TestCase
{
    public function test_encode_pdu_creates_valid_pdu_string()
    {
        $number = '1234567890';
        $message = 'Hello';

        $pdu = PduHelper::encodePdu($number, $message);

        $this->assertIsString($pdu);
        $this->assertNotEmpty($pdu);
        $this->assertTrue(ctype_xdigit($pdu));
    }

    public function test_decode_pdu_extracts_message_data()
    {
        $pdu = '0791947101670000040C91947156436587000021309141705440054C29B01E';

        $decoded = PduHelper::decodePdu($pdu);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('sender', $decoded);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    public function test_encode_and_decode_consistency()
    {
        $number = '1234567890';
        $message = 'Test message';

        $pdu = PduHelper::encodePdu($number, $message);

        $this->assertIsString($pdu);
        $this->assertNotEmpty($pdu);
    }
}