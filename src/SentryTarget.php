<?php

namespace evolcon\sentry;

use Sentry\Severity;
use Exception;
use yii\base\{BaseObject, InvalidConfigException};
use yii\di\Instance;
use yii\helpers\{ArrayHelper, VarDumper};
use yii\log\Target;
use yii\web\{IdentityInterface, User};

/**
 * Class SentryTarget
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
     * @throws InvalidConfigException
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
            if (current($message) instanceof Exception) {
                $this->captureException($message);
            } else {
                $this->captureMessage($message);
            }
        }
    }

    /**
     * Capturing exception by sentry component
     * @param array $messageData
     * @return void
     */
    protected function captureException(array $messageData): void
    {
        $exception = $messageData[0];

        $payLoad = $this->prepareData($messageData);
        $payLoad['exceptions'] = [$exception];

        if ($exception instanceof ExceptionInterface) {
            $data['tags'] = $exception->getTags();
            $data['extra'] = $exception->getExtra();
        }

        $this->sentryComponent->captureEvent($payLoad);
    }

    /**
     * Отправка информационных сообщений. Не Exception
     * @param array $messageData
     * @return void
     */
    protected function captureMessage(array $messageData): void
    {
        $payLoad = $this->prepareData($messageData);
        $payLoad['level'] = new Severity(Logger::getLevelName($messageData[1]));
        $payLoad['stacktrace'] = $messageData[4];

        $message = $messageData[0];
        if (is_string($message)) {
            $payLoad['message'] = $message;
        } elseif (is_array($message)) {
            $payLoad['message'] = ArrayHelper::remove($message, 'message', 'no message');
            $payLoad['tags'][] = ArrayHelper::remove($message, 'tags', []);
            $payLoad['extra'] = $message;
        } else {
            $payLoad['message'] = VarDumper::export($message);
        }

        $this->sentryComponent->captureEvent($payLoad);
    }

    /**
     * @param array $messageData Target message
     * @see Please refer to [[Logger::messages]] for the details about the message structure.
     *
     * @return array ['user' => "array", 'tags' => "array"]
     */
    protected function prepareData(array $messageData): array
    {
        return [
            'user' => $this->prepareUserData(),
            'tags' => ['category' => $messageData[2]],
        ];
    }

    /**
     * @return array
     */
    protected function prepareUserData(): array
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
    protected function getContextMessage(): string
    {
        return '';
    }
}