<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-24
 * Time: 8:37 AM
 */

namespace waylaidwanderer\SteamCommunity\Enum;


abstract class CreateAccountResult
{
    const CreatedOkay = "CreatedOkay";
    const GeneralFailure = "GeneralFailure";
    const NeedCaptcha = "NeedCaptcha";
}
