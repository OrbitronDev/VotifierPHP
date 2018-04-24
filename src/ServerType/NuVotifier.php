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

namespace D3strukt0r\VotifierClient\ServerType;

use D3strukt0r\VotifierClient\Messages;
use D3strukt0r\VotifierClient\ServerConnection;
use D3strukt0r\VotifierClient\VoteType\VoteInterface;

/**
 * The Class to access a server which uses the plugin "NuVotifier".
 */
class NuVotifier extends ClassicVotifier
{
    /**
     * @var bool Use version 2 of the protocol
     */
    private $protocolV2;

    /**
     * @var null|string The token from the config.yml
     */
    private $token;

    /**
     * Creates the NuVotifier object.
     *
     * @param string      $host       (Required) The domain or ip to connect to Votifier
     * @param int|null    $port       (Required) The port which votifier uses on the server
     * @param string      $publicKey  (Required) The key which is generated by the plugin. Only needed if using v1!
     * @param bool        $protocolV2 (Optional) Use version 2 of the protocol (Recommended)
     * @param string|null $token      (Optional) To use version 2 protocol the token is needed from the config.yml
     */
    public function __construct($host, $port, $publicKey, $protocolV2 = false, $token = null)
    {
        parent::__construct($host, $port, $publicKey);

        $this->protocolV2 = $protocolV2;
        $this->token = $token;
    }

    /**
     * Checks whether the connection uses the version 2 protocol.
     *
     * @return bool
     */
    public function isProtocolV2()
    {
        return $this->protocolV2;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $header (Required) The header that the plugin usually sends
     *
     * @return bool
     */
    public function verifyConnection($header)
    {
        $header_parts = explode(' ', $header);
        if (false === $header || false === mb_strpos($header, 'VOTIFIER') || 3 !== count($header_parts)) {
            return false;
        }

        return true;
    }

    /**
     * Prepares the vote package to be sent as version 2 protocol package.
     *
     * @param VoteInterface $vote      (Required) The vote package with information
     * @param string        $challenge (Required) The challenge sent by the server
     *
     * @return string
     */
    public function preparePackageV2(VoteInterface $vote, $challenge)
    {
        $payloadJson = json_encode(array(
            'username' => $vote->getUsername(),
            'serviceName' => $vote->getServiceName(),
            'timestamp' => $vote->getTimestamp(),
            'address' => $vote->getAddress(),
            'challenge' => $challenge,
        ));
        $signature = base64_encode(hash_hmac('sha256', $payloadJson, $this->token, true));
        $messageJson = json_encode(array('signature' => $signature, 'payload' => $payloadJson));

        $payload = pack('nn', 0x733a, mb_strlen($messageJson)).$messageJson;

        return $payload;
    }

    /**
     * {@inheritdoc}
     *
     * @param ServerConnection $connection (Required) The connection type to the plugin
     * @param VoteInterface    $vote       (Required) The vote type package
     *
     * @throws \Exception
     */
    public function send(ServerConnection $connection, VoteInterface $vote)
    {
        if (!$this->isProtocolV2()) {
            parent::send($connection, $vote);

            return;
        }

        if (!$this->verifyConnection($header = $connection->receive(64))) {
            throw new \Exception(Messages::get(Messages::NOT_VOTIFIER));
        }
        $header_parts = explode(' ', $header);
        $challenge = mb_substr($header_parts[2], 0, -1);
        $payload = $this->preparePackageV2($vote, $challenge);

        if (false === $connection->send($payload)) {
            throw new \Exception(Messages::get(Messages::NOT_SENT_PACKAGE));
        }

        if (!$response = $connection->receive(256)) {
            throw new \Exception(Messages::get(Messages::NOT_RECEIVED_PACKAGE));
        }

        $result = json_decode($response);
        if ('ok' !== $result->status) {
            throw new \Exception(Messages::get(Messages::NOT_RECEIVED_PACKAGE, null, $result->cause, $result->error));
        }
    }
}
