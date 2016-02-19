<?php
/**
 * This is an example of how you can use PHP-SteamCommunity.
 * This file is meant to be run via php-cli (the command line) but can be adapted to run on a webserver as well.
 */
require '../vendor/autoload.php';
defined('STDIN') or define('STDIN', fopen("php://stdin","r"));

use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\MobileAuth\WgTokenInvalidException;
use waylaidwanderer\SteamCommunity\SteamCommunity;

date_default_timezone_set('America/Los_Angeles');

$settings = json_decode(file_get_contents('settings.json'), true);

$steam = new SteamCommunity($settings, dirname(__FILE__));
$loginResult = $steam->doLogin();
while ($loginResult != LoginResult::LoginOkay) {
    if ($loginResult == LoginResult::Need2FA) {
        if ($steam->mobileAuth() == null) {
            $authCode = ask('Enter 2FA code: ');
            $steam->setTwoFactorCode($authCode);
        } else {
            $authCode = $steam->mobileAuth()->steamGuard()->generateSteamGuardCode();
            $steam->setTwoFactorCode($authCode);
            writeLine('Generated Steam Guard code: ' . $authCode);
        }
        $loginResult = $steam->doLogin();
    } else if ($loginResult == LoginResult::NeedEmail) {
        $authCode = ask('Enter Steam Guard code from email: ');
        $steam->setEmailCode($authCode);
        $loginResult = $steam->doLogin();
    } else if ($loginResult == LoginResult::NeedCaptcha) {
        $captchaCode = ask('Enter captcha (' . $steam->getCaptchaLink() . '): ');
        $steam->setCaptchaText($captchaCode);
        $loginResult = $steam->doLogin();
    } else {
        break;
    }
    writeLine("Login result: {$loginResult}.");
}

if ($loginResult == LoginResult::LoginOkay) {
    writeLine('Logged in successfully.');

    $tradeOffers = $steam->tradeOffers();
    var_dump($tradeOffers->getTradeOffersViaAPI(true));

    $trade = $tradeOffers->createTrade(12345);
    $trade->addOtherItem(730, 2, "12345678");
    var_dump($trade->send());
    var_dump($trade->sendWithToken('token'));

    var_dump($steam->market()->getWalletBalance());

    try {
        $confirmations = $steam->mobileAuth()->confirmations()->fetchConfirmations();
        foreach ($confirmations as $confirmation) {
            var_dump($steam->mobileAuth()->confirmations()->getConfirmationTradeOfferId($confirmation));
            var_dump($steam->mobileAuth()->confirmations()->acceptConfirmation($confirmation));
        }
    } catch (WgTokenInvalidException $ex) {
        // session invalid
        $steam->mobileAuth()->refreshMobileSession();
    }
} else {
    writeLine("Failed to login: {$loginResult}.");
}

function writeLine($line)
{
    echo $line.PHP_EOL;
}

function ask($prompt = '')
{
    echo $prompt;
    return rtrim(fgets(STDIN), "\n");
}
