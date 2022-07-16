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

declare(strict_types=1);

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\level\Explosion;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\level\Particle;
use pocketmine\level\particle\HugeExplodeParticle;

class EnderCrystal extends Entity {

	const NETWORK_ID = 71;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;

	public function initEntity(){
		parent::initEntity();
		$this->setMaxHealth(2);
		$this->setHealth(2);
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return 'Ender Crystal';
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
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
	public function attack($damage, EntityDamageEvent $source){
		if(!$source->isCancelled()){
			$this->kill();
			$this->close();
			$explode = new Explosion($this->asPosition(), 3);

			$explode->explodeB();

		}
	}
}