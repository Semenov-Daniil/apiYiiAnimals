<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'ru-RU',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'asd',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser',
            ],
        ],
        'response' => [
            // ...
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    // ...
                ],
            ],
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode == 401)
                {
                    return $response->data = [
                        'error' => [
                            'code' => 401,
                            'message' => "Unauthorized",
                        ],
                    ];
                }
            },
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'OPTIONS api/register' => 'main/register',
                'POST api/register' => 'main/register',

                'OPTIONS api/login' => 'main/login',
                'POST api/login' => 'main/login',

                'OPTIONS api/subscription' => 'main/subscription',
                'POST api/subscription' => 'main/subscription',

                'OPTIONS api/search' => 'main/search',
                'GET api/search' => 'main/search',

                'OPTIONS api/districts' => 'main/districts',
                'GET api/districts' => 'main/districts',

                'OPTIONS api/kinds' => 'main/kinds',
                'GET api/kinds' => 'main/kinds',
                [
                    'pluralize' => true,
                    'prefix' => 'api',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'user',
                    'extraPatterns' => [
                        'OPTIONS /' => 'profile',
                        'GET /' => 'profile',
                        'OPTIONS email' => 'change-email',
                        'PATCH email' => 'change-email',
                        'OPTIONS phone' => 'change-phone',
                        'PATCH phone' => 'change-phone',
                        'OPTIONS orders' => 'orders',
                        'GET orders' => 'orders',
                        'OPTIONS orders/<id>' => 'delete-order',
                        'DELETE orders/<id>' => 'delete-order'
                    ],
                ],
                [
                    'pluralize' => true,
                    'prefix' => 'api',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'pet',
                    'extraPatterns' => [
                        'GET' => 'cards',

                        'OPTIONS slider' => 'options',
                        'GET slider' => 'slider',

                        'GET <id>' => 'card-one',

                        'OPTIONS new' => 'create-order',
                        'POST new' => 'create-order',
                    ],
                ],
            ],
        ]
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
