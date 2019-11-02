<?php

namespace evolcon\sentry;

use Sentry\ClientBuilder;
use Throwable;
use Sentry\State\{Hub, Scope, HubInterface};
use Yii;
use yii\base\{Component, InvalidConfigException};

/**
 * Use this class only for extending, for capturing exceptions you can use [[SentryComponent]]
 *
 * @property string  $dsn
 * @property HubInterface  $client
 * @property boolean $enabled
 * @property boolean $environment
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
    public $enabled = true;
    /**
     * Your private DSN url
     * @var string
     */
    public $dsn;
    /**
     * @var HubInterface|array|callable|object Client for sending messages.
     * @throws InvalidConfigException
     */
    public $client;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->enabled) {
            return;
        }
        if (empty($this->dsn)) {
            throw new InvalidConfigException('Private DSN must be set.');
        }

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
        if(!$this->client) {
            $this->client = new Hub();
            $this->client->bindClient(ClientBuilder::create(['dsn' => $this->dsn])->getClient());
        } elseif (is_array($this->client)) {
            if(empty($this->client['class'])) {
                throw new InvalidConfigException('If attribute "client" specified as array, the key "class" must be set');
            } else {
                $this->client = Yii::createObject($this->client);
            }
        } elseif (is_callable($this->client)) {
            $this->client = call_user_func($this->client);
        }

        if (!is_object($this->client)) {
            throw new InvalidConfigException(get_class($this) . '::' . 'client must be an object');
        }
    }

    /**
     * @param Throwable $exception
     * @param array $data
     *
     * @return void
     */
    public function captureException(Throwable $exception, $data = []): void
    {
        $this->captureEvent(['exception' => $exception], $data);
    }

    /**
     * @param array $payLoad
     * @param array $data
     *
     * @return void
     */
    public function captureMessage(array $payLoad, array $data = []): void
    {
        $this->captureEvent($payLoad, $data);
    }

    /**
     * @param array $payLoad Main information with settings for event
     * @param array $data Additional data that may come in handy. (tags, extra, user data and etc.)
     *
     * @return void
     */
    public function captureEvent($payLoad, $data = []): void
    {
        $this->addDataToScope($data);
        $this->beforeCapture();
        $this->client->captureEvent($payLoad);
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
        $this->client->configureScope(function (Scope $scope) use ($extra) {
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
        $this->client->configureScope(function (Scope $scope) use ($tags) {
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
        $this->client->configureScope(function (Scope $scope) use ($userData) {
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

        $this->client->configureScope(function (Scope $scope) {
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