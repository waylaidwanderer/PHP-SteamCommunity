<?php

namespace waylaidwanderer\SteamCommunity;

use waylaidwanderer\SteamCommunity\SteamCommunity;

class Network
{
    /**
     * Proxy Format
     * 
     * array(
     *      'type' => CURLPROXY_HTTP,
     *        'address' => '127.0.0.1:8888',
     *        'auth' => 'user:password'
     * )
     *
     */

    public static $DEFAULT_MOBILE_COOKIES = ['mobileClientVersion' => '0 (2.1.3)', 'mobileClient' => 'android', 'Steam_Language' => 'english', 'dob' => ''];

    private $defaultProxyType = CURLPROXY_HTTP;
    private $proxies = array();
    private $interfaces = array();

    public function __construct()
    {
        if ($proxies = SteamCommunity::getInstance()->get('proxies')) {
            foreach ($proxies as $proxy) {
                if ($this->validateProxy($proxy)) {
                    $this->proxies[] = $proxy;
                }
            }
        }

        if ($interfaces = SteamCommunity::getInstance()->get('interfaces')) {
            foreach ($interfaces as $interface) {
                $this->interfaces[] = $interface;
            }
        }
    }

    public function cURL($url, $ref = null, $postData = null, array $options = array(), $type = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if (isset($options['connectionTimeOut'])) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['connectionTimeOut']);
        }
        if (isset($options['timeOut'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeOut']);
        }

        curl_setopt($ch, CURLOPT_TCP_FASTOPEN, 1);
        curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if (!$type) {
            $type = (SteamCommunity::getInstance()->get('mobile') ? 'mobile' : 'web');
        }

        $cookie = SteamCommunity::getInstance()->getClassFromCache('Auth\Auth')->getCookieFilePath($type);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);

        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if (SteamCommunity::getInstance()->get('mobile')) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Requested-With: com.valvesoftware.android.steam.community"]);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; Google Nexus 4 - 4.1.1 - API 16 - 768x1280 Build/JRO03S) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30");
            curl_setopt($ch, CURLOPT_COOKIE, SteamCommunity::getInstance()->getClassFromCache('Auth\Auth')->buildCookie(self::$DEFAULT_MOBILE_COOKIES));
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        }
        if (isset($ref)) {
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        }
        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postData)) {
                $postStr = "";
                foreach ($postData as $key => $value) {
                    if ($postStr)
                        $postStr .= "&";
                    $postStr .= $key . "=" . $value;
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
            }
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'outputCURLHeaders'));

        if (!isset($options['defaultNetwork']) || !$options['defaultNetwork']) {
            if ($proxy = $this->chooseRandomProxy()) {
                $defaultProxyType = $this->defaultProxyType;

                curl_setopt($ch, CURLOPT_PROXYTYPE, $defaultProxyType);
                if (isset($proxy['type'])) {
                    curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['type']);
                }

                if (isset($proxy['auth'])) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['auth']);
                }

                curl_setopt($ch, CURLOPT_PROXY, $proxy['address']);
            }

            if ($interface = $this->chooseRandomInterface()) {
                curl_setopt($ch, CURLOPT_INTERFACE, $interface);
            }
        }

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function chooseRandomProxy()
    {
        if (empty($this->proxies)) {
            return false;
        }

        return $this->proxies[array_rand($this->proxies)];
    }

    public function chooseRandomInterface()
    {
        if (empty($this->interfaces)) {
            return false;
        }

        return $this->interfaces[array_rand($this->interfaces)];
    }

    protected function outputCURLHeaders($curl, $headerLine)
    {
        // echo '<pre>'; var_dump($headerLine); echo '</pre>';
        return strlen($headerLine);
    }

    protected function validateProxy($proxy)
    {
        if (!isset($proxy['address'])) {
            return false;
        }

        if (count(explode(':', $proxy['address'])) !== 2) {
            return false;
        }

        if (isset($proxy['auth']) && count(explode(':', $proxy['auth'])) !== 2) {
            return false;
        }

        return true;
    }
}