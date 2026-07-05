<?php

return [
    'driver' => env('SMS_DRIVER', 'textbee'),
    
    'twillo' =>[
        "twilio_sid" => env('TWILIO_SID'),
        "twilio_token" => env('TWILIO_TOKEN'),
        "twilio_phone_number" => env('TWILIO_PHONE_NUMBER'),
        "twilio_whatsapp_from" => env('TWILIO_WHATSAPP_FROM'),
    ],
    'textbee' => [
        "textbee_api_key" => env('TEXTBEE_API_KEY'),
        "textbee_device_id" => env('TEXTBEE_DEVICE_ID'),
        'textbee_api_url' => env('TEXTBEE_API_URL'),
    ]
];
