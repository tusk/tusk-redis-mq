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
use RuntimeException;

/**
 * Rpc
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Rpc
{
    const LISTEN_TIMEOUT_DEFAULT = 5;
    const CHANNEL_EXPIRE_DEFAULT = 60;

    private $channel;
    private $requests      = array();
    private $requestBodies = array();
    private $responses     = array();
    private $options;
    private $errors = array();
    private $listenTimeout;
    private $timeout;
    private $channelExpire;

    /**
     * Construct
     *
     * @param Connection $connection Connection
     * @param array      $options    Options
     */
    public function __construct($connection, array $options = array())
    {
        $this->connection = $connection;
        $this->channel = $this->connection->getRpcChannelKey();
        $this->options = $options;
        if (isset($options['listenTimeout'])) {
            $this->listenTimeout = $options['listenTimeout'];
        }
        if (isset($options['channelExpire'])) {
            $this->channelExpire = $options['channelExpire'];
        }
    }

    /**
     * Add request
     *
     * @param string $channel   Channel
     * @param mixed  $body      Message body
     * @param scalar $requestId Request ID
     */
    public function addRequest($channel, $body, $requestId = null)
    {
        if (null === $requestId) {
            $requestId = count($this->requests);
        } elseif (isset($this->requests[$requestId])) {
            throw new \InvalidArgumentException(
                sprintf('Duplicate request id "%s"', $requestId)
            );
        }
        $this->requests[$requestId]     = $channel;
        $this->requestBodies[$requestId] = $body;
        $this->publish($requestId);
    }

    /**
     * Get responses
     *
     * @param integer $try Try count
     *
     * @return array Responses
     */
    public function getResponses(&$try = 0)
    {
        $this->setupTimeout();
        $consumer = new Consumer(
            $this->channel,
            function ($message) {
                $this->responses[$message->getRequestId()] = $message->body;
            },
            $this->connection
        );
        while ($this->checkStatus()) {
            $consumer->listen($this->getListenTimeout());
            if (count($this->responses) >= count($this->requests)) {
                break;
            }
        }
        unset($consumer);
        if (count($this->responses) < count($this->requests)) {
            $requestIds = array_diff(
                array_keys($this->requests),
                array_keys($this->responses)
            );
            if (++$try > 3) {
                throw new RuntimeException(
                    sprintf(
                        '%d out of %d RPC reponses not received',
                        count($requestIds),
                        count($this->requests)
                    )
                );
            }
            foreach ($requestIds as $requestId) {
                $this->publish($requestId);
            }
            $this->getResponses($try);
        }
        return $this->responses;
    }

    /**
     * Get errors
     *
     * @return array Errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Publish
     *
     * @param scalar $requestId Request id
     */
    private function publish($requestId)
    {
        $message = new Message();
        $message->body = $this->requestBodies[$requestId];
        $message->setRpcChannel($this->channel);
        $message->setRpcChannelExpire($this->getChannelExpire());
        $message->setRequestId($requestId);
        $producer = new Producer($this->requests[$requestId], $this->connection);
        $producer->publish($message);
    }

    /**
     * Check status
     *
     * @return boolean True = status ok. False = abort retrieving responses
     */
    private function checkStatus()
    {
        if ($this->timeout instanceof DateTime) {
            $now = new DateTime;
            if ($now > $this->timeout) {
                $this->errors[] = array(
                    'response_timeout' => sprintf(
                        'Response timeout (%ds) reached',
                        $this->options['responseTimeout']
                    )
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Setup response timeout
     */
    private function setupTimeout()
    {
        if (isset($this->options['responseTimeout'])) {
            $this->timeout = new DateTime(
                sprintf(
                    'now +%d seconds',
                    $this->options['responseTimeout']
                )
            );
        }
    }

    /**
     * Get listen timeout
     *
     * @return integer Listen timeout in seconds
     */
    private function getListenTimeout()
    {
        if (null === $this->listenTimeout) {
            $this->listenTimeout = self::LISTEN_TIMEOUT_DEFAULT;
        }
        return $this->listenTimeout;
    }

    /**
     * Get RPC channel expiration
     *
     * @return integer Channel expiration
     */
    private function getChannelExpire()
    {
        if (null === $this->channelExpire) {
            $this->channelExpire = self::CHANNEL_EXPIRE_DEFAULT;
        }
        return $this->channelExpire;
    }
}
