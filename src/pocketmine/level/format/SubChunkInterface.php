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

namespace pocketmine\level\format;

interface SubChunkInterface{

	public function isEmpty(bool $checkLight = true) : bool;

	public function getBlockId(int $x, int $y, int $z) : int;

	public function setBlockId(int $x, int $y, int $z, int $id) : bool;

	public function getBlockData(int $x, int $y, int $z) : int;

	public function setBlockData(int $x, int $y, int $z, int $data) : bool;

	public function getFullBlock(int $x, int $y, int $z) : int;

	public function setBlock(int $x, int $y, int $z, ?int $id = null, ?int $data = null) : bool;

	public function getBlockLight(int $x, int $y, int $z) : int;

	public function setBlockLight(int $x, int $y, int $z, int $level) : bool;

	public function getBlockSkyLight(int $x, int $y, int $z) : int;

	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : bool;

	public function getHighestBlockAt(int $x, int $z) : int;

	public function getBlockIdColumn(int $x, int $z) : string;

	public function getBlockDataColumn(int $x, int $z) : string;

	public function getBlockLightColumn(int $x, int $z) : string;

	public function getSkyLightColumn(int $x, int $z) : string;

	public function getBlockIdArray() : string;

	public function getBlockDataArray() : string;

	public function getSkyLightArray() : string;

	/**
	 * @return void
	 */
	public function setBlockSkyLightArray(string $data);

	public function getBlockLightArray() : string;

	/**
	 * @return void
	 */
	public function setBlockLightArray(string $data);

	public function networkSerialize() : string;
}