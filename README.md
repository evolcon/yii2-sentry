# [Sentry](https://sentry.io) logger extension for Yii2
-------------
Installation
---------
```
composer require luckyshopteam/yii2-sentry @dev
```

Add SentryComponent to the the application config:
```
'components' => [
    'sentry' => [
        'class' => evolcon\sentry\SentryComponent::class,
        'dsn' => 'https://fsdhbk67bhkfa424eehb678agj66b7@sentry.io/2588150',
    ],
],
```

For disabling notification set `false` to attribute `enabled`
```
'components' => [
    'sentry' => [
        'class' => evolcon\sentry\SentryComponent::class,
        'dsn' => 'https://fsdhbk67bhkfa424eehb678agj66b7@sentry.io/2588150',
        'enabled' => false,
    ],
],
```

Add class SentryTarget attribute 'targets' for component 'log'
```
'components' => [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'flushInterval' => 1,
        'targets' => [
            [
                'class' => evolcon\sentry\SentryTarget::class,
                'exportInterval' => 1,
                'levels' => ['error', 'warning'],
                'except' => [
                    'yii\web\HttpException:429', // TooManyRequestsHttpException
                    'yii\web\HttpException:401', // UnauthorizedHttpException
                ],
                'userData' => ['id', 'email', 'role'],
            ],
        ]
    ],
],
```

#User data collecting
__________________
Add ``userData`` attribute to SentryTarget and array list of attributes which are needed to be collected 

```
'components' => [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'flushInterval' => 1,
        'targets' => [
            [
                'class' => evolcon\sentry\SentryTarget::class,
                'exportInterval' => 1,
                'levels' => ['error', 'warning'],
                'except' => [
                    'yii\web\HttpException:429', // TooManyRequestsHttpException
                    'yii\web\HttpException:401', // UnauthorizedHttpException
                ],
                'userData' => ['id', 'email', 'role'],
            ],
        ]
    ],
],
```

By default sentry target uses 'user' component of the application. If you need to override component, configure attribute `userComponent`

`NOTE: component must be an instance of \yii\web\User class`

```
'components' => [
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'flushInterval' => 1,
        'targets' => [
            [
                'class' => evolcon\sentry\SentryTarget::class,
                'userComponent' => 'user', //the component name
                'userData' => ['id', 'email', 'role'],
            ],
        ]
    ],
],
```

Sometimes we need to separate logs for example to separate `warning` and `error` and send them to different projects in sentry.
For that we need to override SentryTarget's attribute `sentryComponent` , and set the component name which we needed


```
'components' => [
    'sentryWarnings' => [
        'class' => evolcon\sentry\SentryComponent::class,
        'dsn' => 'https://fsdhbk67bhkfa424eehb678agj66b7@sentry.io/55555555',
    ],
    'sentryErrors' => [
        'class' => evolcon\sentry\SentryComponent::class,
        'dsn' => 'https://fsdhbk67bhkfa424eehb678agj66b7@sentry.io/999999999',
    ],
    'log' => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'flushInterval' => 1,
        'targets' => [
            [
                'class' => evolcon\sentry\SentryTarget::class,
                'sentryComponent' => 'sentryWarnings',
                'levels' => ['warning'], //only warnings
            ],
            [
                'class' => evolcon\sentry\SentryTarget::class,
                'sentryComponent' => 'sentryErrors',
                'levels' => ['error'], //only errors
            ],
        ]
    ],
],
```
