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

use DateTime;

/**
 * Connection
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Connection
{
    const KEY_PREFIX_DEFAULT = 'trq:';

    private $redis;
    private $keyPrefix;
    private $messageMapper;

    /**
     * Construct
     *
     * @param Object $redis Redis client
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
        /**
         * @todo Options for customizing mapper
         */
        $this->messageMapper = new MessageMapper();
    }

    /**
     * Get message
     *
     * @param string  $channel Channel
     * @param integer $timeout Listen timeout
     *
     * @return Message Message | NULL Timeout reached
     */
    public function getMessage($channel, $timeout)
    {
        $data = $this->getRedis()->brpop(
            $this->formatKey($channel),
            $timeout
        );
        if (null !== $data) {
            $message = new Message();
            $this->messageMapper->load($message, $data[1]);
            return $message;
        }

        return null;
    }

    /**
     * Publish
     *
     * @param string  $channel Channel
     * @param Message $message Message
     */
    public function publish($channel, Message $message)
    {
        $this->getRedis()->lpush(
            $this->formatKey($channel),
            $this->messageMapper->save($message)
        );
    }

    /**
     * Set channel expiration
     *
     * @param string  $channel Channel
     * @param integer $expire  Channel expiration
     */
    public function setChannelExpire($channel, $expire)
    {
        $this->getRedis()->expire($this->formatKey($channel), $expire);
    }

    /**
     * Get Redis client
     *
     * @return PredisClient Predis client
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Format key string
     *
     * @param string $key Key string
     *
     * @return string Formatted key string
     */
    public function formatKey($key)
    {
        return sprintf('%s%s', $this->getKeyPrefix(), $key);
    }

    /**
     * Get RPC channel key
     *
     * @return string RPC channel key
     */
    public function getRpcChannelKey()
    {
        $date = new DateTime;
        return  sprintf(
           'rpc:%s:%d',
            $date->format('YmdHis'),
            $this->getRedis()->incr(
                $this->formatKey('rpc_count')
            )
        );
    }

    /**
     * Get key prefix
     *
     * @return string Key prefix
     */
    private function getKeyPrefix()
    {
        if (null === $this->keyPrefix) {
            $this->keyPrefix = self::KEY_PREFIX_DEFAULT;
        }

        return $this->keyPrefix;
    }
}
