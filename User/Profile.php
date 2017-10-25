<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\User\Profile\Group;
use waylaidwanderer\SteamCommunity\Helper;

class Profile
{
    const BASE_URL = 'http://steamcommunity.com/profiles/';

    private $steamId;
    private $profile;

	public function setSteamId($steamId)
	{
		$this->steamId = $steamId;
	}

    public function getProfileXml()
    {
        if ($this->profile === null) {
            $url = self::BASE_URL . $this->steamId . '/?xml=1';
            $xml = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
            if (substr($xml, 0, 5) != "<?xml") {
                return false;
            }

            $this->profile = new \SimpleXMLElement($xml);
        }

        return $this->profile;
    }

    public function getPersonaName()
    {
        $profile = $this->getProfileXml();
        if ($profile) {
            return (string) $profile->steamID;
        }

        return false;
    }

	public function editProfile(array $settings = array())
	{
		$params = [
			'sessionID' => SteamCommunity::getInstance()->get('sessionId'),
			'type' => 'profileSave',
		] + $settings;

		$url = 'https://steamcommunity.com/id/' . SteamCommunity::getInstance()->get('profileId') . '/edit';
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, $url, $params);

		if (strpos($response, 'Steam Community :: Error') !== false) {
			return true;
		}

		return false;
	}

	public function profileComment($comment)
	{
		$params = [
			'comment' => $comment,
			'sessionid' => SteamCommunity::getInstance()->get('sessionId')
		];

		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/comment/Profile/post/' . Helper::toCommunityId($this->steamId) . '/-1/', null, $params);
		if (!$response = Helper::processJson($response)) {
			return false;
		}

		if (isset($response['success']) && $response['success']) {
			return true;
		}

		return false;
	}

    public function getGroups()
    {
        $profile = $this->getProfileXml();

        $groups = [];
        foreach ($profile->groups->group as $groupXml) {
            $groups[] = new Group($groupXml);
        }

        return $groups;
    }
}