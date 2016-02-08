<?php

require '../vendor/autoload.php';
defined('STDIN') or define('STDIN', fopen("php://stdin","r"));

use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\SteamCommunity;

date_default_timezone_set('America/Los_Angeles');

$settings = json_decode(file_get_contents('settings.json'), true);

$steam = new SteamCommunity($settings, dirname(__FILE__));
$loginResult = $steam->doLogin();
while ($loginResult != LoginResult::LoginOkay) {
    if ($loginResult == LoginResult::Need2FA) {
        if ($steam->getSteamGuard() == null) {
            $authCode = ask('Enter 2FA code: ');
            $steam->setTwoFactorCode($authCode);
        } else {
            $authCode = $steam->getSteamGuard()->GenerateSteamGuardCode();
            $steam->setTwoFactorCode($authCode);
            writeLine('Generated Steam Guard code: ' . $authCode);
        }
        $loginResult = $steam->doLogin();
    } else if ($loginResult == LoginResult::NeedEmail) {
        $authCode = ask('Enter Steam Guard code from email: ');
        $steam->setEmailCode($authCode);
        $loginResult = $steam->doLogin();
    } else {
        break;
    }
    writeLine("Login result: {$loginResult}.");
}

if ($loginResult == LoginResult::LoginOkay) {
    writeLine('Logged in successfully.');

    $tradeOffers = $steam->getTradeOffers();

    /*
    $trade = $tradeOffers->createTrade(12345);
    $trade->addOtherItem(730, 2, "12345678");
    var_dump($trade->send());
    */

    var_dump($steam->getMarket()->getWalletBalance());
    var_dump($steam->getApiKey());
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
