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

namespace pocketmine\level\generator;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class VoidGenerator extends Generator {
	/** @var ChunkManager */
	private $level;
    /** @var Random */
	private $random;
	private $options;
	/** @var Chunk */
	private $emptyChunk = null;

	/**
	 * @return array
	 */
	public function getSettings(){
		return [];
	}

	/**
	 * @return string
	 */
	public function getName(){
		return "Void";
	}

	/**
	 * Void constructor.
	 *
	 * @param array $settings
	 */
	public function __construct(array $settings = []){
		$this->options = $settings;
	}

	/**
	 * @param ChunkManager $level
	 * @param Random       $random
	 *
	 * @return mixed|void
	 */
	public function init(ChunkManager $level, Random $random){
		$this->level = $level;
		$this->random = $random;
	}

	/**
	 * @param $chunkX
	 * @param $chunkZ
	 *
	 * @return mixed|void
	 */
	public function generateChunk($chunkX, $chunkZ){
		if($this->emptyChunk === null){
			$chunk1 = clone $this->level->getChunk($chunkX, $chunkZ);
			$chunk1->setGenerated();

			for($Z = 0; $Z < 16; ++$Z){
				for($X = 0; $X < 16; ++$X){
					$chunk1->setBiomeId($X, $Z, 1);
					for($y = 0; $y < 128; ++$y){
						$chunk1->setBlockId($X, $y, $Z, BlockIds::AIR);
					}
				}
			}

			$spawn = $this->getSpawn();
			if($spawn->getX() >> 4 === $chunkX and $spawn->getZ() >> 4 === $chunkZ){
				$chunk1->setBlockId(0, 64, 0, BlockIds::GRASS);
			}else{
				$this->emptyChunk = clone $chunk1;
			}
		}else{
			$chunk1 = clone $this->emptyChunk;
		}

		$chunk = clone $chunk1;
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	/**
	 * @param $chunkX
	 * @param $chunkZ
	 *
	 * @return mixed|void
	 */
	public function populateChunk($chunkX, $chunkZ){

	}

	/**
	 * @return Vector3
	 */
	public function getSpawn(){
		return new Vector3(128, 72, 128);
	}

}
