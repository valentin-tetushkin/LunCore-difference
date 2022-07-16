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

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\Player;

class BaseTransaction implements Transaction {

	protected $inventory;

	protected $slot;

	protected $targetItem;

	protected $creationTime;
	
	protected $transactionType = Transaction::TYPE_NORMAL;
	
	protected $failures = 0;
	
	protected $wasSuccessful = false;
	
	protected $achievements = [];

	public function __construct($inventory, $slot, Item $targetItem, $achievements = [], $transactionType = Transaction::TYPE_NORMAL){
		$this->inventory = $inventory;
		$this->slot = (int) $slot;
		$this->targetItem = clone $targetItem;
		$this->creationTime = microtime(true);
		$this->transactionType = $transactionType;
		$this->achievements = $achievements;
	}


	public function getCreationTime(){
		return $this->creationTime;
	}
	
	public function getInventory(){
		return $this->inventory;
	}

	public function getSlot(){
		return $this->slot;
	}

	public function getTargetItem(){
		return clone $this->targetItem;
	}
	
	public function getSourceItem(){
		return clone $this->inventory->getItem($this->slot);
	}

	public function setTargetItem(Item $item){
		$this->targetItem = clone $item;
	}

	public function getFailures(){
		return $this->failures;
	}

	public function addFailure(){
		++$this->failures;
	}

	public function succeeded(){
		return $this->wasSuccessful;
	}

	public function setSuccess($value = true){
		$this->wasSuccessful = $value;
	}

	public function getTransactionType(){
		return $this->transactionType;
	}

	public function getAchievements(){
		return $this->achievements;
	}

	public function hasAchievements(){
		return count($this->achievements) !== 0;
	}

	public function addAchievement(string $achievementName){
		$this->achievements[] = $achievementName;
	}

	public function sendSlotUpdate(Player $source){
		if($this->getInventory() instanceof TemporaryInventory) return true;

		if($this->wasSuccessful){
			$targets = $this->getInventory()->getViewers();
			unset($targets[spl_object_hash($source)]);
		}else{
			$targets = [$source];
		}
		$this->inventory->sendSlot($this->slot, $targets);
	}

	public function getChange(){
		$sourceItem = $this->getInventory()->getItem($this->slot);

		if($sourceItem->equals($this->targetItem, true, true, true)){
			return null;

		}elseif($sourceItem->equals($this->targetItem)){
			$item = clone $sourceItem;
			$countDiff = $this->targetItem->getCount() - $sourceItem->getCount();
			$item->setCount(abs($countDiff));

			if($countDiff < 0){
				return ['in' => null,
					'out' => $item];
			}elseif($countDiff > 0){
				return [
					'in' => $item,
					'out' => null];
			}else{
				return null;
			}
			
		}elseif($sourceItem->getId() !== BlockIds::AIR and $this->targetItem->getId() === BlockIds::AIR){
			return ['in' => null,
				'out' => clone $sourceItem];

		}elseif($sourceItem->getId() === BlockIds::AIR and $this->targetItem->getId() !== BlockIds::AIR){
			return ['in' => $this->getTargetItem(),
				'out' => null];

		}else{
			return ['in' => $this->getTargetItem(),
				'out' => clone $sourceItem];
		}
	}
	
	public function execute(Player $source) : bool{
		if($this->getInventory()->processSlotChange($this)){
			if(!$source->getServer()->allowInventoryCheats and !$source->isCreative()){
				$change = $this->getChange();
				if($change === null) return true;
				if($change['out'] instanceof Item){
					if(!$this->getInventory()->slotContains($this->getSlot(), $change['out'])){
						return false;
					}
				}
				if($change['in'] instanceof Item){
					if(!$source->getFloatingInventory()->contains($change['in'])){
						return false;
					}
				}
				if($change['out'] instanceof Item) $source->getFloatingInventory()->addItem($change['out']);
				if($change['in'] instanceof Item) $source->getFloatingInventory()->removeItem($change['in']);
			}
			$this->getInventory()->setItem($this->getSlot(), $this->getTargetItem(), false);
		}
		
		foreach($this->achievements as $achievement){
			$source->awardAchievement($achievement);
		}
		if($this->getInventory() instanceof ShulkerBoxInventory) $this->getInventory()->getHolder()->saveNBT();
		return true;
	}
}
