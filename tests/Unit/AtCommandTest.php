<?php

namespace Iamgerwin\GsmModem\Tests\Unit;

use Iamgerwin\GsmModem\Commands\AtCommand;
use PHPUnit\Framework\TestCase;

class AtCommandTest extends TestCase
{
    public function test_format_command_adds_carriage_return_and_newline()
    {
        $command = 'AT';
        $formatted = AtCommand::formatCommand($command);

        $this->assertEquals("AT\r\n", $formatted);
    }

    public function test_is_success_response_detects_ok()
    {
        $this->assertTrue(AtCommand::isSuccessResponse('AT\r\nOK'));
        $this->assertTrue(AtCommand::isSuccessResponse('Some response\r\nOK'));
        $this->assertFalse(AtCommand::isSuccessResponse('ERROR'));
    }

    public function test_is_error_response_detects_errors()
    {
        $this->assertTrue(AtCommand::isErrorResponse('ERROR'));
        $this->assertTrue(AtCommand::isErrorResponse('+CME ERROR: 10'));
        $this->assertTrue(AtCommand::isErrorResponse('+CMS ERROR: 304'));
        $this->assertFalse(AtCommand::isErrorResponse('OK'));
    }

    public function test_parse_signal_strength_returns_percentage()
    {
        $response = '+CSQ: 20,0\r\nOK';
        $strength = AtCommand::parseSignalStrength($response);

        $this->assertIsInt($strength);
        $this->assertGreaterThanOrEqual(0, $strength);
        $this->assertLessThanOrEqual(100, $strength);
    }

    public function test_parse_signal_strength_returns_null_for_unknown()
    {
        $response = '+CSQ: 99,99\r\nOK';
        $strength = AtCommand::parseSignalStrength($response);

        $this->assertNull($strength);
    }

    public function test_parse_network_info_extracts_operator_and_mode()
    {
        $response = '+COPS: 0,0,"Vodafone",7\r\nOK';
        $info = AtCommand::parseNetworkInfo($response);

        $this->assertArrayHasKey('operator', $info);
        $this->assertArrayHasKey('mode', $info);
        $this->assertEquals('Vodafone', $info['operator']);
        $this->assertEquals('4G', $info['mode']);
    }
}