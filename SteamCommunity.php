<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-04
 * Time: 11:28 PM
 */

namespace waylaidwanderer\SteamCommunity;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

class SteamCommunity
{
    protected $username;
    protected $password;
    protected $cookieFilesDir;

    protected $steamId;

    private $requiresCaptcha = false;
    private $captchaGID;
    private $captchaText;

    private $requiresEmail = false;
    private $emailCode;

    private $requires2FA = false;
    private $twoFactorCode;

    private $loggedIn = false;

    protected $market;

    public function __construct($username, $password, $cookieFilesDir) {
        $this->username = $username;
        $this->password = $password;
        $this->cookieFilesDir = $cookieFilesDir;
        $this->market = new Market($this);
    }

    /**
     * @return LoginResult
     * @throws \Exception
     */
    public function doLogin()
    {
        if (!file_exists($this->getCookiesFilePath())) {
            if (file_put_contents($this->getCookiesFilePath(), '') === false) {
                throw new SteamException("Could not create cookies file for {$this->username}.");
            }
        }

        if ($this->_isLoggedIn()) {
            $this->loggedIn = true;
            return LoginResult::LoginOkay;
        }

        $rsaResponse = $this->cURL('https://steamcommunity.com/login/getrsakey', null, ['username' => $this->username]);
        $rsaJson = json_decode($rsaResponse, true);
        if ($rsaJson == null) {
            return LoginResult::GeneralFailure;
        }

        if (!$rsaJson['success']) {
            return LoginResult::BadRSA;
        }

        $rsa = new RSA();
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $key = [
            'modulus' => new BigInteger($rsaJson['publickey_mod'], 16),
            'publicExponent' => new BigInteger($rsaJson['publickey_exp'], 16)
        ];
        $rsa->loadKey($key, RSA::PUBLIC_FORMAT_RAW);
        $encryptedPassword = base64_encode($rsa->encrypt($this->password));

        $params = [
            'username' => $this->username,
            'password' => urlencode($encryptedPassword),
            'twofactorcode' => is_null($this->twoFactorCode) ? '' : $this->twoFactorCode,
            'captchagid' => $this->requiresCaptcha ? $this->captchaGID : '-1',
            'captcha_text' => $this->requiresCaptcha ? $this->captchaText : '',
            'emailsteamid' => ($this->requires2FA || $this->requiresEmail) ? (string)$this->steamId : '',
            'emailauth' => $this->requiresEmail ? $this->emailCode : '',
            'rsatimestamp' => $rsaJson['timestamp'],
            'remember_login' => 'false'
        ];

        $loginResponse = $this->cURL('https://steamcommunity.com/login/dologin/', null, $params);
        $loginJson = json_decode($loginResponse, true);
        if ($loginJson == null) {
            return LoginResult::GeneralFailure;
        }

        if (isset($loginJson['captcha_needed']) && $loginJson['captcha_needed']) {
            $this->requiresCaptcha = true;
            $this->captchaGID = $loginJson['captcha_gid'];
            return LoginResult::NeedCaptcha;
        }

        if (isset($loginJson['emailauth_needed']) && $loginJson['emailauth_needed']) {
            $this->requiresEmail = true;
            $this->steamId = $loginJson['emailsteamid'];
            return LoginResult::NeedEmail;
        }

        if (isset($loginJson['requires_twofactor']) && $loginJson['requires_twofactor'] && !$loginJson['success']) {
            $this->requires2FA = true;
            return LoginResult::Need2FA;
        }

        if (isset($loginJson['login_complete']) && !$loginJson['login_complete']) {
            return LoginResult::BadCredentials;
        }

        if ($loginJson['success']) {
            $this->loggedIn = true;
            return LoginResult::LoginOkay;
        }

        return LoginResult::GeneralFailure;
    }

    private function _isLoggedIn()
    {
        $response = $this->cURL('http://steamcommunity.com/', null, null);
        return strpos($response, '>'.$this->username.'</span>') !== false;
    }

    public function cURL($url, $ref, $postData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookiesFilePath());
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookiesFilePath());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        if ($ref)
        {
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        }
        if ($postData)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            $postStr = "";
            foreach ($postData as $key => $value)
            {
                if ($postStr)
                    $postStr .= "&";
                $postStr .= $key . "=" . $value;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function getCookiesFilePath()
    {
        return $this->cookieFilesDir.DIRECTORY_SEPARATOR.$this->username.".cookiefiles";
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return Market
     */
    public function getMarket()
    {
        return $this->market;
    }

    /**
     * @return string
     */
    public function getSteamId()
    {
        return $this->steamId;
    }

    /**
     * @return string
     */
    public function getCaptchaGID()
    {
        return $this->captchaGID;
    }

    public function getCaptchaLink()
    {
        return 'https://steamcommunity.com/public/captcha.php?gid=' . $this->captchaGID;
    }

    /**
     * @param string $captchaText
     */
    public function setCaptchaText($captchaText)
    {
        $this->captchaText = $captchaText;
    }

    /**
     * @param string $emailCode
     */
    public function setEmailCode($emailCode)
    {
        $this->emailCode = $emailCode;
    }

    /**
     * @param string $twoFactorCode
     */
    public function setTwoFactorCode($twoFactorCode)
    {
        $this->twoFactorCode = $twoFactorCode;
    }

    /**
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->loggedIn;
    }
}