<?php

namespace evolcon\sentry;

use RuntimeException;
use Throwable;
use Yii;

/**
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
class SilentException extends RuntimeException implements ExceptionInterface
{
    use ExceptionTrait;

    /**
     * SilentException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = null, $code = 0, Throwable $previous = null)
    {
        if(!isset($message) && isset(Yii::$app->controller)) {
            $message = Yii::$app->controller->id . ":" . (Yii::$app->controller->action->id ?? 'undefined action');
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $category
     * @param bool $error
     */
    public function save(string $category = '', bool $error = true): void
    {
        $error ? Yii::error($this, $category) : Yii::warning($this, $category);
    }
}