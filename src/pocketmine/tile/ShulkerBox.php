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

namespace pocketmine\tile;

use pocketmine\block\BlockIds;
use pocketmine\inventory\ShulkerBoxInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

class ShulkerBox extends Spawnable implements InventoryHolder, Container, Nameable{

    /** @var ShulkerBoxInventory */
    protected $inventory;

    /**
     * ShulkerBox constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     */
    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        $this->inventory = new ShulkerBoxInventory($this);

        if(!isset($this->namedtag->Items) or !($this->namedtag->Items instanceof ListTag)){
            $this->namedtag->Items = new ListTag("Items", []);
            $this->namedtag->Items->setTagType(NBT::TAG_Compound);
        }

        for($i = 0; $i < $this->getSize(); ++$i){
            $this->inventory->setItem($i, $this->getItem($i));
        }

        if(!isset($this->namedtag->facing)) $this->namedtag->facing = new ByteTag("facing", 1);
    }

    /**
     * @param CompoundTag $nbt
     */
    public function addAdditionalSpawnData(CompoundTag $nbt){
        if($this->hasName()) $nbt->CustomName = $this->namedtag->CustomName;
    }

    public function close(){
        if(!$this->closed){
            $this->inventory->removeAllViewers();

            parent::close();
        }
    }

    public function saveNBT(){
    	parent::saveNBT();
        $this->namedtag->Items->setValue([]);
        $this->namedtag->Items->setTagType(NBT::TAG_Compound);
        for ($index = 0; $index < $this->getSize(); ++$index) {
            $this->setItem($index, $this->inventory->getItem($index));
        }
    }

    /**
     * @return int
     */
    public function getSize(){
        return 27;
    }

    /**
     * @return ShulkerBoxInventory
     */
    public function getRealInventory(){
        return $this->inventory;
    }

    /**
     * @return ShulkerBoxInventory
     */
    public function getInventory(){
        return $this->inventory;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return isset($this->namedtag->CustomName) ? $this->namedtag->CustomName->getValue() : "ShulkerBox";
    }

    /**
     * @return bool
     */
    public function hasName() : bool{
        return isset($this->namedtag->CustomName);
    }

    /**
     * @param void $str
     */
    public function setName($str){
        if($str === ""){
            unset($this->namedtag->CustomName);
            return;
        }

        $this->namedtag->CustomName = new StringTag("CustomName", $str);
    }

    /**
     * @param int $index
     * @return int
     */
    protected function getSlotIndex(int $index){
        foreach($this->namedtag->Items as $i => $slot){
            if($slot->Slot->getValue() === $index) return (int) $i;
        }

        return -1;
    }

    /**
     * @param int $index
     * @return Item
     */
    public function getItem($index) : Item{
        $i = $this->getSlotIndex($index);
        if($i < 0) return Item::get(BlockIds::AIR, 0, 0);
        else return Item::nbtDeserialize($this->namedtag->Items[$i]);
    }

    /**
     * @param int $index
     * @param Item $item
     */
    public function setItem($index, Item $item){
        $i = $this->getSlotIndex($index);

        $d = $item->nbtSerialize($index);

        if($item->getId() === BlockIds::AIR or $item->getCount() <= 0){
            if($i >= 0) unset($this->namedtag->Items[$i]);
        }elseif($i < 0){
            for($i = 0; $i <= $this->getSize(); ++$i){
                if(!isset($this->namedtag->Items[$i])) break;
            }
            $this->namedtag->Items[$i] = $d;
        }else{
            $this->namedtag->Items[$i] = $d;
        }
    }

    public function getSpawnCompound(){
        $nbt = new CompoundTag("", [
            new StringTag("id", Tile::SHULKER_BOX),
            $this->namedtag->facing,
            new IntTag("x", $this->x),
            new IntTag("y", $this->y),
            new IntTag("z", $this->z)
        ]);

        $tile = $this->getLevel()->getTile($this->asVector3());
        if ($tile instanceof ShulkerBox) $nbt->Items = $tile->namedtag->Items;

        if($this->hasName()) $nbt->CustomName = $this->namedtag->CustomName;
        return $nbt;
    }
}