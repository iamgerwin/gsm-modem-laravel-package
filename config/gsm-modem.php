<?php

return [
    'default' => env('GSM_MODEM_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'port' => env('GSM_MODEM_PORT', '/dev/ttyUSB0'),
            'baud_rate' => env('GSM_MODEM_BAUD_RATE', 115200),
            'data_bits' => env('GSM_MODEM_DATA_BITS', 8),
            'parity' => env('GSM_MODEM_PARITY', 'none'),
            'stop_bits' => env('GSM_MODEM_STOP_BITS', 1),
            'flow_control' => env('GSM_MODEM_FLOW_CONTROL', 'none'),
        ],
    ],

    'sms_mode' => env('GSM_MODEM_SMS_MODE', 'TEXT'),

    'pin' => env('GSM_MODEM_PIN', null),

    'auto_connect' => env('GSM_MODEM_AUTO_CONNECT', false),

    'debug' => env('GSM_MODEM_DEBUG', false),

    'timeouts' => [
        'command' => 10000,
        'sms_send' => 30000,
        'ussd' => 30000,
    ],

    'event_listeners' => [
        'new_message' => [],
        'incoming_call' => [],
        'ussd_response' => [],
        'modem_connected' => [],
        'modem_disconnected' => [],
    ],

    'storage' => [
        'preferred' => 'SM',
    ],

    'encoding' => [
        'charset' => 'GSM',
    ],
];