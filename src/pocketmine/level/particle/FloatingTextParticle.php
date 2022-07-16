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

namespace pocketmine\level\particle;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\utils\UUID;

class FloatingTextParticle extends Particle {
	//TODO: HACK!

	protected $text;
	protected $title;
	protected $entityId;
	protected $invisible = false;

	/**
	 * @param Vector3 $pos
	 * @param string  $text
	 * @param string  $title
	 */
	public function __construct(Vector3 $pos, $text, $title = ""){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->text = $text;
		$this->title = $title;
	}

	/**
	 * @return int
	 */
	public function getText(){
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function getTitle(){
		return $this->title;
	}

	/**
	 * @param $text
	 */
	public function setText($text){
		$this->text = $text;
	}

	/**
	 * @param $title
	 */
	public function setTitle($title){
		$this->title = $title;
	}

	/**
	 * @return bool
	 */
	public function isInvisible(){
		return $this->invisible;
	}

	/**
	 * @param bool $value
	 */
	public function setInvisible($value = true){
		$this->invisible = (bool) $value;
	}

	/**
	 * @return array
	 */
	public function encode(){
		$p = [];

		if($this->entityId === null){
			$this->entityId = Entity::$entityCount++;
		}else{
			$pk0 = new RemoveEntityPacket();
			$pk0->eid = $this->entityId;

			$p[] = $pk0;
		}

		if(!$this->invisible){
			$pk = new AddPlayerPacket();
			$pk->uuid = UUID::fromRandom();
			$pk->username = $this->title;
			$pk->eid = $this->entityId;
			$pk->x = $this->x;
			$pk->y = $this->y - 0.50;
			$pk->z = $this->z;
			$pk->item = Item::get(BlockIds::AIR, 0, 0);
			$flags = (
				(1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
				(1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
				(1 << Entity::DATA_FLAG_IMMOBILE)
			);
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->title . ($this->text !== "" ? "\n" . $this->text : "")],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
			];

			$p[] = $pk;
		}

		return $p;
	}
}
