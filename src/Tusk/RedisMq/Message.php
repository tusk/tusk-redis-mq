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
 * Message
 *
 * @author Bernard van Niekerk <frizzy@paperjaw.com>
 */
class Message
{
    public $body;
    private $rpcChannel;
    private $rpcChannelExpire;
    private $requestId;

    /**
     * Construct
     *
     * @param mixed $body Message body
     */
    public function __construct($body = null)
    {
        if (null !== $body) {
            $this->body = $body;
        }
    }

    /**
     * Set RPC channel
     *
     * @param string $channel RPC channel
     */
    public function setRpcChannel($channel)
    {
        $this->rpcChannel = $channel;
    }

    /**
     * Get RPC channel
     *
     * @return string RPC channel
     */
    public function getRpcChannel()
    {
        return $this->rpcChannel;
    }

    /**
     * Has RPC channel
     *
     * @return boolean Has RPC channel
     */
    public function hasRpcChannel()
    {
        return $this->rpcChannel !== null;
    }

    /**
     * Set RPC channel expiration
     *
     * @param integer $expire RPC channel expiration (seconds)
     */
    public function setRpcChannelExpire($expire)
    {
        $this->rpcChannelExpire = $expire;
    }

    /**
     * Get RPC channel expiration
     *
     * @return integer RPC channel expiration (seconds)
     */
    public function getRpcChannelExpire()
    {
        return $this->rpcChannelExpire;
    }

    /**
     * Has RPC channel expiration
     *
     * @return boolean Has RPC channel expiration
     */
    public function hasRpcChannelExpire()
    {
        return $this->rpcChannelExpire !== null;
    }

    /**
     * Get request id
     *
     * @return scalar Request id
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set request id
     *
     * @param scalar $requestId Request id
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }
}
