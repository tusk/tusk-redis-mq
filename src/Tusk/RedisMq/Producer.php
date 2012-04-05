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

use Predis\Client as PredisClient;

/**
 * Producer
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Producer
{
    private $channel;
    private $connection;
    private $options = array();

    /**
     * Construct
     *
     * @param string     $channel    Channel
     * @param Connection $connection Connection
     * @param array      $options    Options
     */
    public function __construct(
                   $channel,
        Connection $connection,
        array      $options = array()
    ) {
        $this->channel    = $channel;
        $this->connection = $connection;
        $this->options    = $options;
    }

    /**
     * Publish message
     *
     * @param mixed $body    Message body
     * @param array $options Publish options
     */
    public function publish($body, $options = array())
    {
        $message = array('options' => $options, 'body' => $body);
        $this->connection->getRedis()->lpush(
            $this->connection->formatKey($this->channel),
            $this->serialize($message)
        );
        if (isset($this->options['channelExpire'])) {
            $this->connection->getRedis()->expire(
                $this->connection->formatKey($this->channel),
                $this->options['channelExpire']
            );
        }
    }

    /**
     * Serialize message
     *
     * @param array $message Message
     *
     * @return string Serialized message
     */
    public function serialize($message)
    {
        return serialize($message);
    }
}
