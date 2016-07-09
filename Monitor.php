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
 * Monitor
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Monitor
{
    private $connection;

    /**
     * Construct
     *
     * @param Connection $connection Connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get channels
     *
     * @param boolean $includeRpc Include RPC channels
     *
     * @return array Channels
     */
    public function getChannels($includeRpc = false)
    {
        $channels = $this->connection->getRedis()->keys(
            sprintf(
                '%s*',
                $this->connection->formatKey('')
            )
        );
        $list = array();
        foreach ($channels as $channel) {
            $channel = substr(
                $channel,
                strlen($this->connection->formatKey(''))
            );
            if (substr($channel, 0, 4) == 'rpc:' && false == $includeRpc) {
                continue;
            }
            if (substr($channel, 0, 4) == 'rpc_') {
                continue;
            }
            $list[] = $channel;
        }

        return $list;
    }

    /**
     * Get channel queue length
     */
    public function getChannelQueueLength($channel)
    {
        $length = $this->connection->getRedis()->llen(
            $this->connection->formatKey($channel)
        );

        return $length;
    }

}
