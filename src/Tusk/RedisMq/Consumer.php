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

/**
 * Consumer
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Consumer
{
    private $channel;
    private $callback;
    private $connection;

    /**
     * Construct
     *
     * @param string               $channel    Channel
     * @param Closure|PHP callable $callback   Consumer callback
     * @param Connection           $connection Connection
     */
    public function __construct($channel, $callback, Connection $connection)
    {
        $this->channel    = $channel;
        $this->connection = $connection;
        if (! is_callable($callback)) {
            throw new \InvalidArgumentException(
                'No valid callback supplied'
            );
        }
        $this->callback = $callback;
    }

    /**
     * Listen
     *
     * @param integer $timeout Timeout in seconds. 0 is indefinite
     *
     * @return boolean True = Message consumed. False = Timeout reached
     */
    public function listen($timeout = 0)
    {
        $message = $this->connection->getRedis()->brpop(
            $this->connection->formatKey($this->channel),
            $timeout
        );
        if (null !== $message) {
            $this->consume($message[1]);
            return true;
        }

        return false;
    }

    /**
     * Consume
     *
     * @param string $message Serialized message
     */
    private function consume($message)
    {
        $message = $this->unserialize($message);
        if ($this->callback instanceof \Closure
            || is_object($this->callback)) {
            $response = $this->callback->__invoke($message['body'], $message['options']);
        } else {
            $response = call_user_func(
                $this->callback,
                $message['body'],
                $message['options']
            );
        }
        if (isset($message['options']['rpcChannel'])) {
            $options = array();
            if (isset($message['options']['rpcChannelExpire'])) {
                $options = array(
                    'channelExpire' => $message['options']['rpcChannelExpire']
                );
            }
            $producer = new Producer(
                $message['options']['rpcChannel'],
                $this->connection,
                $options
            );
            $producer->publish(
                $response,
                array('requestId' => $message['options']['requestId'])
            );
        }
    }

    /**
     * Unserialize
     *
     * @param string $message Serialized message
     *
     * @return array Unserialized message
     */
    private function unserialize($message)
    {
        return unserialize($message);
    }
}
