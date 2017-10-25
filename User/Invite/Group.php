<?php

namespace waylaidwanderer\SteamCommunity\User\Invite;

class Group implements \JsonSerializable
{
    private $groupId;
    private $groupUrl;
    private $title;

    public function __construct($groupId, $groupUrl, $title)
    {
        $this->groupId = $groupId;
        $this->groupUrl = $groupUrl;
        $this->title = $title;
    }

    public function jsonSerialize()
    {
        return [
            'groupid' => (int) $this->groupId,
            'groupurl' => $this->groupUrl,
            'title' => $this->title
        ];
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return (int) $this->groupId;
    }

    /**
     * @return string
     */
    public function getGroupUrl()
    {
        return $this->groupUrl;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
