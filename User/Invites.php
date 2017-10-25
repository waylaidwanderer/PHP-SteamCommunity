<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Helper;

use waylaidwanderer\SteamCommunity\User\Invite\Friend;
use waylaidwanderer\SteamCommunity\User\Invite\Group;

class Invites
{
    const BASE_URL = 'https://steamcommunity.com/my/home/invites';
    const INVITE_URL = 'http://steamcommunity.com/id/%s/home_process';
    const FRIEND_INVITE_URL = 'https://steamcommunity.com/actions/AddFriendAjax';
	const GROUP_INVITE_URL = 'https://steamcommunity.com/actions/GroupInvite';

	private $friendInvites = array();
	private $groupInvites = array();

	public function fetchInvites()
	{
		$url = self::BASE_URL;
		$this->_parseInvites(SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url));
	}

	private function _parseInvites($html)
	{
		if (empty($html)) {
			return false;
		}

		libxml_use_internal_errors(true);

		$doc = new \DOMDocument();
		$doc->loadHTML($html);
		$xpath = new \DOMXPath($doc);

		$friendInvites = [];
		$friendInviteElements = $xpath->query('//div[@id[starts-with(.,"invite__U")]]');
		foreach ($friendInviteElements as $friendInviteElement) {
			$inviteElement = $xpath->query('.//div[contains(@class, "eventTitle")]', $friendInviteElement)->item(0);

			$name = trim($inviteElement->nodeValue);
			$profileUrl = $inviteElement->childNodes->item(0)->getAttribute('href');

			$acceptDeclineElement = $xpath->query('.//div[contains(@class, "acceptDeclineBlock")]', $friendInviteElement)->item(0);
			$jsAction = $xpath->query('.//a[contains(@class, "linkStandard")]', $acceptDeclineElement)->item(0)->getAttribute('href');
			preg_match('/\'(.*?)\'/', $jsAction, $matches); $steamId = $matches[1];

			$friendInvites[] = new Friend($steamId, $profileUrl, $name);
		}

		$groupInvites = [];
		$groupInviteElements = $xpath->query('//div[@id[starts-with(.,"invite__g")]]');
		foreach ($groupInviteElements as $groupInviteElement) {
			$inviteElement = $xpath->query('.//a[contains(@class, "linkTitle")]', $groupInviteElement)->item(0);

			$title = trim($inviteElement->nodeValue);
			$groupUrl = $inviteElement->getAttribute('href');

			$acceptDeclineElement = $xpath->query('.//div[contains(@class, "acceptDeclineBlock")]', $groupInviteElement)->item(0);
			$jsAction = $xpath->query('.//a[contains(@class, "linkStandard")]', $acceptDeclineElement)->item(0)->getAttribute('href');
			preg_match('/\'(.*?)\'/', $jsAction, $matches); $groupId = $matches[1];

			$groupInvites[] = new Group($groupId, $groupUrl, $title);
		}

		$this->friendInvites = $friendInvites;
		$this->groupInvites = $groupInvites;
	}

	protected function _resolveInviteUrl()
	{
		$url = self::INVITE_URL;
		return sprintf($url, SteamCommunity::getInstance()->get('profileId'));
	}

    public function sendFriendInvite($steamId)
    {
        $params = [
			'accept_invite' => 0,
			'steamid' => Helper::toCommunityID($steamId),
            'sessionID' => SteamCommunity::getInstance()->get('sessionId')
        ];

        $url = self::FRIEND_INVITE_URL;

        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, $params);
		if (!$response = Helper::processJson($response)) {
			return false;
		}

		if (!$response['success']) {
			return true;
		}

		return false;
    }

    public function sendGroupInvite($steamId, $gid = false)
    {
		$gid = (!$gid ? $this->gid : $gid);

        $params = [
			'type' => 'groupInvite',
			'inviter' => Helper::toCommunityID(SteamCommunity::getInstance()->get('steamId')),
			'invitee' => Helper::toCommunityID($steamId),
			'group' => $gid,
            'sessionID' => SteamCommunity::getInstance()->get('sessionId')
        ];

        $url = self::GROUP_INVITE_URL;

        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, $params);
        if (empty($response)) {
            return false;
        }

		$xml = simplexml_load_string($response);
		if ($xml === false || !isset($xml->results[0])) {
			return false;
		}

		if ($xml->results[0] != 'OK') {
			return false;
		}

		return true;
    }

	public function acceptAllFriendInvites()
	{
		foreach ($this->friendInvites as $friendInvite) {
			$this->acceptFriendInvite($friendInvite);
		}
	}

	public function acceptFriendInvite(Friend $friendInvite)
	{
		$params = [
			'action' => 'approvePending',
			'itype' => 'friend',
			'json' => 1,
			'xml' => 0,
			'perform' => 'accept',
			'id' => $friendInvite->getSteamId(),
			'sessionID' => SteamCommunity::getInstance()->get('sessionId')
		];

		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($this->_resolveInviteUrl(), null, $params);

		if (!$response = Helper::processJson($response)) {
			return false;
		}

		if (!$response['success']) {
			return false;
		}

		return true;
	}

	public function acceptAllGroupInvites()
	{
		foreach ($this->groupInvites as $groupInvite) {
			$this->acceptGroupInvite($groupInvite);
		}
	}

	public function acceptGroupInvite(Group $groupInvite)
	{
		$params = [
			'action' => 'approvePending',
			'itype' => 'group',
			'json' => 1,
			'xml' => 0,
			'perform' => 'accept',
			'id' => $groupInvite->getGroupId(),
			'sessionID' => SteamCommunity::getInstance()->get('sessionId')
		];

		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($this->_resolveInviteUrl(), null, $params);

		if (!$response = Helper::processJson($response)) {
			return false;
		}

		if (!$response['success']) {
			return false;
		}

		return $response;
	}

	public function ignoreAllInvites()
	{
		$url = self::INVITE_URL . '?action=ignoreAll&sessionID=' . SteamCommunity::getInstance()->get('sessionId');
		return SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
	}

	public function ignoreAllFriendInvites()
	{
		$url = self::INVITE_URL . '?action=ignoreAll&type=friends&sessionID=' . SteamCommunity::getInstance()->get('sessionId');
		return SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
	}

	public function ignoreAllGroupInvites()
	{
		$url = self::INVITE_URL . '?action=ignoreAll&type=groups&sessionID=' . SteamCommunity::getInstance()->get('sessionId');
		return SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
	}

	public function getFriendInvites()
	{
		return $this->friendInvites;
	}

	public function getGroupInvites()
	{
		return $this->groupInvites;
	}
}