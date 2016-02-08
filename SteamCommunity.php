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
use waylaidwanderer\SteamCommunity\MobileAuth\SteamGuard;

class SteamCommunity
{
    private $username = '';
    private $password = '';
    private $cookieFilesDir = '';
    private $steamGuard;

    private $apiKeyDomain = '';
    private $apiKey;

    private $steamId;
    private $sessionId;

    private $requiresCaptcha = false;
    private $captchaGID;
    private $captchaText;

    private $requiresEmail = false;
    private $emailCode;

    private $requires2FA = false;
    private $twoFactorCode;

    private $loggedIn = false;

    private $market;
    private $tradeOffers;

    public function __construct($settings = [], $cookieFilesDir = '') {
        if (isset($settings['username'])) {
            $this->username = $settings['username'];
        }
        if (isset($settings['password'])) {
            $this->password = $settings['password'];
        }
        if (isset($settings['apiKeyDomain'])) {
            $this->apiKeyDomain = $settings['apiKeyDomain'];
        }
        if (isset($settings['sharedSecret']) && !empty($settings['sharedSecret'])) {
            $this->steamGuard = new SteamGuard($settings['sharedSecret']);
        }
        $this->cookieFilesDir = $cookieFilesDir;

        $this->_setSession();

        $this->market = new Market($this);
        $this->tradeOffers = new TradeOffers($this);
    }

    /**
     * Login with the set username and password.
     * @return LoginResult
     * @throws SteamException Thrown when Steam gives an unexpected response (e.g. Steam is down/having issues)
     * @throws \Exception Thrown when cookiefile is unable to be created.
     */
    public function doLogin()
    {
        if (!empty($this->cookieFilesDir) && !file_exists($this->_getCookiesFilePath())) {
            if (file_put_contents($this->_getCookiesFilePath(), '') === false) {
                throw new \Exception("Could not create cookiefile for {$this->username}.");
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
            $this->_setSession();
            $this->loggedIn = true;
            return LoginResult::LoginOkay;
        }

        return LoginResult::GeneralFailure;
    }

    /**
     * Create a new Steam account.
     * @param $email
     * @return CreateAccountResult
     * @throws SteamException Thrown when Steam gives an unexpected response (e.g. Steam is down/having issues)
     * @throws \Exception Thrown when cookiefile is unable to be created.
     */
    public function createAccount($email)
    {
        $captchaUrl = 'https://store.steampowered.com/join/';
        if (is_null($this->captchaGID)) {
            $captchaUrlResponse = $this->cURL($captchaUrl);
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
            if (!empty($this->cookieFilesDir) && !file_exists($this->_getCookiesFilePath())) {
                if (file_put_contents($this->_getCookiesFilePath(), '') === false) {
                    throw new \Exception("Could not create cookiefile for {$this->username}.");
                }
            }

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
                $this->_setSession();
                $this->loggedIn = true;
                return CreateAccountResult::CreatedOkay;
            }
        }

        return CreateAccountResult::GeneralFailure;
    }

    public function cURL($url, $ref = null, $postData = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if (!empty($this->cookieFilesDir)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_getCookiesFilePath());
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_getCookiesFilePath());
        }
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
            $this->_setSession();
        }
        return $this->steamId != 0;
    }

    private function _setSession()
    {
        $response = $this->cURL('http://steamcommunity.com/');
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

        $this->_setApiKey();
    }

    private function _getCookiesFilePath()
    {
        if (empty($this->cookieFilesDir)) return '';
        return $this->cookieFilesDir.DIRECTORY_SEPARATOR.'cookiefiles'.DIRECTORY_SEPARATOR.$this->username.".cookiefile";
    }

    private function _setApiKey()
    {
        $url = 'https://steamcommunity.com/dev/apikey';
        $response = $this->cURL($url);
        if (preg_match('/<h2>Access Denied<\/h2>/', $response)) {
            $this->apiKey = '';
        } else if (preg_match('/<p>Key: (.*)<\/p>/', $response, $matches)) {
            $this->apiKey = $matches[1];
        } else if (!empty($this->apiKeyDomain)) {
            $registerUrl = 'https://steamcommunity.com/dev/registerkey';
            $params = [
                'domain' => $this->apiKeyDomain,
                'agreeToTerms' => 'agreed',
                'sessionid' => $this->sessionId,
                'Submit' => 'Register'
            ];
            $this->cURL($registerUrl, $url, $params);
            $this->_setApiKey();
        } else {
            $this->apiKey = '';
        }
    }

    /**
     * In most cases, you don't need to call this since an API key is registered automatically upon logging in as long as you have set the domain first.
     * @param string $domain
     * @return string
     * @throws SteamException Thrown when Steam gives an unexpected response (e.g. Steam is down/having issues)
     */
    public function registerApiKey($domain = '')
    {
        $this->apiKeyDomain = $domain;
        $this->_setApiKey();
        if (empty($this->apiKey)) {
            throw new SteamException('Could not register API key.');
        }
        return $this->apiKey;
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
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set this before logging in if you want an API key to be automatically registered.
     * @param string $apiKeyDomain
     */
    public function setApiKeyDomain($apiKeyDomain)
    {
        $this->apiKeyDomain = $apiKeyDomain;
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

    /**
     * Use this to get the captcha image.
     * @return string
     */
    public function getCaptchaLink()
    {
        return 'https://steamcommunity.com/public/captcha.php?gid=' . $this->captchaGID;
    }

    /**
     * Set this after a captcha is encountered when logging in or creating an account.
     * @param string $captchaText
     */
    public function setCaptchaText($captchaText)
    {
        $this->captchaText = $captchaText;
    }

    /**
     * Set this after email auth is required when logging in.
     * @param string $emailCode
     */
    public function setEmailCode($emailCode)
    {
        $this->emailCode = $emailCode;
    }

    /**
     * Set this after 2FA is required when logging in.
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

    /**
     * Returns an instance of the Market class.
     * @return Market
     */
    public function getMarket()
    {
        return $this->market;
    }

    /**
     * Returns an instance of the TradeOffers class.
     * @return TradeOffers
     */
    public function getTradeOffers()
    {
        return $this->tradeOffers;
    }

    /**
     * @return SteamGuard
     */
    public function getSteamGuard()
    {
        return $this->steamGuard;
    }
}