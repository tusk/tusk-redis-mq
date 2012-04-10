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
        $message = $this->connection->getMessage($this->channel, $timeout);
        if (null !== $message) {
            $this->consume($message);
            return true;
        }

        return false;
    }

    /**
     * Consume
     *
     * @param Message $message Message
     */
    private function consume(Message $message)
    {
        if ($this->callback instanceof \Closure
            || is_object($this->callback)) {
            $response = $this->callback->__invoke($message);
        } else {
            $response = call_user_func($this->callback, $message);
        }
        if ($message->hasRpcChannel()) {
            $this->publishRpcResponse($message, $response);
        }
    }

    /**
     * Publish RPC response
     *
     * @param Message $message  Message
     * @param mixed   $response RPC response
     */
    private function publishRpcResponse(Message $message, $response)
    {
        $options = array();
        if ($message->hasRpcChannelExpire()) {
            $options = array(
                'channelExpire' => $message->getRpcChannelExpire()
            );
        }
        $producer = new Producer(
            $message->getRpcChannel(),
            $this->connection,
            $options
        );
        $response = new Message($response);
        $response->setRequestId($message->getRequestId());
        $producer->publish($response);
    }
}
