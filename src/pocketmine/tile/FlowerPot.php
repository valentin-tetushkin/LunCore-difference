<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * @author LunCore team
 * @link http://vk.com/luncore
 * @creator vk.com/klainyt
 *
*/

namespace pocketmine\tile;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;

class FlowerPot extends Spawnable {

	/**
	 * FlowerPot constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!isset($nbt->item)){
			$nbt->item = new ShortTag("item", 0);
		}
		if(!isset($nbt->mData)){
			$nbt->mData = new IntTag("mData", 0);
		}
		parent::__construct($level, $nbt);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function canAddItem(Item $item) : bool{
		if(!$this->isEmpty()){
			return false;
		}
		switch($item->getId()){
			/** @noinspection PhpMissingBreakStatementInspection */
			case BlockIds::TALL_GRASS:
				if($item->getDamage() === 1){
					return false;
				}
			case BlockIds::SAPLING:
			case BlockIds::DEAD_BUSH:
			case BlockIds::DANDELION:
			case BlockIds::RED_FLOWER:
			case BlockIds::BROWN_MUSHROOM:
			case BlockIds::RED_MUSHROOM:
			case BlockIds::CACTUS:
				return true;
			default:
				return false;
		}
	}

	/**
	 * @return Item
	 */
	public function getItem() : Item{
		return Item::get($this->namedtag["item"] ?? 0, $this->namedtag["mData"] ?? 0);
	}

	/**
	 * @param Item $item
	 */
	public function setItem(Item $item){
		$this->namedtag["item"] = $item->getId();
		$this->namedtag["mData"] = $item->getDamage();
		$this->onChanged();
	}

	public function removeItem(){
		$this->setItem(Item::get(BlockIds::AIR));
	}

	/**
	 * @return bool
	 */
	public function isEmpty() : bool{
		return $this->getItem()->getId() === BlockIds::AIR;
	}

	/**
	 * @return CompoundTag
	 */
	public function getSpawnCompound() : CompoundTag{
		return new CompoundTag("", [
			new StringTag("id", Tile::FLOWER_POT),
			new IntTag("x", $this->x),
			new IntTag("y", $this->y),
			new IntTag("z", $this->z),
			$this->namedtag->item,
			$this->namedtag->mData
		]);
	}
}