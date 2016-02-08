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
    private $sharedSecret = '';

    public function __construct($sharedSecret = '')
    {
        $this->sharedSecret = $sharedSecret;
    }

    public function GenerateSteamGuardCode()
    {
        return $this->GenerateSteamGuardCodeForTime(TimeAligner::GetSteamTime());
    }

    public function GenerateSteamGuardCodeForTime($time)
    {
        if (empty($this->sharedSecret)) {
            return '';
        }

        $sharedSecret = base64_decode($this->sharedSecret);

        $time /= 30;
        $timeArray = [];
        for ($i = 8; $i > 0; $i--) {
            $timeArray[$i - 1] = $this->intToByte($time);
            $time >>= 8;
        }
        $timeArray = array_reverse($timeArray);
        $timeHash = '';
        foreach ($timeArray as $value) {
            $timeHash .= chr($value);
        }

        $hmac = unpack('C*', pack('H*', hash_hmac('sha1', $timeHash, $sharedSecret, false)));
        $hashedData = [];
        $modeIndex = 0;
        foreach ($hmac as $value) {
            $hashedData[$modeIndex] = $this->intToByte($value);
            $modeIndex++;
        }

        $b = $this->intToByte(($hashedData[19] & 0xF));
        $codePoint = ($hashedData[$b] & 0x7F) << 24 | ($hashedData[$b+1] & 0xFF) << 16 | ($hashedData[$b+2] & 0xFF) << 8 | ($hashedData[$b+3] & 0xFF);

        $steamGuardCodeCharset = "23456789BCDFGHJKMNPQRTVWXY";
        $steamGuardCodeCharsetLength = strlen($steamGuardCodeCharset);
        $code = "";
        for ($i = 0; $i < 5; $i++)
        {
            $code = $code."".$steamGuardCodeCharset{floor($codePoint) % $steamGuardCodeCharsetLength};
            $codePoint /= $steamGuardCodeCharsetLength;
        }
        return $code;
    }

    private function intToByte($int)
    {
        return $int & (0xff);
    }
}