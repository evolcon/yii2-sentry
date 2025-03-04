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

        if ($exception instanceof ExceptionInterface) {
            $payLoad['tags'] = $exception->getTags();
            $payLoad['extra'] = $exception->getExtra();
        }
        $this->sentryComponent->captureException($exception, $payLoad);
    }

    /**
     * Отправка информационных сообщений. Не Exception
     * @param array $messageData
     * @return void
     */
    protected function captureMessage(array $messageData): void
    {
        $message = $messageData[0];

        $data = $this->prepareData($messageData);
//        $payLoad['level'] = new Severity(Logger::getLevelName($messageData[1]));
        $data['stacktrace'] = $messageData[4];


        if (is_array($message)) {
            $message = ArrayHelper::remove($message, 'message', 'no message');

            if(!empty($message['tags'])) {
                $data['tags'][] = ArrayHelper::remove($message, 'tags', []);
            }
            if($message) {
                $data['extra'] = $message;
            }
        } elseif(!is_string($message)) {
            $message = VarDumper::export($message);
        }

        $this->sentryComponent->captureMessage($message, $data);
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