<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-18
 * Time: 5:04 PM
 */

namespace waylaidwanderer\SteamCommunity\User;


class Group
{
    private $groupId64;
    private $isPrimary;
    // only the first 3 groups on a user's profile will have data for the below variables
    private $groupName;
    private $groupUrl;
    private $headline;
    private $summary;
    private $avatarIcon;
    private $avatarMedium;
    private $avatarFull;
    private $memberCount;
    private $membersInChat;
    private $membersInGame;
    private $membersOnline;

    public function __construct($xml)
    {
        $this->groupId64 = (string)$xml->groupID64;
        $this->groupName = (string)$xml->groupName;
        $this->groupUrl = (string)$xml->groupURL;
        $this->headline = (string)$xml->headline;
        $this->summary = (string)$xml->summary;
        $this->avatarIcon = (string)$xml->avatarIcon;
        $this->avatarMedium = (string)$xml->avatarMedium;
        $this->avatarFull = (string)$xml->avatarFull;
        $this->memberCount = (int)$xml->memberCount;
        $this->membersInChat = (int)$xml->membersInChat;
        $this->membersInGame = (int)$xml->membersInGame;
        $this->membersOnline = (int)$xml->membersOnline;
        $this->isPrimary = (bool)((int)$xml['isPrimary']);
    }

    /**
     * @return string
     */
    public function getGroupId64()
    {
        return $this->groupId64;
    }

    /**
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->isPrimary;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
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
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function getAvatarIcon()
    {
        return $this->avatarIcon;
    }

    /**
     * @return string
     */
    public function getAvatarMedium()
    {
        return $this->avatarMedium;
    }

    /**
     * @return string
     */
    public function getAvatarFull()
    {
        return $this->avatarFull;
    }

    /**
     * @return int
     */
    public function getMemberCount()
    {
        return $this->memberCount;
    }

    /**
     * @return int
     */
    public function getMembersInChat()
    {
        return $this->membersInChat;
    }

    /**
     * @return int
     */
    public function getMembersInGame()
    {
        return $this->membersInGame;
    }

    /**
     * @return int
     */
    public function getMembersOnline()
    {
        return $this->membersOnline;
    }
}
