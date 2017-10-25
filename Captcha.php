<?php

namespace waylaidwanderer\SteamCommunity;

use waylaidwanderer\SteamCommunity\SteamCommunity;

class Captcha
{
    protected $image;
    protected $callback;
    protected $key = '900d19054ebe3f80dc62f31287973f79';

    protected $id;
    protected $text;
    protected $maxTries;

    public function __construct($image, $callback, $maxTries = 20)
    {
        $this->image = $image;
        $this->callback = $callback;
        $this->maxTries = $maxTries;
    }

    public function send()
    {
        $response = SteamCommunity::getInstance()->getClassFromCache('Proxy')->cURL('http://2captcha.com/in.php', null, array(
            'method' => 'base64',
            'key' => $this->key,
            'body' => $this->image
        ));

        var_dump($response);
        $info = explode('|', $info);
        $this->id = (int) $info[1];

        return true;
    }

    public function receive()
    {
        $currentTries = 0;
        if ($this->id) {
            do {
                $info = SteamCommunity::getInstance()->getClassFromCache('Proxy')->cURL('http://2captcha.com/res.php?key=' . $this->key . '&action=get&id=' . $this->id);

                if ($info == 'CAPCHA_NOT_READY') {
                    usleep(300);
                }
            } while($info == 'CAPCHA_NOT_READY' || $currentTries < $this->maxTries);

            $info = explode('|', $info);
            $this->text = $info[1];
        }

        return false;
    }

    public function solve()
    {
        if (!$this->send()) {
            return false;
        }

        if (!$this->receive()) {
            return false;
        }

        return true;
    }

    public function solveAndContinue()
    {
        if (!$this->solve()) {
            return false;
        }
    }
}