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
use Predis\Client as PredisClient;

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

    /**
     * Construct
     *
     * @param PredisClient $redis Predis client
     */
    public function __construct(PredisClient $redis)
    {
        $this->redis = $redis;
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
