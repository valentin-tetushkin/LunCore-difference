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

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Slime extends Living {
	const NETWORK_ID = 37;

	const DATA_SLIME_SIZE = 16;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 5;

	public $dropExp = [1, 4];

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Slime";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Slime::NETWORK_ID;
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
		$drops = [ItemItem::get(ItemIds::SLIMEBALL)];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getEntity() instanceof Player){
			if(\mt_rand(0, 199) < 5){
				switch(\mt_rand(0, 2)){
					case 0:
						$drops[] = ItemItem::get(ItemIds::IRON_INGOT);
						break;
					case 1:
						$drops[] = ItemItem::get(ItemIds::CARROT);
						break;
					case 2:
						$drops[] = ItemItem::get(ItemIds::POTATO);
						break;
				}
			}
		}
		return $drops;
	}
}