<?php

namespace Tusk\RedisMq\Serializer;

class Php implements SerializerInterface
{
    public function serialize($value)
    {
        return \serialize($value);
    }

    public function unserialize($value)
    {
        return \unserialize($value);
    }

    public function isSupported()
    {
        return true;
    }
}
