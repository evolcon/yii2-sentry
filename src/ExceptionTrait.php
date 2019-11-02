<?php

namespace evolcon\sentry;

/**
 * @author Sabryan Oleg <itcutlet@gmail.com>
 */
trait ExceptionTrait
{
    protected $tags = [];
    protected $extra = [];

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param string $tag
     * @param $value
     * @return $this
     */
    public function addTag(string $tag, $value)
    {
        $this->tags[$tag] = (string) $value;

        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function addExtra(string $name, $value)
    {
        $this->extra[$name] = $value;

        return $this;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function addTags(array $tags)
    {
        foreach ($tags as $key => $value) {
            $this->addTag($key, $value);
        }

        return $this;
    }

    /**
     * @param array $extras
     * @return $this
     */
    public function addExtras(array $extras)
    {
        foreach ($extras as $key => $value) {
            $this->addExtra($key, $value);
        }

        return $this;
    }
}