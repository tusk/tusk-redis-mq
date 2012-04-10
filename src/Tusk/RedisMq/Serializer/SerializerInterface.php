<?php

namespace Tusk\RedisMq\Serializer;

interface SerializerInterface
{
    public function serialize($value);
    public function unserialize($value);
    public function isSupported();
}
