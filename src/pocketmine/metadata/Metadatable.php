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

namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;

interface Metadatable {

	/**
     * Устанавливает значение метаданных в хранилище метаданных реализующего объекта.
	 *
	 * @param string        $metadataKey
	 * @param MetadataValue $newMetadataValue
	 */
	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue);

	/**
     * Возвращает список ранее установленных значений метаданных из реализующего
     * хранилище метаданных объекта.
	 *
	 * @param string $metadataKey
	 *
	 * @return MetadataValue[]
	 */
	public function getMetadata(string $metadataKey);

	/**
     * Проверяет, содержит ли реализующий объект заданный
     * значение метаданных в его хранилище метаданных.
	 *
	 * @param string $metadataKey
	 *
	 * @return bool
	 */
	public function hasMetadata(string $metadataKey) : bool;

	/**
     * Удаляет заданное значение метаданных из реализующего объекта
     * хранилище метаданных.
	 *
	 * @param string $metadataKey
	 * @param Plugin $owningPlugin
	 */
	public function removeMetadata(string $metadataKey, Plugin $owningPlugin);

}