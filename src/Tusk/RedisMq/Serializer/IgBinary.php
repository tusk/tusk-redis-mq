<?php

namespace Tusk\RedisMq\Serializer;

class IgBinary implements SerializerInterface
{
    public function serialize($value)
    {
        return \igbinary_serialize($value);
    }

    public function unserialize($value)
    {
        return \igbinary_unserialize($value);
    }

    public function isSupported()
    {
        return function_exists('igbinary_serialize');
    }
}
