<?php

namespace evolcon\sentry;

use Sentry\EventHint;
use Sentry\SentrySdk;
use Sentry\Serializer\RepresentationSerializer;
use Sentry\Stacktrace;
use Sentry\StacktraceBuilder;
use Throwable;
use Sentry\State\{Scope, HubInterface};
use Yii;
use yii\base\{Component, InvalidConfigException};
use function Sentry\init as initSentry;

/**
 * Use this class only for extending, for capturing exceptions you can use [[SentryComponent]]
 *
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
abstract class BaseSentryComponent extends Component implements ComponentInterface
{
    const EVENT_BEFORE_CAPTURE = 'beforeCapture';
    const EVENT_AFTER_CAPTURE = 'afterCapture';

    /**
     * Set to `false` to skip collecting errors
     * @var bool
     */
    public bool $enabled = true;
    /**
     * Your private DSN url
     * @var string
     */
    public string $dsn;
    /**
     * @var HubInterface|array|null Client for sending messages.
     * @throws InvalidConfigException
     */
    public HubInterface|array|null $client = null;

    /**
     * @var HubInterface
     */
    protected HubInterface $_client;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->initClient();
    }

    /**
     * Client initialization
     *
     * @return void
     * @throws InvalidConfigException
     */
    protected function initClient()
    {
        if(!isset($this->client)) {
            initSentry(['dsn' => $this->dsn]);
            $this->_client = SentrySdk::getCurrentHub();
        } elseif (is_array($this->client)) {
            $this->_client = Yii::createObject($this->client);
        } elseif (!$this->client instanceof HubInterface) {
            throw new InvalidConfigException('Invalid client setting. Expected: null, array, HubInterface.');
        }

        $this->client = null;
    }

    /**
     * @inheritDoc
     */
    public function captureException(Throwable $exception, $payload = []): void
    {
        $this->capture($exception, $payload);
    }

    /**
     * @inheritDoc
     */
    public function captureMessage(string $message, array $payload = []): void
    {
        $this->capture($message, $payload);
    }

    /**
     * @param Throwable|string $event Main information to be logged
     * @param array $payload Additional data that may come in handy. (tags, extra, user data and etc.)
     *
     * @return void
     */
    protected function capture(Throwable|string $event, array $payload = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->addDataToScope($payload);
        $this->beforeCapture();

        if(is_string($event)) {
            $hint = new EventHint();

            if(isset($payload['stacktrace'])) {
                $stackBuilder = new StacktraceBuilder($this->_client->getClient()->getOptions(), new RepresentationSerializer($this->_client->getClient()->getOptions()));
                $hint->stacktrace = $stackBuilder->buildFromBacktrace($payload['stacktrace'], '', 0);
            }
            $this->_client->captureMessage($event, null, $hint);
        } else {
            $this->_client->captureException($event);
        }

        $this->afterCapture();
    }

    /**
     * @param array $data Array list of data for current scope.
     *
     * @return void
     */
    protected function addDataToScope(array $data): void
    {
        if (!empty($data['extra'])) {
            $this->setExtra($data['extra']);
        }
        if (!empty($data['tags'])) {
            $this->setTags($data['tags']);
        }
        if (!empty($data['user'])) {
            $this->setUser($data['user']);
        }
    }

    /**
     * Adds extra data to scope
     * @param array $extra
     *
     * @return void
     */
    protected function setExtra(array $extra): void
    {
        $this->_client->configureScope(function (Scope $scope) use ($extra) {
            $scope->setExtras($extra);
        });
    }

    /**
     * Adds tags list to scope
     * @param array $tags
     *
     * @return void
     */
    protected function setTags(array $tags): void
    {
        $this->_client->configureScope(function (Scope $scope) use ($tags) {
            $scope->setTags($tags);
        });
    }

    /**
     * Adds user data to scope
     * @param array $userData
     *
     * @return void
     */
    protected function setUser(array $userData): void
    {
        $this->_client->configureScope(function (Scope $scope) use ($userData) {
            $scope->setUser($userData);
        });
    }

    /**
     * ```
     * //my code
     *
     * parent::afterCapture()
     * ```
     *
     * @return void
     */
    protected function afterCapture(): void
    {
        $this->trigger(self::EVENT_AFTER_CAPTURE);

        $this->_client->configureScope(function (Scope $scope) {
            $scope->clear();
        });
    }

    /**
     * @return void
     */
    protected function beforeCapture(): void
    {
        $this->trigger(self::EVENT_BEFORE_CAPTURE);
    }
}