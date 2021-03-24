<?php

namespace evolcon\sentry;

use Sentry\ClientBuilder;
use Sentry\Event;
use Throwable;
use Sentry\State\{Hub, Scope, HubInterface};
use Yii;
use yii\base\{Component, InvalidConfigException};

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
     * @var HubInterface|array|callable|object Client for sending messages.
     * @throws InvalidConfigException
     */
    public mixed $client;

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
        if(!$this->client) {
            $this->_client = new Hub();
            $this->_client->bindClient(ClientBuilder::create(['dsn' => $this->dsn])->getClient());
        } elseif (is_array($this->client)) {
            if(empty($this->client['class'])) {
                throw new InvalidConfigException('If attribute "client" specified as array, the key "class" must be set');
            } else {
                $this->_client = Yii::createObject($this->client);
            }
        } elseif (is_callable($this->client)) {
            $this->_client = call_user_func($this->client);
        } else {
            $this->_client = $this->client;
        }

        $this->client = null;
    }

    /**
     * @param Throwable $exception
     * @param array $data
     *
     * @return void
     */
    public function captureException(Throwable $exception, $data = []): void
    {
        $this->captureEvent(['exceptions' => [$exception]], $data);
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
    public function captureEvent(array $payLoad, $data = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->addDataToScope($data);
        $this->beforeCapture();
        $event = Event::createEvent();

        foreach ($payLoad as $attr => $value) {
            $setter = 'set' . ucfirst($attr);
            $event->$setter($value);
        }

        $this->client->captureEvent($event);
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