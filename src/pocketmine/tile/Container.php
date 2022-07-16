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

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

interface Container{

	/**
	 * @param int $index
	 *
	 * @return Item
	 */
	public function getItem($index);

	/**
	 * @param int  $index
	 * @param Item $item
	 */
	public function setItem($index, Item $item);

	/**
	 * @return int
	 */
	public function getSize();

	/**
	 * @return Inventory
	 */
	public function getInventory();
}
