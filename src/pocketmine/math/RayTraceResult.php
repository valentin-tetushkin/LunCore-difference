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

namespace pocketmine\math;

/**
 * Class representing a ray trace collision with an AxisAlignedBB
 */
class RayTraceResult{

	/**
	 * @var AxisAlignedBB
	 */
	public $bb;
	/**
	 * @var int
	 */
	public $hitFace;
	/**
	 * @var Vector3
	 */
	public $hitVector;

	/**
	 * @param int           $hitFace one of the Vector3::SIDE_* constants
	 */
	public function __construct(AxisAlignedBB $bb, int $hitFace, Vector3 $hitVector){
		$this->bb = $bb;
		$this->hitFace = $hitFace;
		$this->hitVector = $hitVector;
	}

	public function getBoundingBox() : AxisAlignedBB{
		return $this->bb;
	}

	public function getHitFace() : int{
		return $this->hitFace;
	}

	public function getHitVector() : Vector3{
		return $this->hitVector;
	}
}