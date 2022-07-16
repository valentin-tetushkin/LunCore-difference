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
 * Автор биома - http://vk.com/KlainYT
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
*/

namespace pocketmine\level\generator\normal\biome;


use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\generator\populator\Cactus;
use pocketmine\level\generator\populator\DeadBush;

class SandyBiome extends GrassyBiome {

	public function __construct(){
		parent::__construct();

		$cactus = new Cactus();
		$cactus->setBaseAmount(6);
		$deadBush = new DeadBush();
		$deadBush->setBaseAmount(2);

		$this->addPopulator($cactus);
		$this->addPopulator($deadBush);

        $this->setElevation(62, 65);

		$this->temperature = 0.05;
		$this->rainfall = 0.8;
		$this->setGroundCover([
			Block::get(BlockIds::SAND),
			Block::get(BlockIds::SAND),
			Block::get(BlockIds::SAND),
			Block::get(BlockIds::SAND),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
			Block::get(BlockIds::SANDSTONE),
		]);
	}

	public function getName() : string{
		return "Sandy";
	}
}
