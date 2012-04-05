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
 * Rpc
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Rpc
{
    const LISTEN_TIMEOUT_DEFAULT = 5;
    const CHANNEL_EXPIRE_DEFAULT = 60;

    private $channel;
    private $requests = array();
    private $options;
    private $responseStartTime;
    private $errors = array();
    private $listenTimeout;
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
        $this->requests[$requestId] = $channel;
        $producer = new Producer($channel, $this->connection);
        $options = array(
            'rpcChannel'       => $this->channel,
            'rpcChannelExpire' => $this->getChannelExpire(),
            'requestId'        => $requestId
        );
        $producer->publish($body, $options);
    }

    /**
     * Get responses
     *
     * @return array Responses
     */
    public function getResponses()
    {
        $responses = array_fill_keys(array_keys($this->requests), null);
        $consumer = new Consumer(
            $this->channel,
            function ($body, $options) use (&$responses) {
                $responses[$options['requestId']] = $body;
            },
            $this->connection
        );
        $this->responseStartTime = time();
        $responseCount = 0;
        while ($this->checkStatus()) {
            if ($consumer->listen($this->getListenTimeout())) {
                $responseCount++;
            }
            if ($responseCount >= count($this->requests)) {
                break;
            }
        }

        return $responses;
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
     * Check status
     *
     * @return boolean True = status ok. False = abort retrieving responses
     */
    private function checkStatus()
    {
        $responseDuration = time() - $this->responseStartTime;
        if (isset($this->options['responseTimeout'])
            && $responseDuration >= $this->options['responseTimeout']
        ) {
            $this->errors[] = array(
                'response_timeout' => sprintf(
                    'Response timeout (%ds) reached',
                    $this->options['responseTimeout']
                )
            );
            return false;
        }

        return true;
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
     * Get RPC channel expire time
     */
    private function getChannelExpire()
    {
        if (null === $this->channelExpire) {
            $this->channelExpire = self::CHANNEL_EXPIRE_DEFAULT;
        }

        return $this->channelExpire;
    }
}
