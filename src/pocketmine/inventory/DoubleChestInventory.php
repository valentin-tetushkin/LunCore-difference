<?php

/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;

class DoubleChestInventory extends ChestInventory implements InventoryHolder {
	/** @var ChestInventory */
	private $left;
	/** @var ChestInventory */
	private $right;

	/**
	 * DoubleChestInventory constructor.
	 *
	 * @param Chest $left
	 * @param Chest $right
     * @noinspection PhpMissingParentConstructorInspection
     */
	public function __construct(Chest $left, Chest $right){
        $this->left = $left->getRealInventory();
        $this->right = $right->getRealInventory();

        BaseInventory::__construct($this, InventoryType::get(InventoryType::DOUBLE_CHEST));
	}

	/**
	 * @return $this
	 */
	public function getInventory(){
		return $this;
	}

	/**
	 * @return Chest
	 */
	public function getHolder(){
		return $this->left->getHolder();
	}

	/**
	 * @param int $index
	 *
	 * @return Item
	 */
	public function getItem($index){
     if($this->left == null || $this->right == null) return Item::get(0);
		return $index < $this->left->getSize() ? $this->left->getItem($index) : $this->right->getItem($index - $this->left->getSize());
	}

    /**
     * @param int $index
     * @param Item $item
     *
     * @param bool $send
     * @return bool
     */
	public function setItem($index, Item $item, $send = true){
      if($this->left == null || $this->right == null) return Item::get(0);
		$old = $this->getItem($index);
		if($index < $this->left->getSize() ? $this->left->setItem($index, $item, $send) : $this->right->setItem($index - $this->left->getSize(), $item, $send)){
			$this->onSlotChange($index, $old, $send);
			return true;
		}
		return false;
	}

	public function getContents(bool $includeEmpty = false) : array{
		$result = $this->left->getContents($includeEmpty);
		$leftSize = $this->left->getSize();

		foreach($this->right->getContents($includeEmpty) as $i => $item){
			$result[$i + $leftSize] = $item;
		}

		return $result;
	}

    /**
     * @param Item[] $items
     * @param bool $send
     */
	public function setContents(array $items, $send = true){
		if(count($items) > $this->size){
			$items = array_slice($items, 0, $this->size, true);
		}

		for($i = 0; $i < $this->size; ++$i){
			if(!isset($items[$i])){
				if($i < $this->left->size){
					if(isset($this->left->slots[$i])){
						$this->clear($i);
					}
				}elseif(isset($this->right->slots[$i - $this->left->size])){
					$this->clear($i);
				}
			}elseif(!$this->setItem($i, $items[$i])){
				$this->clear($i);
			}
		}

		if($send){
			$this->sendContents($this->getViewers());
		}
	}

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who){
		parent::onOpen($who);

		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->right->getHolder()->getX();
			$pk->y = $this->right->getHolder()->getY();
			$pk->z = $this->right->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 2;
			if(($level = $this->right->getHolder()->getLevel()) instanceof Level){
				$level->addChunkPacket($this->right->getHolder()->getX() >> 4, $this->right->getHolder()->getZ() >> 4, $pk);
			}
		}
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who){
		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->right->getHolder()->getX();
			$pk->y = $this->right->getHolder()->getY();
			$pk->z = $this->right->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 0;
			if(($level = $this->right->getHolder()->getLevel()) instanceof Level){
				$level->addChunkPacket($this->right->getHolder()->getX() >> 4, $this->right->getHolder()->getZ() >> 4, $pk);
			}
		}
		parent::onClose($who);
	}

	/**
	 * @return ChestInventory
	 */
	public function getLeftSide(){
		return $this->left;
	}

	/**
	 * @return ChestInventory
	 */
	public function getRightSide(){
		return $this->right;
	}

	public function invalidate(){
		$this->left = null;
		$this->right = null;
	}
}