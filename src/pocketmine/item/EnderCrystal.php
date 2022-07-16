<?php


/* @author LunCore team
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

namespace pocketmine\item;

use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\{Player, Server};
use pocketmine\nbt\tag\{CompoundTag, StringTag, ShortTag, ListTag, LongTag, ByteTag, IntTag, DoubleTag, FloatTag, Enum};
use pocketmine\entity\{Entity};
use pocketmine\entity\EnderCrystal as Crystal;

class EnderCrystal extends Item{

    const ENDER_CRYSTAL = ;
    private $temporalVector = null;
	/**
	 * EyeOfEnder constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::ENDER_CRYSTAL, 0, $count, "Ender Crystal");
		if($this->temporalVector === null) $this->temporalVector = new Vector3(0, 0, 0);
	}

	public function canBeActivated() : bool{
		return true;
	}
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
        if($target->getId() !== 49 and $target->getId() !== 7) return;
        if($level->getBlock($target->asVector3()->add(0, 1))->getId() !== 0) return;
        $player->getInventory()->setItemInHand(Item::get(426, 0, $player->getItemInHand()->getCount() - 1));
        $npc = new Crystal($player->level, new CompoundTag("", [new ListTag("Pos", [new DoubleTag("", $target->getX() + 0.5), new DoubleTag("", $target->getY() + 1), new DoubleTag("", $target->getZ() + 0.5)]), new ListTag("Motion", [new DoubleTag("", 0.0), new DoubleTag("", 0.0), new DoubleTag("", 0.0)]), new ListTag("Rotation", [new FloatTag("", $player->getYaw()), new FloatTag("", $player->getPitch())])]));
        $npc->spawnToAll();
	}
}