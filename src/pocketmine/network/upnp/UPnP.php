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

/**
 * Поддержка переадресации портов UPnP.
 */
namespace pocketmine\network\upnp;

use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Internet;
use function count;
use function libxml_use_internal_errors;
use function parse_url;
use function preg_last_error;
use function preg_match;
use function socket_close;
use function socket_create;
use function socket_last_error;
use function socket_recvfrom;
use function socket_sendto;
use function socket_set_option;
use function socket_strerror;
use function sprintf;
use function strlen;
use function trim;
use const AF_INET;
use const SO_RCVTIMEO;
use const SOCK_DGRAM;
use const SOCKET_ETIMEDOUT;
use const SOL_SOCKET;
use const SOL_UDP;

abstract class UPnP{
	private const MAX_DISCOVERY_ATTEMPTS = 3;

	/** @var string|null */
	private static $serviceURL = null;

	private static function makePcreError() : \RuntimeException{
		$errorCode = preg_last_error();
		$message = [
			PREG_INTERNAL_ERROR => "Internal error",
			PREG_BACKTRACK_LIMIT_ERROR => "Backtrack limit reached",
			PREG_RECURSION_LIMIT_ERROR => "Recursion limit reached",
			PREG_BAD_UTF8_ERROR => "Malformed UTF-8",
			PREG_BAD_UTF8_OFFSET_ERROR => "Bad UTF-8 offset",
			PREG_JIT_STACKLIMIT_ERROR => "PCRE JIT stack limit reached"
		][$errorCode] ?? "Unknown (code $errorCode)";
		throw new \RuntimeException("PCRE error: $message");
	}

	public static function getServiceUrl() : string{
		$socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if($socket === false){
			throw new \RuntimeException("Socket error: " . trim(socket_strerror(socket_last_error())));
		}
		if(!@socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 3, "usec" => 0])){
			throw new \RuntimeException("Socket error: " . trim(socket_strerror(socket_last_error($socket))));
		}
		$contents =
			"M-SEARCH * HTTP/1.1\r\n" .
			"MX: 2\r\n" .
			"HOST: 239.255.255.250:1900\r\n" .
			"MAN: \"ssdp:discover\"\r\n" .
			"ST: upnp:rootdevice\r\n\r\n";
		$location = null;
		for($i = 0; $i < self::MAX_DISCOVERY_ATTEMPTS; ++$i){
			$sendbyte = @socket_sendto($socket, $contents, strlen($contents), 0, "239.255.255.250", 1900);
			if($sendbyte === false){
				throw new \RuntimeException("Socket error: " . trim(socket_strerror(socket_last_error($socket))));
			}
			if($sendbyte !== strlen($contents)){
				throw new \RuntimeException("Socket error: Unable to send the entire contents.");
			}
			while(true){
				if(@socket_recvfrom($socket, $buffer, 1024, 0, $responseHost, $responsePort) === false){
					if(socket_last_error($socket) === SOCKET_ETIMEDOUT){
						continue 2;
					}
					throw new \RuntimeException("Socket error: " . trim(socket_strerror(socket_last_error($socket))));
				}
				$pregResult = preg_match('/location\s*:\s*(.+)\n/i', $buffer, $matches);
				if($pregResult === false){
					//TODO: replace with preg_last_error_msg() in PHP 8.
					throw self::makePcreError();
				}
				if($pregResult !== 0){ //this might be garbage from somewhere other than the router
					$location = trim($matches[1]);
					break 2;
				}
			}
		}
		socket_close($socket);
		if($location === null){
			throw new \RuntimeException("Unable to find the router. Ensure that network discovery is enabled in Control Panel.");
		}
		$url = parse_url($location);
		if($url === false){
			throw new \RuntimeException("Failed to parse the router's url: $location");
		}
		if(!isset($url['host'])){
			throw new \RuntimeException("Failed to recognize the host name from the router's url: $location");
		}
		$urlHost = $url['host'];
		if(!isset($url['port'])){
			throw new \RuntimeException("Failed to recognize the port number from the router's url: $location");
		}
		$urlPort = $url['port'];
		$response = Internet::getURL($location, 3, [], $err, $headers, $httpCode);
		if($response === false){
			throw new \RuntimeException("Unable to access XML: $err");
		}
		if($httpCode !== 200){
			throw new \RuntimeException("Unable to access XML: $response");
		}

		$defaultInternalError = libxml_use_internal_errors(true);
		try{
			$root = new \SimpleXMLElement($response);
		}catch(\Exception $e){
			throw new \RuntimeException("Broken XML.");
		}
		libxml_use_internal_errors($defaultInternalError);
		$root->registerXPathNamespace("upnp", "urn:schemas-upnp-org:device-1-0");
		$xpathResult = $root->xpath(
			'//upnp:device[upnp:deviceType="urn:schemas-upnp-org:device:InternetGatewayDevice:1"]' .
			'/upnp:deviceList/upnp:device[upnp:deviceType="urn:schemas-upnp-org:device:WANDevice:1"]' .
			'/upnp:deviceList/upnp:device[upnp:deviceType="urn:schemas-upnp-org:device:WANConnectionDevice:1"]' .
			'/upnp:serviceList/upnp:service[upnp:serviceType="urn:schemas-upnp-org:service:WANIPConnection:1"]' .
			'/upnp:controlURL'
		);
		if($xpathResult === false){
			//this should be an array of 0 if there is no matching elements; false indicates a problem with the query itself
			throw new AssumptionFailedError("xpath query should not error here");
		}
		if(count($xpathResult) === 0){
			throw new \RuntimeException("Your router does not support portforwarding");
		}
		$controlURL = (string) $xpathResult[0];
        return sprintf("%s:%d/%s", $urlHost, $urlPort, $controlURL);
	}

	public static function PortForward(int $port) : void{
		if(!Internet::$online){
			throw new \RuntimeException("Server is offline");
		}

		if(self::$serviceURL === null){
			self::$serviceURL = self::getServiceUrl();
		}
		$body =
			'<u:AddPortMapping xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">' .
				'<NewRemoteHost></NewRemoteHost>' .
				'<NewExternalPort>' . $port . '</NewExternalPort>' .
				'<NewProtocol>UDP</NewProtocol>' .
				'<NewInternalPort>' . $port . '</NewInternalPort>' .
				'<NewInternalClient>' . Internet::getInternalIP() . '</NewInternalClient>' .
				'<NewEnabled>1</NewEnabled>' .
				'<NewPortMappingDescription>PocketMine-MP</NewPortMappingDescription>' .
				'<NewLeaseDuration>0</NewLeaseDuration>' .
			'</u:AddPortMapping>';

		$contents =
			'xml version="1.0"' .
			'<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">' .
			'<s:Body>' . $body . '</s:Body></s:Envelope>';

		$headers = [
			'Content-Type: text/xml',
			'SOAPAction: "urn:schemas-upnp-org:service:WANIPConnection:1#AddPortMapping"'
		];

		if(Internet::postURL(self::$serviceURL, $contents, 3, $headers, $err) === false){
			throw new \RuntimeException("Failed to portforward using UPnP: " . $err);
		}
	}

	public static function RemovePortForward(int $port) : bool{
		if(!Internet::$online){
			return false;
		}
		if(self::$serviceURL === null){
			return false;
		}

		$body =
			'<u:DeletePortMapping xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1">' .
				'<NewRemoteHost></NewRemoteHost>' .
				'<NewExternalPort>' . $port . '</NewExternalPort>' .
				'<NewProtocol>UDP</NewProtocol>' .
			'</u:DeletePortMapping>';

		$contents =
			'xml version="1.0"' .
			'<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">' .
			'<s:Body>' . $body . '</s:Body></s:Envelope>';

		$headers = [
			'Content-Type: text/xml',
			'SOAPAction: "urn:schemas-upnp-org:service:WANIPConnection:1#DeletePortMapping"'
		];

		if(Internet::postURL(self::$serviceURL, $contents, 3, $headers) === false){
			return false;
		}

		return true;
	}
}