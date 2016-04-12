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
use waylaidwanderer\SteamCommunity\MobileAuth\MobileAuth;

class SteamCommunity
{
    static $DEFAULT_MOBILE_COOKIES = ['mobileClientVersion' => '0 (2.1.3)', 'mobileClient' => 'android', 'Steam_Language' => 'english', 'dob' => ''];

    private $username = '';
    private $password = '';
    private $rootDir = '';
    private $mobile = false;

    private $mobileAuth;

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

    /**
     * SteamCommunity constructor.
     * @param array $settings An array containing the account's information.
     * @param string $rootDir The absolute path of the cookiefiles/authfiles directory root.
     * @param bool $mobile
     */
    public function __construct($settings = [], $rootDir = '', $mobile = false) {
        if (isset($settings['username'])) {
            $this->username = $settings['username'];
        }
        if (isset($settings['password'])) {
            $this->password = $settings['password'];
        }
        if (isset($settings['apiKeyDomain'])) {
            $this->apiKeyDomain = $settings['apiKeyDomain'];
        }
        if (isset($settings['apiKey'])) {
            $this->apiKey = $settings['apiKey'];
        }
        if (isset($settings['mobileAuth'])) {
            $this->mobileAuth = new MobileAuth($settings['mobileAuth'], new SteamCommunity([
                'username' => $settings['username'],
                'password' => $settings['password']
            ], $rootDir, true));
        }
        $this->rootDir = $rootDir;
        $this->mobile = $mobile;

        $this->_setSession();

        if (!$mobile) {
            $this->market = new Market($this);
            $this->tradeOffers = new TradeOffers($this);
        }
    }

    /**
     * Login with the set username and password.
     * @param bool $mobile Set to true to login as a mobile user.
     * @param bool $relogin Set to true to force a fresh login session.
     * @return LoginResult
     * @throws SteamException Thrown when Steam gives an unexpected response (e.g. Steam is down/having issues)
     */
    public function doLogin($mobile = false, $relogin = false)
    {
        $this->mobile = $mobile;
        $this->_createAuthFile();
        $this->_createCookieFile();

        if ($this->_isLoggedIn() && !$relogin) {
            if ($this->mobileAuth != null) {
                $this->mobileAuth->setOauth(file_get_contents($this->getAuthFilePath()));
            }
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
        if ($mobile) {
            $params['oauth_client_id'] = 'DE45CD61';
            $params['oauth_scope'] = 'read_profile write_profile read_client write_client';
            $params['loginfriendlyname'] = '#login_emailauth_friendlyname_mobile';
        }

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
            if (isset($loginJson['oauth'])) {
                file_put_contents($this->getAuthFilePath(), $loginJson['oauth']);
            }
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
            $this->_createCookieFile();

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
        if (!empty($this->rootDir)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_getCookieFilePath());
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_getCookieFilePath());
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if ($this->mobile) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Requested-With: com.valvesoftware.android.steam.community"]);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; Google Nexus 4 - 4.1.1 - API 16 - 768x1280 Build/JRO03S) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30");
            curl_setopt($ch, CURLOPT_COOKIE, $this->buildCookie(self::$DEFAULT_MOBILE_COOKIES));
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        }
        if (isset($ref)) {
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        }
        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            $postStr = "";
            foreach ($postData as $key => $value) {
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

    private function buildCookie($cookie) {
        $out = "";
        foreach ($cookie as $k => $c) {
            $out .= "{$k}={$c}; ";
        }
        return $out;
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
        if ($this->mobileAuth != null) {
            $this->mobileAuth->startMobileSession();
        }
    }

    private function _getFileDir($dir)
    {
        if (empty($this->rootDir)) return '';
        return $this->rootDir.DIRECTORY_SEPARATOR.$dir;
    }

    private function _getFilePath($dir, $name, $ext)
    {
        if (empty($this->rootDir)) return '';
        return $this->_getFileDir($dir).DIRECTORY_SEPARATOR.$name.'.'.$ext;
    }

    /**
     * The directory and file are created automatically.
     * @param $dir
     * @param $name
     * @param $ext
     * @throws \Exception
     */
    private function _createFile($dir, $name, $ext)
    {
        if (!empty($this->rootDir)) {
            if (!file_exists($this->_getFileDir($dir))) {
                mkdir($this->_getFileDir($dir), 0777, true);
            }
            if (!file_exists($this->_getFilePath($dir, $name, $ext))) {
                if (file_put_contents($this->_getFilePath($dir, $name, $ext), '') === false) {
                    throw new \Exception("Could not create $ext for {$this->username}.");
                }
            }
        }
    }

    private function _getCookieFilePath()
    {
        $name = $this->mobile ? $this->username . '_auth' : $this->username;
        return $this->_getFilePath('cookiefiles', $name, 'cookiefile');
    }

    private function _createCookieFile()
    {
        $name = $this->mobile ? $this->username . '_auth' : $this->username;
        $this->_createFile('cookiefiles', $name, 'cookiefile');
    }

    public function getAuthFilePath()
    {
        return $this->_getFilePath('authfiles', $this->username, 'authfile');
    }

    private function _createAuthFile()
    {
        $this->_createFile('authfiles', $this->username, 'authfile');
    }

    private function _setApiKey($recursionLevel = 1)
    {
        if (!$this->apiKey) {
            $url = 'https://steamcommunity.com/dev/apikey';
            $response = $this->cURL($url);
            if (preg_match('/<h2>Access Denied<\/h2>/', $response)) {
                $this->apiKey = '';
            } else if (preg_match('/<p>Key: (.*)<\/p>/', $response, $matches)) {
                $this->apiKey = $matches[1];
            } else if ($recursionLevel < 3 && !empty($this->apiKeyDomain)) {
                $registerUrl = 'https://steamcommunity.com/dev/registerkey';
                $params = [
                    'domain' => $this->apiKeyDomain,
                    'agreeToTerms' => 'agreed',
                    'sessionid' => $this->sessionId,
                    'Submit' => 'Register'
                ];
                $this->cURL($registerUrl, $url, $params);
                $recursionLevel++;
                $this->_setApiKey($recursionLevel);
            } else {
                $this->apiKey = '';
            }
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
    public function market()
    {
        return $this->market;
    }

    /**
     * Returns an instance of the TradeOffers class.
     * @return TradeOffers
     */
    public function tradeOffers()
    {
        return $this->tradeOffers;
    }

    /**
     * Returns an instance of the MobileAuth class.
     * @return MobileAuth
     */
    public function mobileAuth()
    {
        return $this->mobileAuth;
    }
}
