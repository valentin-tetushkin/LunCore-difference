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
use pocketmine\utils\Color;
use RuntimeException;

class ClientboundMapItemDataPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	const BITFLAG_TEXTURE_UPDATE = 0x02;
	const BITFLAG_DECORATION_UPDATE = 0x04;
	const BITFLAG_ENTITY_UPDATE = 0x08;

	public $mapId;
	public $type;
	public $eids = [];
	public $scale;
	public $decorations = [];
	public $width;
	public $height;
	public $xOffset = 0;
	public $yOffset = 0;
	/** @var Color[][] */
	public $colors = [];

	/**
	 *
	 */
	public function decode(){
		$this->mapId = $this->getEntityId();
		$this->type = $this->getUnsignedVarInt();
		if(($this->type & self::BITFLAG_ENTITY_UPDATE) !== 0){
			$count = $this->getUnsignedVarInt();
			for($i = 0; $i < $count && !$this->feof(); ++$i){
				$this->eids[] = $this->getEntityId();
			}
		}
		if(($this->type & (self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = $this->getByte();
		}
		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$count = $this->getUnsignedVarInt();
			for($i = 0; $i < $count && !$this->feof(); ++$i){
				$weird = $this->getVarInt();
				$this->decorations[$i]["rot"] = $weird & 0x0f;
				$this->decorations[$i]["img"] = $weird >> 4;
				$this->decorations[$i]["xOffset"] = $this->getByte();
				$this->decorations[$i]["yOffset"] = $this->getByte();
				$this->decorations[$i]["label"] = $this->getString();
				$this->decorations[$i]["color"] = Color::fromARGB($this->getLInt()); //already BE, don't need to reverse it again
			}
		}
		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$this->width = $this->getVarInt();
			$this->height = $this->getVarInt();
			$this->xOffset = $this->getVarInt();
			$this->yOffset = $this->getVarInt();
			for($y = 0; $y < $this->height && !$this->feof(); ++$y){
				for($x = 0; $x < $this->width && !$this->feof(); ++$x){
					$this->colors[$y][$x] = Color::fromABGR($this->getUnsignedVarInt());
				}
			}
		}
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->mapId); //entity unique ID, signed var-int
		$type = 0;
		if(($eidsCount = count($this->eids)) > 0){
			$type |= self::BITFLAG_ENTITY_UPDATE;
		}
		if(($decorationCount = count($this->decorations)) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if(count($this->colors) > 0){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}
		$this->putUnsignedVarInt($type);
		if(($type & self::BITFLAG_ENTITY_UPDATE) !== 0){ //TODO: find out what these are for
			$this->putUnsignedVarInt($eidsCount);
			foreach($this->eids as $eid){
				$this->putEntityId($eid);
			}
		}
		if(($type & (self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			$this->putByte($this->scale);
		}
		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			$this->putUnsignedVarInt($decorationCount);
			foreach($this->decorations as $decoration){
				$this->putVarInt(($decoration["rot"] & 0x0f) | ($decoration["img"] << 4));
				$this->putByte($decoration["xOffset"]);
				$this->putByte($decoration["yOffset"]);
				$this->putString($decoration["label"]);
				assert($decoration["color"] instanceof Color);
				$this->putLInt($decoration["color"]->toABGR());
			}
		}
		if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$this->putVarInt($this->width);
			$this->putVarInt($this->height);
			$this->putVarInt($this->xOffset);
			$this->putVarInt($this->yOffset);
			for($y = 0; $y < $this->height; ++$y){
				for($x = 0; $x < $this->width; ++$x){
				    $color = @$this->colors[$y][$x];
                    if($color !== null){
                        $this->putUnsignedVarInt($color->toABGR());
                    }else{
                        throw new RuntimeException("Color $y:$x not filled!");
                    }
				}
			}
		}
	}
}