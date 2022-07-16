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
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class Rabbit extends Animal {
	const NETWORK_ID = 18;

	const DATA_RABBIT_TYPE = 18;
	const DATA_JUMP_TYPE = 19;

	const TYPE_BROWN = 0;
	const TYPE_WHITE = 1;
	const TYPE_BLACK = 2;
	const TYPE_BLACK_WHITE = 3;
	const TYPE_GOLD = 4;
	const TYPE_SALT_PEPPER = 5;
	const TYPE_KILLER_BUNNY = 99;

	public $height = 0.5;
	public $width = 0.5;
	public $length = 0.5;

	public $dropExp = [1, 3];

	public function initEntity(){
		$this->setMaxHealth(3);
		parent::initEntity();
	}

	/**
	 * Rabbit constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->RabbitType)){
			$nbt->RabbitType = new ByteTag("RabbitType", $this->getRandomRabbitType());
		}
		parent::__construct($level, $nbt);

		$this->setDataProperty(self::DATA_RABBIT_TYPE, self::DATA_TYPE_BYTE, $this->getRabbitType());
	}

	/**
	 * @return int
	 */
	public function getRandomRabbitType() : int{
		$arr = [0, 1, 2, 3, 4, 5, 99];
		return $arr[mt_rand(0, count($arr) - 1)];
	}

	/**
	 * @param int $type
	 */
	public function setRabbitType(int $type){
		$this->namedtag->RabbitType = new ByteTag("RabbitType", $type);
	}

	/**
	 * @return int
	 */
	public function getRabbitType() : int{
		return (int) $this->namedtag["RabbitType"];
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Rabbit";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Rabbit::NETWORK_ID;
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
		$lootingL = 0;
		$cause = $this->lastDamageCause;
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$lootingL = $damager->getItemInHand()->getEnchantmentLevel(Enchantment::TYPE_WEAPON_LOOTING);
			}
		}
		$drops = [ItemItem::get(ItemIds::RABBIT_HIDE, 0, mt_rand(0, 1))];
		if($this->getLastDamageCause() === EntityDamageEvent::CAUSE_FIRE){
			$drops[] = ItemItem::get(ItemIds::COOKED_RABBIT, 0, mt_rand(0, 1));
		}else{
			$drops[] = ItemItem::get(ItemIds::RAW_RABBIT, 0, mt_rand(0, 1));
		}
		if(mt_rand(1, 200) <= (5 + 2 * $lootingL)){
			$drops[] = ItemItem::get(ItemIds::RABBIT_FOOT);
		}
		return $drops;
	}


}
