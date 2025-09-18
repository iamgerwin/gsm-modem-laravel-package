<?php

namespace Iamgerwin\GsmModem\Tests\Unit;

use DateTime;
use Iamgerwin\GsmModem\Models\SmsMessage;
use PHPUnit\Framework\TestCase;

class SmsMessageTest extends TestCase
{
    public function test_create_sms_message_from_at_response()
    {
        $response = '+CMGL: 1,"REC READ","+1234567890",,"24/01/01,10:30:00+00"' . "\n" . 'Hello World';

        $message = SmsMessage::fromAtResponse($response);

        $this->assertInstanceOf(SmsMessage::class, $message);
        $this->assertEquals(1, $message->index);
        $this->assertEquals('+1234567890', $message->sender);
        $this->assertEquals('Hello World', $message->message);
        $this->assertEquals('REC READ', $message->status);
    }

    public function test_create_sms_message_from_pdu()
    {
        $pduData = [
            'sender' => '+1234567890',
            'message' => 'Test message',
            'timestamp' => '2024-01-01 10:30:00',
        ];

        $message = SmsMessage::fromPdu(1, '0000', $pduData);

        $this->assertInstanceOf(SmsMessage::class, $message);
        $this->assertEquals(1, $message->index);
        $this->assertEquals('+1234567890', $message->sender);
        $this->assertEquals('Test message', $message->message);
        $this->assertInstanceOf(DateTime::class, $message->timestamp);
    }

    public function test_sms_message_to_array()
    {
        $message = new SmsMessage(
            index: 1,
            sender: '+1234567890',
            message: 'Test',
            timestamp: new DateTime('2024-01-01 10:30:00'),
            status: 'UNREAD'
        );

        $array = $message->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['index']);
        $this->assertEquals('+1234567890', $array['sender']);
        $this->assertEquals('Test', $array['message']);
        $this->assertEquals('2024-01-01 10:30:00', $array['timestamp']);
        $this->assertEquals('UNREAD', $array['status']);
    }
}