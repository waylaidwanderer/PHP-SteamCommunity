<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 3:00 PM
 */

namespace waylaidwanderer\SteamCommunity\MobileAuth\Confirmations;


class Confirmation
{
    private $confirmationId;
    private $confirmationKey;
    private $confirmationOfferId;
    private $confirmationDescription;

    public function __construct($confirmationId, $confirmationKey, $confirmationOfferId, $confirmationDescription)
    {
        $this->confirmationId = $confirmationId;
        $this->confirmationKey = $confirmationKey;
        $this->confirmationOfferId = $confirmationOfferId;
        $this->confirmationDescription = $confirmationDescription;
    }

    /**
     * @return string
     */
    public function getConfirmationId()
    {
        return $this->confirmationId;
    }

    /**
     * @return string
     */
    public function getConfirmationKey()
    {
        return $this->confirmationKey;
    }

    /**
     * @return string
     */
    public function getConfirmationDescription()
    {
        return $this->confirmationDescription;
    }

    /**
     * @return string
     */
    public function getConfirmationOfferId()
    {
        return $this->confirmationOfferId;
    }
}
