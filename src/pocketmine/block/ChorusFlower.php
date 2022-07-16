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

use pocketmine\Server;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\block\Block;
use pocketmine\block\EndStone;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\item\Tool;

class ChorusFlower extends Flowable {

	protected $id = self::CHORUS_FLOWER;

	/**
	 * ChorusFlower constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.4;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Chorus Flower";
	}

	/**
     *
	 * @return array
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(0);
		if($down->getId() === 240 or $down->getId() === 121 or $down->getId() === 200){
			$block0 = $this->getSide(2);
			$block1 = $this->getSide(3);
			$block2 = $this->getSide(4);
			$block3 = $this->getSide(5);
			if(!$block0->isSolid() and !$block1->isSolid() and !$block2->isSolid() and !$block3->isSolid()){
				$this->getLevel()->setBlock($this, $this, true);
				return true;
			}
		}

		return false;
	}
	public function getDrops(Item $item) : array{
		$drops = [];
		$drops[] = [BlockIds::CHORUS_FLOWER, 0, 1];
		return $drops;
	}
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_RANDOM){
			if(mt_rand(1, 2) == 2) return;
			$count = 0;
			for($i = 0; $i <= 15; $i++){
				if($this->getLevel()->getBlock(new Vector3($this->getX(), $this->getY() - $i, $this->getZ()))->getId() == 240) $count++;
			}
			if($count >= 10) return;
			if($this->getSide(Vector3::SIDE_DOWN)->getId() == 121 or $this->getSide(Vector3::SIDE_DOWN)->getId() == 240){
				Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this->getLevel()->getBlock($this), new ChorusFlower()));
				if($ev->isCancelled()) return;
				$rand = mt_rand(1, 3);
				for($i = 0; $i <= $rand; $i++){
					if($this->getLevel()->getBlock(new Vector3($this->getX(), $this->getY() + $i, $this->getZ()))->getId() !== 0 and $this->getLevel()->getBlock(new Vector3($this->getX(), $this->getY() + $i, $this->getZ()))->getId() !== 200 and $this->getLevel()->getBlock(new Vector3($this->getX(), $this->getY() + $i, $this->getZ()))->getId() !== 240) return;
					$this->getLevel()->setBlock(new Vector3($this->getX(), $this->getY() + $i, $this->getZ()), Block::get(240));
				}
				$this->getLevel()->setBlock(new Vector3($this->getX(), $this->getY() + $rand, $this->getZ()), Block::get(200));
			}else{
				$this->getLevel()->useBreakOn($this);
			}
			return Level::BLOCK_UPDATE_RANDOM;
		}

		return false;
	}
}