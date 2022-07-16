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
use pocketmine\Player;

interface Transaction {

	//Transaction type constants
	const TYPE_NORMAL = 0;
	const TYPE_DROP_ITEM = 1;

	/**
	 * @return Inventory
	 */
	public function getInventory();

	/**
	 * @return int
	 */
	public function getSlot();

	/**
	 * @return Item
	 */
	public function getTargetItem();
	
	/**
	 * @return Item
	 */
	public function getSourceItem();

	/**
	 * @return float
	 */
	public function getCreationTime();

	/**
	 * @param Player $source
	 *
	 * @return bool
	 */
	public function execute(Player $source) : bool;
}
