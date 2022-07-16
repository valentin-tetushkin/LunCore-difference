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


namespace pocketmine\item;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\Color;

abstract class Armor extends Item {
	const TIER_LEATHER = 1;
	const TIER_GOLD = 2;
	const TIER_CHAIN = 3;
	const TIER_IRON = 4;
	const TIER_DIAMOND = 5;

	const TYPE_HELMET = 0;
	const TYPE_CHESTPLATE = 1;
	const TYPE_LEGGINGS = 2;
	const TYPE_BOOTS = 3;

	/**
	 * @return int
	 */
	public function getMaxStackSize() : int{
		return 1;
	}

	/**
	 * @return bool
	 */
	public function isArmor(){
		return true;
	}

	/**
	 *
	 * @param Item $object
	 * @param int  $cost
	 *
	 * @return bool
	 */
	public function useOn($object, int $cost = 1){
		if($this->isUnbreakable()){
			return true;
		}
		$unbreakings = [
			0 => 100,
			1 => 80,
			2 => 73,
			3 => 70
		];
		$unbreakingl = $this->getEnchantmentLevel(Enchantment::TYPE_MINING_DURABILITY);
		if(mt_rand(1, 100) > $unbreakings[$unbreakingl]){
			return true;
		}
		$this->setDamage($this->getDamage() + $cost);
		if($this->getDamage() >= $this->getMaxDurability()){
			$this->setCount(0);
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function isUnbreakable(){
		$tag = $this->getNamedTagEntry("Unbreakable");
		return $tag !== null and $tag->getValue() > 0;
	}

	/**
	 * @param Color $color
	 */
	public function setCustomColor(Color $color){
		if(($hasTag = $this->hasCompoundTag())){
			$tag = $this->getNamedTag();
		}else{
			$tag = new CompoundTag("", []);
		}

		$tag->customColor = new IntTag("customColor", $color->getColorCode());
		$this->setCompoundTag($tag);
	}

	/**
	 * @return mixed|null
	 */
	public function getCustomColor(){
		if(!$this->hasCompoundTag()) return null;
		$tag = $this->getNamedTag();
		if(isset($tag->customColor)){
			return $tag["customColor"];
		}
		return null;
	}

	public function clearCustomColor(){
		if(!$this->hasCompoundTag()) return;
		$tag = $this->getNamedTag();
		if(isset($tag->customColor)){
			unset($tag->customColor);
		}
		$this->setCompoundTag($tag);
	}

	/**
	 * @return bool
	 */
	public function getArmorTier(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function getArmorType(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function getMaxDurability(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function getArmorValue(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function isHelmet(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function isChestplate(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function isLeggings(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function isBoots(){
		return false;
	}
}