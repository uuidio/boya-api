<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'shop_users',
        ],

        'admin_users' => [
            'driver' => 'passport',
            'provider' => 'admin_users',
        ],

        'seller_users' => [
            'driver' => 'passport',
            'provider' => 'seller_users',
        ],

        'shop_users' => [
            'driver' => 'passport',
            'provider' => 'shop_users',
        ],

        'group_users' => [
            'driver' => 'passport',
            'provider' => 'group_users',
        ],

        'live_users' => [
            'driver' => 'passport',
            'provider' => 'live_users',
        ],

        'assistant_users' => [
            'driver' => 'passport',
            'provider' => 'assistant_users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\AdminUsers::class,
        ],

        'admin_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\AdminUsers::class,
        ],

        'seller_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\SellerAccount::class,
        ],

        'shop_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\UserAccount::class,
        ],

        'group_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\GroupManageUser::class,
        ],
        'live_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\LiveUsers::class,
        ],

        'assistant_users' => [
            'driver' => 'eloquent',
            'model' => \ShopEM\Models\AssistantUsers::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

];
