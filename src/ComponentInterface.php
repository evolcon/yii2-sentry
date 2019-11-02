<?php

namespace evolcon\sentry;

use Throwable;
use function Sentry\init as initSentryClient;
use Sentry\SentrySdk;
use Sentry\State\{Scope, HubInterface};
use Yii;
use yii\base\{Component, InvalidConfigException};

/**
 * Class SentryComponent
 *
 * @property string  $dsn
 * @property HubInterface  $client
 * @property boolean $enabled
 * @property boolean $environment
 *
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
interface ComponentInterface
{
    /**
     * @param Throwable $exception
     * @param array $data
     *
     * @return void
     */
    public function captureException(Throwable $exception, $data = []): void;

    /**
     * @param array $payLoad
     * @param array $data
     *
     * @return void
     */
    public function captureMessage(array $payLoad, array $data = []): void;

    /**
     * @param array $payLoad Main information with settings for event
     * @param array $data Additional data that may come in handy. (tags, extra, user data and etc.)
     *
     * @return void
     */
    public function captureEvent($payLoad, $data = []): void;
}