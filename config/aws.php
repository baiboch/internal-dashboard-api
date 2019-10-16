<?php

use Aws\Laravel\AwsServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. This file
    | is published to the application config directory for modification by the
    | user. The full set of possible options are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    */
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID', 'AKIA4QGDRN3KC5R7HQYI'),
        'secret' => env('AWS_SECRET_ACCESS_KEY', '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'),
    ],
    'region' => env('AWS_REGION', 'ap-southeast-1'),
    'version' => '2012-08-10',
    'ua_append' => [
        'L5MOD/' . AwsServiceProvider::VERSION,
    ],
];
