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

namespace pocketmine\level\generator\biome;

use pocketmine\level\generator\noise\Simplex;
use pocketmine\utils\Random;

class BiomeSelector {

	private $fallback;

	private $temperature;

	private $rainfall;

	private $biomes = [];

	private $map = [];

	private $lookup;

	public function __construct(Random $random, callable $lookup, Biome $fallback){ // Шум для биомов
		$this->fallback = $fallback;
		$this->lookup = $lookup;
		$this->temperature = new Simplex($random, 7, 1 / 64, 1 / 1612); // шум для биомов 1612
		$this->rainfall = new Simplex($random, 7, 1 / 64, 1 / 1612); // шум для биомов 1612
	}

	public function recalculate(){
		$this->map = new \SplFixedArray(64 * 64);

		for($i = 0; $i < 64; ++$i){
			for($j = 0; $j < 64; ++$j){
				$this->map[$i + ($j << 6)] = call_user_func($this->lookup, $i / 63, $j / 63);
			}
		}
	}

	public function addBiome(Biome $biome){
		$this->biomes[$biome->getId()] = $biome;
	}

	public function getTemperature($x, $z){
		return ($this->temperature->noise2D($x, $z, true) + 1) / 2;
	}

	public function getRainfall($x, $z){
		return ($this->rainfall->noise2D($x, $z, true) + 1) / 2;
	}

	/**
	 * @param $x
	 * @param $z
	 *
	 * @return Biome
	 */
	public function pickBiome($x, $z){
		$temperature = (int) ($this->getTemperature($x, $z) * 63);
		$rainfall = (int) ($this->getRainfall($x, $z) * 63);

		$biomeId = $this->map[$temperature + ($rainfall << 6)];
		return $this->biomes[$biomeId] ?? $this->fallback;
	}
}