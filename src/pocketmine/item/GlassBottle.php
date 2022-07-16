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

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\event\player\PlayerGlassBottleEvent;
use pocketmine\level\Level;
use pocketmine\Player;

class GlassBottle extends Item {
	/**
	 * GlassBottle constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::GLASS_BOTTLE, $meta, $count, "Glass Bottle");
	}

	/**
	 * @param Level  $level
	 * @param Player $player
	 * @param Block  $block
	 * @param Block  $target
	 * @param        $face
	 * @param        $fx
	 * @param        $fy
	 * @param        $fz
	 *
	 * @return bool
	 */
	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($player === null or $player->isSurvival() !== true){
			return false;
		}
		if($target->getId() === BlockIds::STILL_WATER or $target->getId() === BlockIds::WATER){
			$player->getServer()->getPluginManager()->callEvent($ev = new PlayerGlassBottleEvent($player, $target, $this));
			if($ev->isCancelled()){
				return false;
			}else{
				if($this->count <= 1){
					$player->getInventory()->setItemInHand(Item::get(ItemIds::POTION));
					return true;
				}else{
					$this->pop();
					$player->getInventory()->setItemInHand($this);
				}
				if($player->getInventory()->canAddItem(Item::get(ItemIds::POTION)) === true){
					$player->getInventory()->AddItem(Item::get(ItemIds::POTION));
				}else{
					$motion = $player->getDirectionVector()->multiply(0.4);
					$position = clone $player->getPosition();
					$player->getLevel()->dropItem($position->add(0, 0.5), Item::get(ItemIds::POTION), $motion, 40);
				}
				return true;
			}
		}
		return false;
	}
}