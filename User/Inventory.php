<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Helper;

class Inventory
{
    private $steamId;
    private $profile;
    private $cacheTime;

    private $iconUrl = 'https://steamcommunity-a.akamaihd.net/economy/image/';

    public function __construct($cacheTime = 60, $language = 'english')
    {
        $this->cacheTime = $cacheTime;
        $this->language = $language;
    }

    public function loadInventory($steamId, $appId = 1, $contextId = 2, $useCache = false)
    {
        if ($appId == 1) {
            $steamCommunity = SteamCommunity::getInstance();
            $globalAppIds = $steamCommunity->get('appIds');
            if (empty($globalAppIds)) {
                return false;
            }

            return $this->loadChosenInventory($steamId, $globalAppIds, $globalAppDetails['contextId'], $useCache);
        }

        return $this->loadChosenInventory($steamId, $appId, $contextId, $useCache);
    }

    private function loadChosenInventory($steamId, $appId, $contextId, $useCache)
    {
        if (!is_array($appId)) {
            $appId = array($appId);
        }

        if (!is_array($contextId)) {
            $contextId = array($contextId);
        }

        $inventories = array();

        foreach ($appId as $aId) {
            foreach ($contextId as $cId) {
                $cacheFile = $this->getInventoryPath($steamId, $aId, $cId);
                if ($useCache) {
                    $cacheData = @file_get_contents($cacheFile);
                    if ($cacheData !== false && (time() - filemtime($cacheFile)) < ($this->cacheTime * 60)) {
                        $inventories = $this->mergeResponses($inventories, Helper::processJson($cacheData)); continue;
                    }
                }

                if ($inventory = $this->getSteamInventory($steamId, $aId, $cId)) {
                    $inventory = $this->parseItems($inventory, $cId);
                }

                if (!$inventory) {
                    continue;
                }

                if ($useCache) {
                    file_put_contents($cacheFile, json_encode($inventory, JSON_UNESCAPED_UNICODE));
                }

                $inventories = $this->mergeResponses($inventories, $inventory);
            }
        }

        if (empty($inventories)) {
            return false;
        }

        return $inventories;
    }

    private function getSteamInventory($steamId, $appId, $contextId, $allData = array(), $startAssetId = false)
    {
        $steamId = Helper::to64ID($steamId);
        if (!$this->checkInfo($steamId, $appId, $contextId)) {
            return false;
        }

        $json = $this->querySteam($steamId, $appId, $contextId, $startAssetId);

        if (!$json || empty($json['success']) || count($json) == 0 || !array_key_exists('assets', $json) || !array_key_exists('descriptions', $json)) {
            return false;
        }

        if (empty($allData)) {
            $allData = array(
                'assets' => $json['assets'],
                'descriptions' => $json['descriptions'],
                'totalInventoryCount' => $json['total_inventory_count']
            );
        } else {
            $allData['assets'] = $this->mergeResponses($allData['assets'], $json['assets']);
            $allData['descriptions'] = $this->mergeResponses($allData['descriptions'], $json['descriptions']);
        }

        if (isset($json['more_items']) && $json['more_items'] && isset($json['last_assetid'])) {
            return $this->getSteamInventory($steamId, $appId, $contextId, $allData, $json['last_assetid']);
        }

        return $allData;
    }

    private function mergeResponses($globalResponse, $newResponse, $useKey = false)
    {
        foreach ($newResponse as $dataId => $data) {
            if ($useKey) {
                $globalResponse[$dataId] = $data;
            } else {
                $globalResponse[] = $data;
            }
        }

        return $globalResponse;
    }

    private function querySteam($steamId, $appId, $contextId, $startAssetId = false, $count = 500, $trading = true)
    {
        $url = $this->steamApiUrl($steamId, $appId, $contextId, $startAssetId, $count, $trading, $this->language);

        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
        if (!$response = Helper::processJson($response)) {
            return false;
        }

        return $response;        
    }

    public function parseItems($data, $contextId)
    {
        $descriptions = array();
        foreach ($data['descriptions'] as $dataItem) {
            $name = trim(@end((explode('|', $dataItem['name']))));

            $desc = null;
            if (isset($dataItem['descriptions'])) {
                $desc = $this->parseDataArray($dataItem['descriptions']);
            }

            $actions = null;
            if (isset($dataItem['actions'])) {
                $actions = $this->parseDataArray($dataItem['actions']);
            }

            $tags = $this->parseItemTags($dataItem['tags']);
            $cat = (isset($tags['category']) ? $tags['category'] : null);

            $array = [
                'app_id'               => $dataItem['appid'],            // 730
                'class_id'             => $dataItem['classid'],          // 310777928
                'instance_id'          => $dataItem['instanceid'],       // 480085569 or 0
                'context_id'           => $contextId,
                'name'                 => $dataItem['name'],                                     // Sand Dune
                'market_name'          => (isset($dataItem['market_name']) ? $dataItem['market_name'] : null),      // P250 | Sand Dune (Field-Tested)
                'market_name_hash'     => (isset($dataItem['market_name_hash']) ? $dataItem['market_name_hash'] : null),      // P250 | Sand Dune (Field-Tested)
                'weapon'               => (isset($tags['Weapon']) ? $tags['Weapon'] : null),                // P250
                'type'                 => (isset($tags['Type']) ? $tags['Type'] : null),                  // Pistol
                'quality'              => (isset($tags['Quality']) ? $tags['Quality'] : null),               // Consumer Grade
                'exterior'             => (isset($tags['Exterior']) ? $tags['Exterior'] : null),              // Field-Tested
                'collection'           => (isset($tags['Collection']) ? $tags['Collection'] : null),            // The Dust 2 Collection
                'stattrack'            => (stripos($cat, 'StatTrak') !== false) ? true : null,
                'icon_url'             => (isset($dataItem['icon_url']) ? $this->iconUrl . $dataItem['icon_url'] : null),         // fWFc82js0fmoRAP-qOIPu5THSWqfSmTEL ...
                'icon_url_large'       => (isset($dataItem['icon_url_large']) ? $this->iconUrl . $dataItem['icon_url_large'] : null),   // fWFc82js0fmoRAP-qOIPu5THSWqfSmTEL ...
                'description'          => $desc,
                'actions'              => $actions,
                'marketable'           => (isset($dataItem['marketable']) ? $dataItem['marketable'] : null),
                'name_color'           => (isset($dataItem['name_color']) ? '#' . $dataItem['name_color'] : null),
            ];

            foreach ($array as $key => $value) {
                if ($value === null) {
                    unset($array[$key]);
                }
            }

            $unique = $this->getUniqueItemId($dataItem['appid'], $dataItem['classid'], $dataItem['instanceid'], $contextId);
            $descriptions[$unique] = $array;

            unset($desc, $tags, $cat, $array, $asset, $unique);
        }

        $items = array();

        foreach ($data['assets'] as $asset) {
            $unique = $this->getUniqueItemId($asset['appid'], $asset['classid'], $asset['instanceid'], $contextId);
            if (!array_key_exists($unique, $descriptions)) {
                continue;
            }

            $items[$asset['assetid']] = array(
                'asset_id' => $asset['assetid'],
                'amount' => $asset['amount']
            ) + $descriptions[$unique];

            unset($unique, $dataAsset);
        }

        return $items;
    }

    private function getUniqueItemId($appId, $classId, $instanceId, $contextId)
    {
        return ($appId ? $appId : '0') . ($classId ? $classId : '0') . ($instanceId ? $instanceId : '0') . ($contextId ? $contextId : '0');
    }

    private function parseDataArray($data)
    {
        foreach ($data as $descId => $desc) {
            foreach ((array) $desc as $id => $val) {
                $val = trim($val);
                if (empty($val)) {
                    unset($data[$descId][$id]); continue;
                }

                $data[$descId][$id] = $val;
            }
        }

        return array_filter($data);
    }

    private function parseItemTags(array $tags)
    {
        if (!count($tags)) {
            return [];
        }

        $parsed = [];

        foreach ($tags as $tag) {
            $categoryName = $tag['localized_category_name'];
            $tagName = $tag['localized_tag_name'];

            $parsed[$categoryName] = $tagName;
        }

        return $parsed;
    }

    private function checkInfo($steamId, $appId, $contextId)
    {
        if (!is_numeric($steamId) || !is_numeric($appId) || !is_numeric($contextId)) {
            return false;
        }

        return true;
    }

    private function getInventoryPath($steamId, $appId, $contextId = 2)
    {
        return SteamCommunity::getInstance()->getFilePath('inventories', $appId . '_' . $contextId . '_' . $steamId, 'json');
    }

    private function steamApiUrl($steamId, $appId, $contextId, $startAssetId = false, $count = 500, $trading = true, $lang = 'english')
    {
        return 'http://steamcommunity.com/inventory/' . $steamId . '/' . $appId . '/' . $contextId . '/?l=' . $lang . '&trading=' . (int) $trading . '&count=' . (($count != 500) ? $count : 500) . ($startAssetId ? '&start_assetid=' . $startAssetId : '');
    }
}