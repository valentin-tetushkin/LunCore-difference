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

namespace pocketmine;

use pocketmine\permission\ServerOperator;

interface IPlayer extends ServerOperator {

	public function isOnline(); 	// TODO  @return bool
	public function getName(); 	// TODO  @return string
	public function isBanned(); // TODO  @return bool
	public function setBanned($banned);	// TODO bool $banned
	public function isWhitelisted();	// TODO bool
	public function setWhitelisted($value);	// TODO  bool $value
	public function getPlayer();	// TODO  @Player|null
	public function getFirstPlayed();	// TODO  @return int|двойной
	public function getLastPlayed();	// TODO  @return int|двойной
	public function hasPlayedBefore();	// TODO  @возврат смешанный

}