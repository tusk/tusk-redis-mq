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
     * @param mixed|Message $body    Message body|Message
     * @param array         $options Publish options
     */
    public function publish($message)
    {
        if (! $message instanceof Message) {
            $message = new Message($message);
        }
        $this->connection->publish($this->channel, $message);
        if (isset($this->options['channelExpire'])) {
            $this->connection->setChannelExpire(
                $this->channel,
                $this->options['channelExpire']
            );
        }
    }
    
    /**
     * Get connection
     * 
     * @return Connection Connection
     */
    public function getConnection()
    {
       return $this->connection; 
    }
    
    /**
     * Get channel
     *
     * @return string Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
