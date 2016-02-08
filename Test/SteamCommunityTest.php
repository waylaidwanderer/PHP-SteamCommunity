<?php

/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-27
 * Time: 12:33 PM
 */
class SteamCommunityTest extends PHPUnit_Framework_TestCase
{
    public function test_setSessionWhenNotLoggedIn()
    {
        $steam = new \waylaidwanderer\SteamCommunity\SteamCommunity([], dirname(__FILE__));
        $this->assertSame(0, $steam->getSteamId());
        $this->assertNotNull($steam->getSessionId());
    }
}
