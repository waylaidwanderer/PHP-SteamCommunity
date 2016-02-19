<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-18
 * Time: 12:14 PM
 */

namespace waylaidwanderer\SteamCommunity\Group\History;


class HistoryItem
{
    private $type;
    private $date;
    private $userSteamId;
    private $targetSteamId;

    public function __construct($type, $date, $userSteamId, $targetSteamId = '0')
    {
        $this->type = $type;
        $this->date = $date;
        $this->userSteamId = $userSteamId;
        $this->targetSteamId = $targetSteamId;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getUserSteamId()
    {
        return $this->userSteamId;
    }

    /**
     * @return string
     */
    public function getTargetSteamId()
    {
        return $this->targetSteamId;
    }
}
