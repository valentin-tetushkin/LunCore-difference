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

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class TallGrass {
	/**
	 * @param ChunkManager $level
	 * @param Vector3      $pos
	 * @param Random       $random
	 * @param int          $count
	 * @param int          $radius
	 */
	public static function growGrass(ChunkManager $level, Vector3 $pos, Random $random, $count = 15, $radius = 10){
		$arr = [
			[BlockIds::DANDELION, 0],
			[BlockIds::POPPY, 0],
			[BlockIds::TALL_GRASS, 1],
			[BlockIds::TALL_GRASS, 1],
			[BlockIds::TALL_GRASS, 1],
			[BlockIds::TALL_GRASS, 1]
		];
		$arrC = count($arr) - 1;
		for($c = 0; $c < $count; ++$c){
			$x = $random->nextRange($pos->x - $radius, $pos->x + $radius);
			$z = $random->nextRange($pos->z - $radius, $pos->z + $radius);
			if($level->getBlockIdAt($x, $pos->y + 1, $z) === BlockIds::AIR and $level->getBlockIdAt($x, $pos->y, $z) === BlockIds::GRASS){
				$t = $arr[$random->nextRange(0, $arrC)];
				$level->setBlockIdAt($x, $pos->y + 1, $z, $t[0]);
				$level->setBlockDataAt($x, $pos->y + 1, $z, $t[1]);
			}
		}
	}
}