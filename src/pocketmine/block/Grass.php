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

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Tool;
use pocketmine\level\generator\object\TallGrass as TallGrassObject;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Random;

class Grass extends Solid {

	protected $id = self::GRASS;

	/**
	 * Grass constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Grass";
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.6;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_SHOVEL;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->getEnchantmentLevel(Enchantment::TYPE_MINING_SILK_TOUCH) > 0){
			return [
				[BlockIds::GRASS, 0, 1],
			];
		}else{
			return [
				[BlockIds::DIRT, 0, 1],
			];
		}
	}

	/**
	 * @param int $type
	 */
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_RANDOM){
			$lightAbove = $this->level->getFullLightAt($this->x, $this->y + 1, $this->z);
			if($lightAbove < 4 and Block::$lightFilter[$this->level->getBlockIdAt($this->x, $this->y + 1, $this->z)] >= 3){ //2 plus 1 standard filter amount
				//grass dies
				$this->level->getServer()->getPluginManager()->callEvent($ev = new BlockSpreadEvent($this, $this, Block::get(BlockIds::DIRT)));
				if(!$ev->isCancelled()){
					$this->level->setBlock($this, $ev->getNewState(), false, false);
				}

				return Level::BLOCK_UPDATE_RANDOM;
			}elseif($lightAbove >= 9){
				//try grass spread
				for($i = 0; $i < 4; ++$i){
					$x = mt_rand($this->x - 1, $this->x + 1);
					$y = mt_rand($this->y - 3, $this->y + 1);
					$z = mt_rand($this->z - 1, $this->z + 1);
					if(
						$this->level->getBlockIdAt($x, $y, $z) !== BlockIds::DIRT or
						$this->level->getFullLightAt($x, $y + 1, $z) < 4 or
						Block::$lightFilter[$this->level->getBlockIdAt($x, $y + 1, $z)] >= 3
					){
						continue;
					}

					$this->level->getServer()->getPluginManager()->callEvent($ev = new BlockSpreadEvent($b = $this->level->getBlockAt($x, $y, $z), $this, Block::get(BlockIds::GRASS)));
					if(!$ev->isCancelled()){
						$this->level->setBlock($b, $ev->getNewState(), false, false);
					}
				}

				return Level::BLOCK_UPDATE_RANDOM;
			}
		}

		return false;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if($item->getId() === ItemIds::DYE and $item->getDamage() === 0x0F){
            $item->pop();
			TallGrassObject::growGrass($this->getLevel(), $this, new Random(mt_rand()), 8, 2);

			return true;
		}elseif($item->isHoe()){
			$item->useOn($this);
			$this->getLevel()->setBlock($this, new Farmland());

			return true;
		}elseif($item->isShovel() and $this->getSide(1)->getId() === BlockIds::AIR){
			$item->useOn($this);
			$this->getLevel()->setBlock($this, new GrassPath());

			return true;
		}

		return false;
	}
}
