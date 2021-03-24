<?php

namespace evolcon\sentry;

/**
 * @author Sabryan Oleg <itcutlet@gmail.com>
 **/
interface ExceptionInterface
{
    public function getTags(): array;
    public function getExtra(): array;
    public function addTag(string $tag, $value): static;
    public function addExtra(string $name, $value): static;
    public function save(string $category = '', bool $error = true): void;
}