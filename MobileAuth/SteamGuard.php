<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 12:16 AM
 */

namespace waylaidwanderer\SteamCommunity\MobileAuth;


class SteamGuard
{
    private static $codeTranslations = [50, 51, 52, 53, 54, 55, 56, 57, 66, 67, 68, 70, 71, 72, 74, 75, 77, 78, 80, 81, 82, 84, 86, 87, 88, 89];
    private static $codeTranslationsLength = 26;
    private $sharedSecret = '';

    public function __construct($sharedSecret)
    {
        $this->sharedSecret = $sharedSecret;
    }

    public function generateSteamGuardCode()
    {
        return $this->generateSteamGuardCodeForTime(TimeAligner::GetSteamTime());
    }

    public function generateSteamGuardCodeForTime($time)
    {
        if (empty($this->sharedSecret)) {
            return '';
        }

        $sharedSecret = base64_decode($this->sharedSecret);
        $timeHash = pack('N*', 0) . pack('N*', floor($time / 30));
        $hmac = unpack('C*', pack('H*', hash_hmac('sha1', $timeHash, $sharedSecret, false)));
        $hashedData = [];
        $modeIndex = 0;
        foreach ($hmac as $value) {
            $hashedData[$modeIndex] = $this->intToByte($value);
            $modeIndex++;
        }

        $b = $this->intToByte(($hashedData[19] & 0xF));
        $codePoint = ($hashedData[$b] & 0x7F) << 24 | ($hashedData[$b+1] & 0xFF) << 16 | ($hashedData[$b+2] & 0xFF) << 8 | ($hashedData[$b+3] & 0xFF);

        $code = '';
        for ($i = 0; $i < 5; ++$i) {
            $code .= chr(self::$codeTranslations[$codePoint % self::$codeTranslationsLength]);
            $codePoint /= self::$codeTranslationsLength;
        }
        return $code;
    }

    private function intToByte($int)
    {
        return $int & (0xff);
    }
}
