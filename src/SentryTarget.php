<?php

namespace evolcon\sentry;

use Sentry\Severity;
use Throwable;
use yii\base\BaseObject;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Target;
use yii\web\IdentityInterface;
use yii\web\User;

/**
 * Class SentryTarget
 *
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
class SentryTarget extends Target
{
    /**
     * @var string|SentryComponent
     */
    public $sentryComponent = 'sentry';
    /**
     * @var string|User
     */
    public $userComponent = 'user';
    /**
     * @var array User data which will be collected to scope
     */
    public $userData;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->sentryComponent = Instance::ensure($this->sentryComponent, SentryComponent::class);
        if(!empty($this->userData)) {
            $this->userComponent = Instance::ensure($this->userComponent, User::class);
        }
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            if (current($message) instanceof Throwable) {
                $this->captureException($message);
            } else {
                $this->captureMessage($message);
            }
        }
    }

    /**
     * Отправка сообщения об ошибке
     * @param array $message
     * @return void
     */
    protected function captureException($messageData)
    {
        $data = $this->prepareData($messageData);

        if (($exception = $messageData[0]) instanceof ExceptionInterface) {
            $data = ArrayHelper::merge($data, [
                'tags'  => $exception->getTags(),
                'extra' => $exception->getExtra(),
            ]);
        }

        $this->sentryComponent->captureException($exception, $data);
    }

    /**
     * Отправка информационных сообщений. Не Exception
     * @param array $message
     * @return void
     */
    protected function captureMessage($messageData)
    {
        $message = $messageData[0];
        $data = $this->prepareData($messageData);
        $payLoad = [
            'level' => new Severity(Logger::getLevelName($messageData[1])),
            'stacktrace' => $messageData[4],
        ];

        if (is_string($message)) {
            $payLoad['message'] = $message;
        } elseif (is_array($message)) {
            $payLoad['message'] = ArrayHelper::remove($message, 'message', 'no message');
            $data['tags'] = ArrayHelper::remove($message, 'tags', []);
            $data['extra'] = $message;
        } else {
            $payLoad['message'] = VarDumper::export($message);
        }

        $this->sentryComponent->captureMessage($payLoad, $data);
    }

    /**
     * @param array $messageData Target message
     * @see Please refer to [[Logger::messages]] for the details about the message structure.
     * @return array
     */
    protected function prepareData($messageData)
    {
        return [
            'user' => $this->getUserData(),
            'tags' => ['category' => $messageData[2]],
        ];
    }

    /**
     * @return array
     */
    protected function prepareUserData()
    {
        $userData = [];

        if ($this->userData && $userIdentity = $this->userComponent->identity) {
            /** @var IdentityInterface|BaseObject $userIdentity */
            foreach ($this->userData as $attribute) {
                if ($userIdentity->canGetProperty($attribute)) {
                    $userData[$attribute] = $userIdentity->$attribute;
                }
            }
        }

        return $userData;
    }

    /**
     * @inheritDoc
     */
    protected function getContextMessage()
    {
        return '';
    }
}