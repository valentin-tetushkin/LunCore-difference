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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\entity\Arrow;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use pocketmine\utils\Random;

class TNT extends Solid implements ElectricalAppliance {

	protected $id = self::TNT;

	/**
	 * TNT constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "TNT";
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 0;
	}

	public function hasEntityCollision(){
		return true;
	}

	/**
	 * @return int
	 */
	public function getBurnChance() : int{
		return 15;
	}

	/**
	 * @return int
	 */
	public function getBurnAbility() : int{
		return 100;
	}

	/**
	 * @param Player|null $player
	 */
	public function prime(Player $player = null){
		$this->meta = 1;
		if($player != null and $player->isCreative()){
			$dropItem = false;
		}else{
			$dropItem = true;
		}
		$mot = (new Random())->nextSignedFloat() * M_PI * 2;
		$tnt = Entity::createEntity("PrimedTNT", $this->getLevel(), new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $this->x + 0.5),
				new DoubleTag("", $this->y),
				new DoubleTag("", $this->z + 0.5)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", -sin($mot) * 0.02),
				new DoubleTag("", 0.2),
				new DoubleTag("", -cos($mot) * 0.02)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", 0),
				new FloatTag("", 0)
			]),
			"Fuse" => new ShortTag("Fuse", 80)
		]), $dropItem, $player);

		$tnt->spawnToAll();
		$this->level->addSound(new TNTPrimeSound($this));
	}

	public function onEntityCollide(Entity $entity){
		if($entity instanceof Arrow and $entity->isOnFire()){
			$this->prime();
			$this->getLevel()->setBlock($this, new Air(), true);
		}
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($type == Level::BLOCK_UPDATE_SCHEDULED){
			$sides = [0, 1, 2, 3, 4, 5];
			foreach($sides as $side){
				$block = $this->getSide($side);
				if($block instanceof RedstoneSource and $block->isActivated($this)){
					$this->prime();
					$this->getLevel()->setBlock($this, new Air(), true);
					break;
				}
			}
			return Level::BLOCK_UPDATE_SCHEDULED;
		}
		return false;
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool|void
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->getLevel()->setBlock($this, $this, true, false);

		$this->getLevel()->scheduleUpdate($this, 40);
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if($item->getId() === ItemIds::FLINT_STEEL or $item->hasEnchantment(Enchantment::TYPE_WEAPON_FIRE_ASPECT)){
			$this->prime($player);
			$this->getLevel()->setBlock($this, new Air(), true);

			$item->useOn($this);

			return true;
		}

		return false;
	}
}