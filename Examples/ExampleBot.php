<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:25 AM
 */

require '../vendor/autoload.php';

use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\SteamCommunity;

date_default_timezone_set('America/Los_Angeles');

$settings = json_decode(file_get_contents('settings.json'));

$steam = new SteamCommunity($settings->username, $settings->password, dirname(__FILE__).$settings->cookieFilesDir);
$loginResult = $steam->doLogin();
while ($loginResult != LoginResult::LoginOkay) {
    if ($loginResult == LoginResult::Need2FA) {
        $authCode = ask('Enter 2FA code: ');
        $steam->setTwoFactorCode($authCode);
        $loginResult = $steam->doLogin();
    } else if ($loginResult == LoginResult::NeedEmail) {
        $authCode = ask('Enter Steam Guard code from email: ');
        $steam->setEmailCode($authCode);
        $loginResult = $steam->doLogin();
    } else {
        break;
    }
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
