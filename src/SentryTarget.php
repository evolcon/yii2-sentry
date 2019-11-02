<?php

namespace evolcon\sentry;

use Sentry\Severity;
use Yii;
use app\models\User;
use Throwable;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

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
    public $component = 'sentry';
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

        $this->component = Instance::ensure($this->component, SentryComponent::class);

        if (!$this->component->enabled) {
            $this->setEnabled(false);
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
     * @inheritDoc
     */
    protected function getContextMessage()
    {
        return '';
    }

    /**
     * Отправка сообщения об ошибке
     * @param array $message
     * @return void
     */
    protected function captureException($message)
    {
        list($context, $level, $category, $timestamp, $traces) = $message; // Принебрегая принцыпам YAGNI, оставим пока как было

        $data = [
            'user'      => $this->getUserData(),
            'level'     => $level,     // возможно надо будет удалить
            'timestamp' => $timestamp, // возможно надо будет удалить
            'tags' => [
                'category' => $category,
            ],
        ];

        if ($context instanceof ExceptionInterface) {

            if (!$context->ready()) {
                return;
            }

            $data = ArrayHelper::merge($data, [
                'tags'  => $context->getTags(),
                'extra' => $context->getExtra(),
            ]);
        }
        $this->component->captureException($context, $data);
    }
    /**
     * Отправка информационных сообщений. Не Exception
     * @param array $message
     * @return void
     */
    protected function captureMessage($message)
    {
        list($context, $level, $category, $timestamp, $traces) = $message; // Принебрегая принцыпам YAGNI, оставим пока как было
        $data = [
            'user'      => $this->getUserData(),
            'tags'      => ['category' => $category],
            'timestamp' => $timestamp,// возможно надо будет удалить
        ];
        $payLoad = [
            'level' => new Severity(self::getLevelName($level)),
        ];
        if (is_string($context)) {
            $payLoad['message'] = $context;
        } elseif (is_array($context)) {
            $payLoad['message'] = ArrayHelper::remove($context, 'msg') ?? ArrayHelper::remove($context, 'message', 'no message');
            if (isset($context['traces'])) {
                $traces[] = ArrayHelper::remove($context, 'traces');
            }

            $tags = ArrayHelper::remove($context, 'tags', []);
            $extra = ArrayHelper::remove($context, 'extra', []);
            $data = ArrayHelper::merge($data, [
                'traces' => $traces, // возможно надо будет удалить
                'tags'   => $tags,
                'extra'  => array_merge($extra, $context),
            ]);
        } else {
            $payLoad['message'] = VarDumper::export($context);
        }
        $this->component->captureMessage($payLoad, $data);
    }
    /**
     * @return array
     */
    protected function getUserData()
    {
        $userData = [];
        if ($this->userData && Yii::$app->has('user') && $user = Yii::$app->user->identity) {
            /** @var User $user */
            foreach ($this->userData as $attribute) {
                if ($user->canGetProperty($attribute)) {
                    $userData[$attribute] = $user->$attribute;
                }
            }
        }
        return $userData;
    }
}