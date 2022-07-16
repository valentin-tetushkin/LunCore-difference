<?php

namespace pocketmine\entity;

use pocketmine\level\Level;
use pocketmine\nbt\tag\{ByteTag, CompoundTag};
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Bat extends FlyingAnimal {

	const NETWORK_ID = 19;

	const DATA_IS_RESTING = 16;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	public $flySpeed = 0.8;
	public $switchDirectionTicks = 100;

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Bat";
	}

	public function initEntity(){
		$this->setMaxHealth(6);
		parent::initEntity();
	}

	/**
	 * Bat constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->isResting)){
			$nbt->isResting = new ByteTag("isResting", 0);
		}
		parent::__construct($level, $nbt);

		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RESTING, $this->isResting());
	}

	/**
	 * @return int
	 */
	public function isResting() : int{
		return (int) $this->namedtag["isResting"];
	}

	/**
	 * @param bool $resting
	 */
	public function setResting(bool $resting){
		$this->namedtag->isResting = new ByteTag("isResting", $resting ? 1 : 0);
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate($currentTick){
		if($this->age > 20 * 60 * 10){
			$this->kill();
		}
		return parent::onUpdate($currentTick);
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Bat::NETWORK_ID;
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