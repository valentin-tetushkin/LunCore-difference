<?php

/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\tile;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\block\Piston;

class PistonArm extends Spawnable {
	
	public function getSpawnCompound(){
     $sticky = 0;
     $block = $this->level->getBlock($this);
     if($block instanceof Piston) {
     $sticky = $block->isSticky() ? 1 : 0;
     }
		return new CompoundTag("", [
			new StringTag("id", Tile::PISTON_ARM),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z),
			new FloatTag("Progress", $this->namedtag['Progress']),
			new ByteTag("State", $this->namedtag['State']),
            new ByteTag("Sticky", $sticky),
		]);
	}
}