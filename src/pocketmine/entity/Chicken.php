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

namespace pocketmine\entity;

use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Chicken extends Animal {
	const NETWORK_ID = 10;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0;

	public $dropExp = [1, 3];

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Chicken";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Chicken::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}

	/**
	 * @return array
	 */
	public function getDrops(){
		$drops = [ItemItem::get(ItemIds::FEATHER, 0, mt_rand(0, 2))];
		$drops[] = ItemItem::get(ItemIds::RAW_CHICKEN, 0, mt_rand(0, 3));

		return $drops;
	}
}
