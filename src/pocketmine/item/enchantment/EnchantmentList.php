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

namespace pocketmine\item\enchantment;


class EnchantmentList {

	/** @var EnchantmentEntry[] */
	private $enchantments;

	/**
	 * EnchantmentList constructor.
	 *
	 * @param $size
	 */
	public function __construct($size){
		$this->enchantments = new \SplFixedArray($size);
	}

	/**
	 * @param                  $slot
	 * @param EnchantmentEntry $entry
	 */
	public function setSlot($slot, EnchantmentEntry $entry){
		$this->enchantments[$slot] = $entry;
	}

	/**
	 * @param $slot
	 *
	 * @return EnchantmentEntry
	 */
	public function getSlot($slot){
		return $this->enchantments[$slot];
	}

	/**
	 * @return int
	 */
	public function getSize(){
		return $this->enchantments->getSize();
	}

}