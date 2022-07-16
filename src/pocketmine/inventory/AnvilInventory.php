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
use pocketmine\item\EnchantedBook;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\inventory\AnvilProcessEvent;

class AnvilInventory extends TemporaryInventory {

	const TARGET = 0;
	const SACRIFICE = 1;
	const RESULT = 2;


	/**
	 * AnvilInventory constructor.
	 *
	 * @param Position $pos
	 */
	public function __construct(Position $pos){
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::ANVIL));
	}

	/**
	 * @return FakeBlockMenu|InventoryHolder
	 */
	public function getHolder(){
		return $this->holder;
	}

	/**
	 * @return int
	 */
	public function getResultSlotIndex(){
		return self::RESULT;
	}

	/**
	 * @param Player $player
	 * @param Item   $resultItem
	 *
	 * @return bool
	 */
	public function onRename(Player $player, Item $resultItem) : bool{
		if(!$resultItem->equals($this->getItem(self::TARGET), true, false, true)){
			//Item does not match target item. Everything must match except the tags.
			return false;
		}

		if($player->getXpLevel() < $resultItem->getRepairCost()){ //Not enough exp
			return false;
		}
		$player->takeXpLevel($resultItem->getRepairCost());

		$this->clearAll();
		if(!$player->getServer()->allowInventoryCheats and !$player->isCreative()){
			if(!$player->getFloatingInventory()->canAddItem($resultItem)){
				return false;
			}
			$player->getFloatingInventory()->addItem($resultItem);
		}
		return true;
	}

	/**
	 * @param Player $player
	 * @param Item   $target
	 * @param Item   $sacrifice
	 *
	 * @return bool
	 */
	public function process(Player $player, Item $target, Item $sacrifice){
		$resultItem = clone $target;
		Server::getInstance()->getPluginManager()->callEvent($ev = new AnvilProcessEvent($this));
		if($ev->isCancelled()){
			$this->clearAll();
			return false;
		}
		if($sacrifice instanceof EnchantedBook && $sacrifice->hasEnchantments()){ //Enchanted Books!
			foreach($sacrifice->getEnchantments() as $enchant){
				$resultItem->addEnchantment($enchant);
			}

			if($player->getXpLevel() < $resultItem->getRepairCost()){ //Not enough exp
				return false;
			}
			$player->takeXpLevel($resultItem->getRepairCost());

			$this->clearAll();
			if(!$player->getServer()->allowInventoryCheats and !$player->isCreative()){
				if(!$player->getFloatingInventory()->canAddItem($resultItem)){
					return false;
				}
				$player->getFloatingInventory()->addItem($resultItem);
			}
		}
	}

	/**
	 * @param Transaction $transaction
	 *
	 * @return bool
	 */
	public function processSlotChange(Transaction $transaction) : bool{
		if($transaction->getSlot() === $this->getResultSlotIndex()){
			return false;
		}
		return true;
	}

	/**
	 * @param int  $index
	 * @param Item $before
	 * @param bool $send
	 */
	public function onSlotChange($index, $before, $send){
		//Do not send anvil slot updates to anyone. This will cause a client crash.
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who){
		parent::onClose($who);

		$this->dropContents($this->holder->getLevel(), $this->holder->add(0.5, 0.5, 0.5));
	}

}
