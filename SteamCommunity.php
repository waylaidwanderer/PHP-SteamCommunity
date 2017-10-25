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
use waylaidwanderer\SteamCommunity\User\Chat;
use waylaidwanderer\SteamCommunity\SteamException;
use waylaidwanderer\SteamCommunity\Enum\CreateAccountResult;
use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\Auth\MobileAuth;

class SteamCommunity
{
    protected static $fromCache = false;

    protected static $instance = null;
    protected static $classCache = array();
    protected static $settings = array();

    protected static $cache = array();

    public function beginSteamCommunity(array $settings = array())
    {
        self::$settings = $settings;

        foreach ($settings as $key => $value) {
            if ($key == 'fromCache' && $value) {
                self::$fromCache = true;
            }

            self::$cache[$key] = $value;
        }

        if (!self::$fromCache) {
            self::getClassFromCache('Auth\Auth')->startSession();
            if (isset($settings['mobile']) && $settings['mobile']) {
                self::getClassFromCache('Auth\MobileAuth')->startSession();
            }
        }
    }

    public static function initialize(array $settings = array())
    {
        self::getInstance()->beginSteamCommunity($settings);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self(self::$settings);
        }

        return self::$instance;
    }

    public function getClass($class)
    {
        $namespaceClass = 'waylaidwanderer\\SteamCommunity\\' . $class;
        return (func_num_args() > 1 ? new $namespaceClass(func_get_args()) : new $namespaceClass());
    }

    public function getClassFromCache($class)
    {
        if (!array_key_exists($class, self::$classCache)) {
            $namespaceClass = 'waylaidwanderer\\SteamCommunity\\' . $class;
            self::$classCache[$class] = (func_num_args() > 1 ? new $namespaceClass(func_get_args()) : new $namespaceClass());
        }

        return self::$classCache[$class];
    }

    public function set($key, $value)
    {
        self::$cache[$key] = $value;
        file_put_contents($this->getFilePath('steamcommunity', $this->get('username') . '_login'), serialize($this->getAll()));
        return $value;
    }

    public function setAll(array $data = array())
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        file_put_contents($this->getFilePath('steamcommunity', $this->get('username') . '_login'), serialize($this->getAll()));
    }

    public function get($key)
    {
        if (!empty(self::$cache) && array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return null;
    }

    public function getAll(array $ignore = array())
    {
        return array_diff_key(self::$cache, array_flip($ignore));
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
        $this->getClassFromCache('Auth\Auth')->createCookieFile(($mobile ? 'mobile' : 'web'));

        if (!$relogin && $this->get('loggedIn')) {
            if ($this->get('mobileAuth') != null) {
                $this->getClassFromCache('Auth\MobileAuth')->setAuth(file_get_contents($this->getClassFromCache('Auth\Auth')->getAuthFilePath('mobile')));
            }

            return LoginResult::LoginOkay;
        }

        $rsaResponse = $this->getClassFromCache('Network')->cURL('https://steamcommunity.com/login/getrsakey', null, ['username' => $this->get('username')]);
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
        $encryptedPassword = base64_encode($rsa->encrypt($this->get('password')));

        $params = [
            'username' => $this->get('username'),
            'password' => urlencode($encryptedPassword),
            'twofactorcode' => is_null($this->get('twoFactorCode')) ? '' : $this->get('twoFactorCode'),
            'captchagid' => $this->get('requiresCaptcha') ? $this->captchaGID : '-1',
            'captcha_text' => $this->get('requiresCaptcha') ? $this->get('captchaText') : '',
            'emailsteamid' => ($this->get('requires2FA') || $this->get('requiresEmail')) ? (string) $this->get('steamId') : '',
            'emailauth' => $this->get('requiresEmail') ? $this->get('emailCode') : '',
            'rsatimestamp' => $rsaJson['timestamp'],
            'remember_login' => 'false'
        ];

        if ($this->get('mobile')) {
            $params['oauth_client_id'] = 'DE45CD61';
            $params['oauth_scope'] = 'read_profile write_profile read_client write_client';
            $params['loginfriendlyname'] = '#login_emailauth_friendlyname_mobile';
        }

        $loginResponse = $this->getClassFromCache('Network')->cURL('https://steamcommunity.com/login/dologin/', null, $params);
        $loginJson = json_decode($loginResponse, true);

        if ($loginJson == null) {
            return LoginResult::GeneralFailure;
        } else if (isset($loginJson['captcha_needed']) && $loginJson['captcha_needed']) {
            $this->requiresCaptcha = true;
            $this->set('requiresCaptcha', true);
            $this->set('captchaGID', $loginJson['captcha_gid']);
            return LoginResult::NeedCaptcha;
        } else if (isset($loginJson['emailauth_needed']) && $loginJson['emailauth_needed']) {
            $this->requiresEmail = true;
            $this->set('requiresEmail', true);
            $this->set('steamId', $loginJson['emailsteamid']);
            return LoginResult::NeedEmail;
        } else if (isset($loginJson['requires_twofactor']) && $loginJson['requires_twofactor'] && !$loginJson['success']) {
            $this->set('requires2FA', true);
            return LoginResult::Need2FA;
        } else if (isset($loginJson['login_complete']) && !$loginJson['login_complete']) {
            return LoginResult::BadCredentials;
        } else if (isset($loginJson['message']) && strpos($loginJson['message'], 'login failures') !== false) {
            return LoginResult::TooManyFailedLogins;
        } else if ($loginJson['success']) {
            if (isset($loginJson['oauth'])) {
                file_put_contents($this->getClassFromCache('Auth\Auth')->getAuthFilePath('mobile'), $loginJson['oauth']);
                $this->getClassFromCache('Auth\MobileAuth')->startSession();
                $this->getClassFromCache('Auth\MobileAuth')->setAuth($loginJson['oauth']);
            }

            if (isset($loginJson['transfer_parameters'])) {
                file_put_contents($this->getClassFromCache('Auth\Auth')->getAuthFilePath('web'), $loginJson['transfer_parameters']);
                $this->getClassFromCache('Auth\Auth')->startSession();
                $this->getClassFromCache('Auth\Auth')->setAuth($loginJson['transfer_parameters']);
            }

            $this->set('loggedIn', true);
            $this->set('lastLoginTime', time());

            return LoginResult::LoginOkay;
        }

        return LoginResult::GeneralFailure;
    }

    public function reLogin($firstResponse = false, $mobile = false)
    {
        $loginResult = $this->doLogin($mobile, true);
        if ($firstResponse && $loginResult != $firstResponse) {
            return $loginResult;
        }

        $authCode = $this->getClassFromCache('Auth\SteamGuard')->generateSteamGuardCode();
        $this->set('twoFactorCode', $authCode);

        return $this->doLogin($mobile, true);
    }

    public function getFileDir($dir)
    {
        if (empty($this->get('rootDir'))) {
            return '';
        }

        return $this->get('rootDir') . DIRECTORY_SEPARATOR . $dir;
    }

    public function getFilePath($dir, $name, $ext = false)
    {
        if (empty($this->get('rootDir'))) {
            return '';
        }

        return $this->getFileDir($dir) . DIRECTORY_SEPARATOR . $name . ($ext ? '.' . $ext : '');
    }

    /**
     * The directory and file are created automatically.
     * @param $dir
     * @param $name
     * @param $ext
     * @throws \Exception
     */
    public function createFile($dir, $name, $ext = false)
    {
        if (!empty($this->get('rootDir'))) {
            if (!file_exists($this->getFileDir($dir))) {
                mkdir($this->getFileDir($dir), 0777, true);
            }
            if (!file_exists($this->getFilePath($dir, $name, $ext))) {
                if (file_put_contents($this->getFilePath($dir, $name, $ext), '') === false) {
                    throw new \Exception("Could not create $ext for {$this->username}.");
                }
            }
        }
    }

    /**
     * Use this to get the captcha image.
     * @return string
     */
    public function getCaptchaLink()
    {
        return 'https://steamcommunity.com/public/captcha.php?gid=' . $this->captchaGID;
    }
}