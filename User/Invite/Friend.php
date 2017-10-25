<?php

namespace waylaidwanderer\SteamCommunity\User\Invite;

class Friend implements \JsonSerializable
{
    private $steamId;
    private $profileUrl;
    private $name;

    public function __construct($steamId, $profileUrl, $name)
    {
        $this->steamId = $steamId;
        $this->profileUrl = $profileUrl;
        $this->name = $name;
    }

    public function jsonSerialize()
    {
        return [
            'steamid' => (int) $this->steamId,
            'profileurl' => $this->profileUrl,
            'name' => $this->name
        ];
    }

    /**
     * @return int
     */
    public function getSteamId()
    {
        return (int) $this->steamId;
    }

    /**
     * @return string
     */
    public function getProfileUrl()
    {
        return $this->profileUrl;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}