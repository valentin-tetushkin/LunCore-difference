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

namespace pocketmine\block;

use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerGlassBottleEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\item\Tool;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\GraySplashSound;
use pocketmine\level\sound\SpellSound;
use pocketmine\level\sound\SplashSound;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Cauldron as TileCauldron;
use pocketmine\tile\Tile;
use pocketmine\utils\Color;

class Cauldron extends Solid {

	protected $id = self::CAULDRON_BLOCK;

	/**
	 * Cauldron constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 2;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Cauldron";
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$nbt = new CompoundTag("", [
			new StringTag("id", Tile::CAULDRON),
			new IntTag("x", $block->x),
			new IntTag("y", $block->y),
			new IntTag("z", $block->z),
			new ShortTag("PotionId", 0xffff),
			new ByteTag("SplashPotion", 0),
			new ListTag("Items", [])
		]);

		if($item->hasCustomBlockData()){
			foreach($item->getCustomBlockData() as $key => $v){
				$nbt->{$key} = $v;
			}
		}

		Tile::createTile("Cauldron", $this->getLevel(), $nbt);

		$this->getLevel()->setBlock($block, $this, true, true);
		return true;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function onBreak(Item $item){
		$this->getLevel()->setBlock($this, new Air(), true);
		return true;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= 1){
			return [
				[ItemIds::CAULDRON, 0, 1]
			];
		}
		return [];
	}

	public function update(){//umm... right update method...?
		$this->getLevel()->setBlock($this, Block::get($this->id, $this->meta + 1), true);
		$this->getLevel()->setBlock($this, $this, true);//Undo the damage value
	}

	/**
	 * @return bool
	 */
	public function isEmpty(){
		return $this->meta === 0x00;
	}

	/**
	 * @return bool
	 */
	public function isFull(){
		return $this->meta === 0x06;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){//@author iTX. rewrite @Dog194
		$tile = $this->getLevel()->getTile($this);
		if(!($tile instanceof TileCauldron)){
			return false;
		}
		switch($item->getId()){
			case ItemIds::BUCKET:
				if($item->getDamage() === 0){//empty bucket
					if(!$this->isFull() or $tile->isCustomColor() or $tile->hasPotion()){
						break;
					}
					$bucket = clone $item;
					$bucket->setDamage(8);//water bucket
					Server::getInstance()->getPluginManager()->callEvent($ev = new PlayerBucketFillEvent($player, $this, 0, $item, $bucket));
					if(!$ev->isCancelled()){
						if($player->isSurvival()){
							$player->getInventory()->setItemInHand($ev->getItem());
						}
						$this->meta = 0;//empty
						$this->getLevel()->setBlock($this, $this, true);
						$tile->clearCustomColor();
						$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
					}
				}elseif($item->getDamage() === 8){//water bucket
					if($this->isFull() and !$tile->isCustomColor() and !$tile->hasPotion()){
						break;
					}
					$bucket = clone $item;
					$bucket->setDamage(0);//empty bucket
					Server::getInstance()->getPluginManager()->callEvent($ev = new PlayerBucketEmptyEvent($player, $this, 0, $item, $bucket));
					if(!$ev->isCancelled()){
						if($player->isSurvival()){
							$player->getInventory()->setItemInHand($ev->getItem());
						}
						if($tile->hasPotion()){//if has potion
							$this->meta = 0;//empty
							$tile->setPotionId(0xffff);//reset potion
							$tile->setSplashPotion(false);
							$tile->clearCustomColor();
							$this->getLevel()->setBlock($this, $this, true);
							$this->getLevel()->addSound(new ExplodeSound($this->add(0.5, 0, 0.5)));
						}else{
							$this->meta = 6;//fill
							$tile->clearCustomColor();
							$this->getLevel()->setBlock($this, $this, true);
							$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
						}
						$this->update();
					}
				}
				break;
			case ItemIds::DYE:
				if($tile->hasPotion()) break;
				$color = Color::getDyeColor($item->getDamage());
				if($tile->isCustomColor()){
					$color = Color::averageColor($color, $tile->getCustomColor());
				}
				if($player->isSurvival()){
					$item->pop();
					/*if($item->getCount() <= 0){
						$player->getInventory()->setItemInHand(Item::get(Item::AIR));
					}*/
				}
				$tile->setCustomColor($color);
				$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
				$this->update();
				break;
			case ItemIds::LEATHER_CAP:
			case ItemIds::LEATHER_TUNIC:
			case ItemIds::LEATHER_PANTS:
			case ItemIds::LEATHER_BOOTS:
				if($this->isEmpty()) break;
            --$this->meta;
            $this->getLevel()->setBlock($this, $this, true);
            $newItem = clone $item;
            /** @var Armor $newItem */
            if($tile->isCustomColor()){
                $newItem->setCustomColor($tile->getCustomColor());
					$player->getInventory()->setItemInHand($newItem);
					$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
					if($this->isEmpty()){
						$tile->clearCustomColor();
					}
				}else{
                $newItem->clearCustomColor();
					$player->getInventory()->setItemInHand($newItem);
					$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
				}
				break;
			case ItemIds::POTION:
			case ItemIds::SPLASH_POTION:
				if(!$this->isEmpty() and (($tile->getPotionId() !== $item->getDamage() and $item->getDamage() !== Potion::WATER_BOTTLE) or
						($item->getId() === ItemIds::POTION and $tile->getSplashPotion()) or
						($item->getId() === ItemIds::SPLASH_POTION and !$tile->getSplashPotion()) and $item->getDamage() !== 0 or
						($item->getDamage() === Potion::WATER_BOTTLE and $tile->hasPotion()))
				){//long...
					$this->meta = 0x00;
					$this->getLevel()->setBlock($this, $this, true);
					$tile->setPotionId(0xffff);//reset
					$tile->setSplashPotion(false);
					$tile->clearCustomColor();
					if($player->isSurvival()){
						$player->getInventory()->setItemInHand(Item::get(ItemIds::GLASS_BOTTLE));
					}
					$this->getLevel()->addSound(new ExplodeSound($this->add(0.5, 0, 0.5)));
				}elseif($item->getDamage() === Potion::WATER_BOTTLE){//水瓶 喷溅型水瓶
					$this->meta += 2;
					if($this->meta > 0x06) $this->meta = 0x06;
					$this->getLevel()->setBlock($this, $this, true);
					if($player->isSurvival()){
						$player->getInventory()->setItemInHand(Item::get(ItemIds::GLASS_BOTTLE));
					}
					$tile->setPotionId(0xffff);
					$tile->setSplashPotion(false);
					$tile->clearCustomColor();
					$this->getLevel()->addSound(new SplashSound($this->add(0.5, 1, 0.5)));
				}elseif(!$this->isFull()){
					$this->meta += 2;
					if($this->meta > 0x06) $this->meta = 0x06;
					$tile->setPotionId($item->getDamage());
					$tile->setSplashPotion($item->getId() === ItemIds::SPLASH_POTION);
					$tile->clearCustomColor();
					$this->getLevel()->setBlock($this, $this, true);
					if($player->isSurvival()){
						$player->getInventory()->setItemInHand(Item::get(ItemIds::GLASS_BOTTLE));
					}
					$color = Potion::getColor($item->getDamage());
				//	$this->getLevel()->addSound(new SpellSound($this->add(0.5, 1, 0.5), $color[0], $color[1], $color[2]));
				}
				break;
			case ItemIds::GLASS_BOTTLE:
				$player->getServer()->getPluginManager()->callEvent($ev = new PlayerGlassBottleEvent($player, $this, $item));
				if($ev->isCancelled()){
					return false;
				}
				if($this->meta < 2){
					break;
				}
                $this->meta -= 2;
                if($tile->hasPotion()){
                    if($tile->getSplashPotion() === true){
						$result = Item::get(ItemIds::SPLASH_POTION, $tile->getPotionId());
					}else{
						$result = Item::get(ItemIds::POTION, $tile->getPotionId());
					}
					if($this->isEmpty()){
						$tile->setPotionId(0xffff);//reset
						$tile->setSplashPotion(false);
						$tile->clearCustomColor();
					}
					$this->getLevel()->setBlock($this, $this, true);
					$this->addItem($item, $player, $result);
					$color = Potion::getColor($result->getDamage());
				//	$this->getLevel()->addSound(new SpellSound($this->add(0.5, 1, 0.5), $color[0], $color[1], $color[2]));
				}else{
                    $this->getLevel()->setBlock($this, $this, true);
					if($player->isSurvival()){
						$result = Item::get(ItemIds::POTION, Potion::WATER_BOTTLE);
						$this->addItem($item, $player, $result);
					}
					$this->getLevel()->addSound(new GraySplashSound($this->add(0.5, 1, 0.5)));
				}
				break;
		}
		return true;
	}

	/**
	 * @param Item   $item
	 * @param Player $player
	 * @param Item   $result
	 */
	public function addItem(Item $item, Player $player, Item $result){
		if($item->getCount() <= 1){
			$player->getInventory()->setItemInHand($result);
		}else{
			$item->pop();
			if($player->getInventory()->canAddItem($result) === true){
				$player->getInventory()->addItem($result);
			}else{
				$motion = $player->getDirectionVector()->multiply(0.4);
				$position = clone $player->getPosition();
				$player->getLevel()->dropItem($position->add(0, 0.5), $result, $motion, 40);
			}
		}
	}
}