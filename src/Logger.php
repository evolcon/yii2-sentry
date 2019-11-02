<?php

namespace evolcon\sentry;

/**
 * @inheritDoc
 *
 * @author Sabryan Oleg <itcutlet@gmail.com>
 **/
class Logger extends \yii\log\Logger
{
    /**
     * @var array Error level map
     */
    const LEVEL_MAP = [
        self::LEVEL_ERROR => 'error',
        self::LEVEL_WARNING => 'warning',
        self::LEVEL_INFO => 'info',
        self::LEVEL_TRACE => 'debug',
        self::LEVEL_PROFILE_BEGIN => 'debug',
        self::LEVEL_PROFILE_END => 'debug',
    ];

    /**
     * @inheritDoc
     */
    public static function getLevelName($level)
    {
        return self::LEVEL_MAP[$level] ?? 'error';
    }
}