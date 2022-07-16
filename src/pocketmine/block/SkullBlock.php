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

use pocketmine\item\Item;

use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Skull as SkullTile;

use pocketmine\tile\Tile;

class SkullBlock extends Flowable {

	protected $id = self::SKULL_BLOCK;

	/**
	 * SkullBlock constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 1;
	}

	/**
	 * @return bool
	 */
	public function getName() : bool{
		return "Mob Head";
	}

	/**
	 * @return AxisAlignedBB
	 */
	protected function recalculateBoundingBox(){
		//TODO: different bounds depending on attached face (meta)
		return new AxisAlignedBB(
			$this->x + 0.25,
			$this->y,
			$this->z + 0.25,
			$this->x + 0.75,
			$this->y + 0.5,
			$this->z + 0.75
		);
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
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($face !== 0){
			$this->meta = $face;
			$rot = 0;
			if($face === Vector3::SIDE_UP and $player !== null){
				$rot = floor(($player->yaw * 16 / 360) + 0.5) & 0x0F;
			}
			$this->getLevel()->setBlock($block, $this, true);
			$moveMouth = false;
			if($item->getDamage() === SkullTile::TYPE_DRAGON){
				if(in_array($target->getId(), [BlockIds::REDSTONE_TORCH, BlockIds::REDSTONE_BLOCK])) $moveMouth = true; //Temp-hacking Dragon Head Mouth Move
			}
			$nbt = new CompoundTag("", [
				new StringTag("id", Tile::SKULL),
				new ByteTag("SkullType", $item->getDamage()),
				new ByteTag("Rot", $rot),
				new ByteTag("MouthMoving", $moveMouth),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			if($item->hasCustomName()){
				$nbt->CustomName = new StringTag("CustomName", $item->getCustomName());
			}
			Tile::createTile("Skull", $this->getLevel(), $nbt);
			return true;
		}
		return false;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		$tile = $this->level->getTile($this);
		if($tile instanceof SkullTile){
			return [
				[ItemIds::MOB_HEAD, $tile->getType(), 1]
			];
		}
		return [];
	}
}
