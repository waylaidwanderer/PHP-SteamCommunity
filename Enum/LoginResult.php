<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-24
 * Time: 8:37 AM
 */

namespace waylaidwanderer\SteamCommunity\Enum;


abstract class LoginResult
{
    const LoginOkay = "LoginOkay";
    const GeneralFailure = "GeneralFailure";
    const BadRSA = "BadRSA";
    const BadCredentials = "BadCredentials";
    const NeedCaptcha = "NeedCaptcha";
    const Need2FA = "Need2FA";
    const NeedEmail = "NeedEmail";
}
