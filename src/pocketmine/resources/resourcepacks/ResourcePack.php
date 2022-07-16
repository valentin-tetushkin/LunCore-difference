<?php
#╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
#║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
#║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
#║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
#║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
#╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝

namespace pocketmine\resources\resourcepacks;


interface ResourcePack{

	public function getPath() : string;

	/**
	 * @return string
	 */
	public function getPackName() : string;

	/**
	 * @return string
	 */
	public function getPackId() : string;

	/**
	 * @return int
	 */
	public function getPackSize() : int;

	/**
	 * @return string
	 */
	public function getPackVersion() : string;

	/**
	 * @return string
	 */
	public function getSha256() : string;

	public function getPackChunk(int $start, int $length) : string;
}