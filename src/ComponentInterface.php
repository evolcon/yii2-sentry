<?php

namespace evolcon\sentry;

use Throwable;

/**
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
interface ComponentInterface
{
    /**
     * @param Throwable $exception
     * @param array $payload
     *
     * @return void
     */
    public function captureException(Throwable $exception, array $payload = []): void;

    /**
     * @param string $message
     * @param array $payload
     *
     * @return void
     */
    public function captureMessage(string $message, array $payload = []): void;
}