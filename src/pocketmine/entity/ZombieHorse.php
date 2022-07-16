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

use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemIds;

class ZombieHorse extends Animal {
	const NETWORK_ID = 27;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 0;

	public $dropExp = [5, 5];

	/**
	 * @return string
	 */
	public function getName(){
		return "ZombieHorse";
	}

	public function initEntity(){
		$this->setMaxHealth(20);
		parent::initEntity();
	}

    public function getDrops(){
        $lootingL = 0;
        $cause = $this->lastDamageCause;
        if($cause instanceof EntityDamageByEntityEvent and $cause->getDamager() instanceof Player){
            $lootingL = $cause->getDamager()->getItemInHand()->getEnchantmentLevel(Enchantment::TYPE_WEAPON_LOOTING);
        }
        $drops[] = ItemItem::get(ItemIds::ROTTEN_FLESH, 0, mt_rand(0, 3 + $lootingL));
        return $drops;
    }

	/**
	 * @param Player $player
	 */

	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = ZombieHorse::NETWORK_ID;
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
}
