<?php

/**
 * Votifier PHP Client
 *
 * @package   VotifierClient
 *
 * @author    Manuele Vaccari <manuele.vaccari@gmail.com>
 * @copyright Copyright (c) 2017-2018 Manuele Vaccari <manuele.vaccari@gmail.com>
 * @license   https://github.com/D3strukt0r/Votifier-PHP-Client/blob/master/LICENSE.md MIT License
 *
 * @link      https://github.com/D3strukt0r/Votifier-PHP-Client
 */

namespace D3strukt0r\VotifierClient;

use D3strukt0r\VotifierClient\ServerType\ServerTypeInterface;
use D3strukt0r\VotifierClient\VoteType\VoteInterface;

/**
 * This class is used for easy access to all classes and to send the votes.
 */
class Vote
{
    /**
     * @var \D3strukt0r\VotifierClient\VoteType\VoteInterface The vote package
     */
    private $vote;

    /**
     * @var \D3strukt0r\VotifierClient\ServerType\ServerTypeInterface The server type information package
     */
    private $server;

    /**
     * Created a Vote object.
     *
     * @param VoteInterface       $vote       (Required) The vote package
     * @param ServerTypeInterface $serverType (Required) The server type information package
     */
    public function __construct(VoteInterface $vote, ServerTypeInterface $serverType)
    {
        $this->vote = $vote;
        $this->server = $serverType;
    }

    /**
     * Sends the vote package to the server.
     *
     * @throws \Exception
     */
    public function send()
    {
        $con = new ServerConnection($this->server);

        $this->vote->setTimestamp();
        $this->server->send($con, $this->vote);
    }
}
