<?php

/*
 * This file is part of the Tusk RedisMq package.
 *
 * (c) 2012 Tusk PHP Components <frizzy@paperjaw.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tusk\RedisMq;

use Tusk\RedisMq\Serializer\SerializerInterface;

/**
 * Message mapper
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class MessageMapper
{
    private $serializerDefault = 'php';
    private $serializers = array();

    /**
     * Load
     *
     * @param Message $message Message
     * @param mixed   $data    Message data
     */
    public function load(Message $message, $data)
    {
        $data = json_decode($data, true);
        if (! $this->getSerializer($data['serializer'])->isSupported()) {
            throw new \UnexpectedValueException(
                sprintf('Serializer "%s" is not supported', $data['serializer'])
            );
        }
        if (isset($data['rpc_channel'])) {
            $message->setRpcChannel($data['rpc_channel']);
        }
        if (isset($data['rpc_channel_expire'])) {
            $message->setRpcChannelExpire($data['rpc_channel_expire']);
        }
        if (isset($data['request_id'])) {
            $message->setRequestId($data['request_id']);
        }

        $message->body = $this->getSerializer(
            $data['serializer']
        )->unserialize($data['body']);
    }

    /**
     * Save
     *
     * @param Message $message Message
     *
     * @return string Message string
     */
    public function save(Message $message)
    {
        $data = array(
            'serializer' => $this->serializerDefault,
            'body'       => $this->getSerializer()->serialize($message->body)
        );
        if ($message->hasRpcChannel()) {
            $data['rpc_channel'] = $message->getRpcChannel();
        }
        if ($message->hasRpcChannelExpire()) {
            $data['rpc_channel_expire'] = $message->getRpcChannelExpire();
        }
        if (null !== $requestId = $message->getRequestId()) {
            $data['request_id'] = $requestId;
        }

        return json_encode($data);
    }

    /**
     * Set default serializer type
     *
     * @param sting $serializer Serializer type
     */
    public function setDefaultSerializer($serializer)
    {
        $this->serializerDefault = $serializer;
    }

    /**
     * Get serializer
     *
     * @param string $serializer Serializer type
     *
     * @return Serializer Serializer
     */
    public function getSerializer($serializer = null)
    {
        if (null === $serializer) {
            $serializer = $this->serializerDefault;
        }
        if (! isset($this->serializers[$serializer])) {
            $class = sprintf(
                '\Tusk\RedisMq\Serializer\%s',
                ucfirst(self::camelize($serializer))
            );
            if (! class_exists($class)) {
                throw new \InvalidArgumentException(
                    sprintf('Serializer "%s" not found', $serializer)
                );
            }
            $this->serializers[$serializer] = new $class;
        }

        return $this->serializers[$serializer];
    }

    /**
     * Filter underscore from camel case string
     *
     * @param string $value
     *
     * @return string Filtered value
     */
    private static function underscore($value)
    {
        return strtolower(
            preg_replace(
                array(
                    '/([A-Z]+)([A-Z][a-z])/',
                    '/([a-z\d])([A-Z])/'
                ),
                array('\\1_\\2', '\\1_\\2'),
                strtr($value, '_', '.')
            )
        );
    }

    /**
     * Filter camelize underscored string
     *
     * @param string $value
     *
     * @return string Filtered value
     */
    private static function camelize($value)
    {
        return preg_replace(
            array('/(?:^|_)+(.)/e', '/\.(.)/e'),
            array("strtoupper('\\1')", "'_'.strtoupper('\\1')"),
            $value
        );
    }
}
