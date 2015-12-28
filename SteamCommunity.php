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
use waylaidwanderer\SteamCommunity\Enum\CreateAccountResult;
use waylaidwanderer\SteamCommunity\Enum\LoginResult;

class SteamCommunity
{
    protected $username;
    protected $password;
    protected $cookieFilesDir;

    protected $steamId;
    protected $sessionId;

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

        $this->setSession();

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
        } else if (isset($loginJson['captcha_needed']) && $loginJson['captcha_needed']) {
            $this->requiresCaptcha = true;
            $this->captchaGID = $loginJson['captcha_gid'];
            return LoginResult::NeedCaptcha;
        } else if (isset($loginJson['emailauth_needed']) && $loginJson['emailauth_needed']) {
            $this->requiresEmail = true;
            $this->steamId = $loginJson['emailsteamid'];
            return LoginResult::NeedEmail;
        } else if (isset($loginJson['requires_twofactor']) && $loginJson['requires_twofactor'] && !$loginJson['success']) {
            $this->requires2FA = true;
            return LoginResult::Need2FA;
        } else if (isset($loginJson['login_complete']) && !$loginJson['login_complete']) {
            return LoginResult::BadCredentials;
        } else if ($loginJson['success']) {
            $this->setSession();
            $this->loggedIn = true;
            return LoginResult::LoginOkay;
        }

        return LoginResult::GeneralFailure;
    }

    /**
     * @param $email
     * @return CreateAccountResult
     */
    public function createAccount($email)
    {
        $captchaUrl = 'https://store.steampowered.com/join/';
        if (is_null($this->captchaGID)) {
            $captchaUrlResponse = $this->cURL($captchaUrl, null, null);
            //<input type="hidden" id="captchagid" name="captchagid" value="710806085505843213" />
            $pattern = '/<input(?:.*?)id=\"captchagid\"(?:.*)value=\"([^"]+).*>/i';
            preg_match($pattern, $captchaUrlResponse, $matches);
            if (isset($matches[1])) {
                $this->requiresCaptcha = true;
                $this->captchaGID = $matches[1];
                return CreateAccountResult::NeedCaptcha;
            } else {
                throw new SteamException('Unexpected response from Steam.');
            }
        } else {
            $createAccountUrl = 'https://store.steampowered.com/join/createaccount/';
            $params = [
                'accountname' => $this->username,
                'password' => $this->password,
                'email' => $email,
                'captchagid' => $this->captchaGID,
                'captcha_text' => $this->captchaText,
                'i_agree' => 1,
                'ticket' => '',
                'count' => 14
            ];
            $createAccountResponse = $this->cURL($createAccountUrl, $captchaUrl, $params);
            $createAccountJson = json_decode($createAccountResponse, true);

            if ($createAccountJson == null) {
                return CreateAccountResult::GeneralFailure;
            } else if (isset($createAccountJson['bSuccess']) && $createAccountJson['bSuccess']) {
                $this->setSession();
                $this->loggedIn = true;
                return CreateAccountResult::CreatedOkay;
            }
        }

        return CreateAccountResult::GeneralFailure;
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

    private function _isLoggedIn()
    {
        if (is_null($this->steamId)) {
            $this->setSession();
        }
        return $this->steamId != 0;
    }

    private function setSession()
    {
        $response = $this->cURL('http://steamcommunity.com/', null, null);
        $pattern = '/g_steamID = (.*);/';
        preg_match($pattern, $response, $matches);
        if (!isset($matches[1])) {
            throw new SteamException('Unexpected response from Steam.');
        }

        $steamId = str_replace('"', '', $matches[1]);
        if ($steamId == 'false') {
            $steamId = 0;
        }
        $this->steamId = $steamId;

        $pattern = '/g_sessionID = (.*);/';
        preg_match($pattern, $response, $matches);
        if (!isset($matches[1])) {
            throw new SteamException('Unexpected response from Steam.');
        }

        $this->sessionId = str_replace('"', '', $matches[1]);
    }

    private function getCookiesFilePath()
    {
        return $this->cookieFilesDir.DIRECTORY_SEPARATOR.$this->username.".cookiefile";
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
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
    public function getSessionId()
    {
        return $this->sessionId;
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