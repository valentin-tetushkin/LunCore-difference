<?php


/*
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
*/

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class LoginPacket extends DataPacket {
	const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	const MOJANG_PUBKEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAE8ELkixyLcwlZryUQcu1TvPOmI2B7vX83ndnWRUaXm74wFfa5f/lwQNTfrLVHa2PmenpGI6JhIMUJaWZrjmMj90NoKNFSNBuKdm8rYiXsfaz3K36x/1U26HpG0ZxK/V1V";

	const EDITION_POCKET = 0;

	public $username;
	public $protocol;
	public $gameEdition;
	public $clientUUID;
	public $clientId;
	public $identityPublicKey;
	public $serverAddress;

	public $skinId = null;
	public $skin = null;

	public $clientData = [];

	public $deviceModel;
	public $deviceOS;

	public $languageCode;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function mayHaveUnreadBytes() : bool{
		return $this->protocol !== ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function decode(){
		$this->protocol = $this->getInt();
		if(!in_array($this->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)){
			$this->buffer = null;
			return;
		}

		$this->gameEdition = $this->getByte();

		$this->setBuffer($this->getString());

		$time = time();

		$chainData = json_decode($this->get($this->getLInt()))->{"chain"};
		// Start with the trusted one
		$chainKey = self::MOJANG_PUBKEY;
		while(!empty($chainData)){
			foreach($chainData as $index => $chain){
				list($verified, $webtoken) = $this->decodeToken($chain, $chainKey);
				if(isset($webtoken["extraData"])){
					if(isset($webtoken["extraData"]["displayName"])){
						$this->username = $webtoken["extraData"]["displayName"];
					}
					if(isset($webtoken["extraData"]["identity"])){
						$this->clientUUID = $webtoken["extraData"]["identity"];
					}
				}
				if($verified){
					$verified = isset($webtoken["nbf"]) && $webtoken["nbf"] <= $time && isset($webtoken["exp"]) && $webtoken["exp"] > $time;
				}
				if($verified and isset($webtoken["identityPublicKey"])){
					// Looped key chain. #blamemojang
					if($webtoken["identityPublicKey"] != self::MOJANG_PUBKEY) $chainKey = $webtoken["identityPublicKey"];
					break;
				}elseif($chainKey === null){
					// We have already gave up
					break;
				}
			}
			if(!$verified && $chainKey !== null){
				$chainKey = null;
			}else{
				unset($chainData[$index]);
			}
		}

		list($verified, $this->clientData) = $this->decodeToken($this->get($this->getLInt()), $chainKey);

		$this->clientId = $this->clientData["ClientRandomId"] ?? null;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? null;
		$this->skinId = $this->clientData["SkinId"] ?? null;

		if(isset($this->clientData["SkinData"])){
			$this->skin = base64_decode($this->clientData["SkinData"]);
		}

		if (isset($this->clientData["LanguageCode"])) {
			$this->languageCode = $this->clientData["LanguageCode"];
		}

		if(isset($this->clientData["DeviceModel"])){
			$this->deviceModel = $this->clientData["DeviceModel"];
		}

		if(isset($this->clientData["DeviceOS"])){
			$this->deviceOS = $this->clientData["DeviceOS"];
		}

		if($verified){
			$this->identityPublicKey = $chainKey;
		}
	}

	/**
	 *
	 */
	public function encode(){

	}

	/**
	 * @param $token
	 * @param $key
	 *
	 * @return array
	 */
	public function decodeToken($token, $key){
		$tokens = explode(".", $token);
		list($headB64, $payloadB64, $sigB64) = $tokens;

		if($key !== null and extension_loaded("openssl")){
			$sig = base64_decode(strtr($sigB64, '-_', '+/'), true);
			$rawLen = 48; // ES384
			for($i = $rawLen; $i > 0 and $sig[$rawLen - $i] == chr(0); $i--){
			}
			$j = $i + (ord($sig[$rawLen - $i]) >= 128 ? 1 : 0);
			for($k = $rawLen; $k > 0 and $sig[2 * $rawLen - $k] == chr(0); $k--){
			}
			$l = $k + (ord($sig[2 * $rawLen - $k]) >= 128 ? 1 : 0);
			$len = 2 + $j + 2 + $l;
			$derSig = chr(48);
			if($len > 255){
				throw new \RuntimeException("Invalid signature format");
			}elseif($len >= 128){
				$derSig .= chr(81);
			}
			$derSig .= chr($len) . chr(2) . chr($j);
			$derSig .= str_repeat(chr(0), $j - $i) . substr($sig, $rawLen - $i, $i);
			$derSig .= chr(2) . chr($l);
			$derSig .= str_repeat(chr(0), $l - $k) . substr($sig, 2 * $rawLen - $k, $k);

			$verified = openssl_verify($headB64 . "." . $payloadB64, $derSig, "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", true) . "\n-----END PUBLIC KEY-----\n", OPENSSL_ALGO_SHA384) === 1;
		}else{
			$verified = false;
		}

		return [$verified, json_decode(base64_decode($payloadB64), true)];
	}

}
