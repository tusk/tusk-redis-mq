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
 * RpcFactory
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class RpcFactory
{
    private $connection;
    private $options;

    /**
     * Construct
     *
     * @param Connection $connection Connection
     * @param array      $options    Options
     */
    public function __construct($connection, array $options = array())
    {
        $this->connection = $connection;
        $this->options    = $options;
    }

    /**
     * Get
     *
     * @return Rpc Rpc handler
     */
    public function get()
    {
        return new Rpc($this->connection, $this->options);
    }
}
