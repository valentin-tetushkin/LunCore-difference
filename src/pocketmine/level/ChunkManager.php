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

declare(strict_types=1);

namespace pocketmine\level;

use pocketmine\level\format\Chunk;

interface ChunkManager {

	public function getBlockIdAt(int $x, int $y, int $z) : int;
	public function setBlockIdAt(int $x, int $y, int $z, int $id);
	public function getBlockDataAt(int $x, int $y, int $z) : int;
	public function setBlockDataAt(int $x, int $y, int $z, int $data);
	public function getBlockLightAt(int $x, int $y, int $z) : int;
	public function updateBlockLight(int $x, int $y, int $z);
	public function setBlockLightAt(int $x, int $y, int $z, int $level);
	public function getBlockSkyLightAt(int $x, int $y, int $z) : int;
	public function setBlockSkyLightAt(int $x, int $y, int $z, int $level);
	public function getChunk(int $chunkX, int $chunkZ);
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk = null);
	public function getSeed();
	public function getWorldHeight() : int;
	public function isInWorld(int $x, int $y, int $z) : bool;
}