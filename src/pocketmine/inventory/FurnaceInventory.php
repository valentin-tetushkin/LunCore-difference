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

namespace pocketmine\inventory;


use pocketmine\item\Item;
use pocketmine\tile\Furnace;

class FurnaceInventory extends ContainerInventory {

	const SMELTING = 0;
	const FUEL = 1;
	const RESULT = 2;

	/**
	 * FurnaceInventory constructor.
	 *
	 * @param Furnace $tile
	 */
	public function __construct(Furnace $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::FURNACE));
	}

	/**
	 * @return Furnace
	 */
	public function getHolder(){
		return $this->holder;
	}

	/**
	 * @return Item
	 */
	public function getResult(){
		return $this->getItem(self::RESULT);
	}

	/**
	 * @return Item
	 */
	public function getFuel(){
		return $this->getItem(self::FUEL);
	}

	/**
	 * @return Item
	 */
	public function getSmelting(){
		return $this->getItem(self::SMELTING);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setResult(Item $item){
		return $this->setItem(self::RESULT, $item);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setFuel(Item $item){
		return $this->setItem(self::FUEL, $item);
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setSmelting(Item $item){
		return $this->setItem(self::SMELTING, $item);
	}

	/**
	 * @param int  $index
	 * @param Item $before
	 * @param bool $send
	 */
	public function onSlotChange($index, $before, $send){
		parent::onSlotChange($index, $before, $send);

		$this->getHolder()->scheduleUpdate();
	}
}
