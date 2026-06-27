<?php



return [

    'default' => env('CARRIER_DEFAULT', 'dhl'),



    'sync_interval_minutes' => (int) env('CARRIER_SYNC_INTERVAL', 15),



    'dhl' => [

        'enabled' => env('DHL_ENABLED', false),

        'sandbox' => env('DHL_SANDBOX', true),

        'test_base_url' => env('DHL_TEST_BASE_URL', 'https://testapi.mngkargo.com.tr'),

        'production_base_url' => env('DHL_PRODUCTION_BASE_URL', 'https://api.mngkargo.com.tr'),

        'client_id' => env('DHL_CLIENT_ID'),

        'client_secret' => env('DHL_CLIENT_SECRET'),

        'customer_number' => env('DHL_CUSTOMER_NUMBER', env('DHL_ACCOUNT_NUMBER')),

        'password' => env('DHL_PASSWORD'),

        'packaging_type' => (int) env('DHL_PACKAGING_TYPE', 3),

        'shipment_service_type' => (int) env('DHL_SHIPMENT_SERVICE_TYPE', 1),

        'payment_type' => (int) env('DHL_PAYMENT_TYPE', 1),

        'delivery_type' => (int) env('DHL_DELIVERY_TYPE', 1),

        'sms_preference1' => (int) env('DHL_SMS_PREFERENCE1', 0),

        'sms_preference2' => (int) env('DHL_SMS_PREFERENCE2', 0),

        'sms_preference3' => (int) env('DHL_SMS_PREFERENCE3', 0),

        'sender' => [

            'name' => env('DHL_SENDER_NAME', env('KOSAR_LEGAL_NAME', 'Koşar')),

            'phone' => env('DHL_SENDER_PHONE', env('KOSAR_PHONE')),

            'email' => env('DHL_SENDER_EMAIL', env('KOSAR_EMAIL', 'info@kosar.com.tr')),

            'address' => env('DHL_SENDER_ADDRESS', env('KOSAR_ADDRESS', 'Nilüfer, Bursa')),

            'city' => env('DHL_SENDER_CITY', 'Bursa'),

            'district' => env('DHL_SENDER_DISTRICT', 'Nilüfer'),

            'postal_code' => env('DHL_SENDER_POSTAL_CODE', '16120'),

        ],

    ],



    'sms' => [

        'enabled' => env('SMS_ENABLED', false),

        'provider' => env('SMS_PROVIDER', 'log'),

        'sender' => env('SMS_SENDER', 'KOSAR'),

        'netgsm' => [

            'usercode' => env('NETGSM_USERCODE'),

            'password' => env('NETGSM_PASSWORD'),

            'header' => env('NETGSM_HEADER', env('SMS_SENDER', 'KOSAR')),

        ],

        'tracking_template' => 'Sayin {customer}, {order_number} siparisiniz kargoya verildi. Takip no: {tracking}. {site}',

    ],



    'status_map' => [

        'dhl' => [

            'submitted' => 'submitted',

            'picked_up' => 'picked_up',

            'in_transit' => 'in_transit',

            'delivered' => 'delivered',

            'returned' => 'returned',

            'cancelled' => 'cancelled',

            'failed' => 'failed',

        ],

    ],

];


