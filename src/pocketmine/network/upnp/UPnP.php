<?php

namespace pocketmine\network\upnp;

use pocketmine\utils\Utils;

abstract class UPnP {
	/**
	 * @param $port
	 *
	 * @return bool
	 */
	public static function PortForward($port){
		if(!Utils::$online){
			throw new \RuntimeException("Server is offline");
		}
		if(Utils::getOS() !== "win"){
			throw new \RuntimeException("UPnP is only supported on Windows");
		}
		if(!class_exists("COM")){
			throw new \RuntimeException("UPnP requires the com_dotnet extension");
		}
		$port = (int) $port;
		$myLocalIP = gethostbyname(trim(`hostname`));
		/** @noinspection PhpUndefinedClassInspection */
		$com = new \COM("HNetCfg.NATUPnP");
		/** @noinspection PhpUndefinedFieldInspection */
		if($com === false or !is_object($com->StaticPortMappingCollection)){
			throw new \RuntimeException("Failed to portforward (unsupported?)");
		}

		/** @noinspection PhpUndefinedFieldInspection */
		$com->StaticPortMappingCollection->Add($port, "UDP", $port, $myLocalIP, true, "PocketMine-MP");
	}

	/**
	 * @param $port
	 *
	 * @return bool
	 */
	public static function RemovePortForward($port){
		if(Utils::$online === false){
			return false;
		}
		if(Utils::getOS() != "win" or !class_exists("COM")){
			return false;
		}
		$port = (int) $port;
		try{
			$com = new \COM("HNetCfg.NATUPnP");
			if($com === false or !is_object($com->StaticPortMappingCollection)){
				return false;
			}
			$com->StaticPortMappingCollection->Remove($port, "UDP");
		}catch(\Throwable $e){
			return false;
		}

		return true;
	}
}
