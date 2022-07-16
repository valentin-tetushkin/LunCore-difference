<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * @author LunCore team
 * @link http://vk.com/luncore
 * @creator vk.com/klainyt
 *
*/

namespace pocketmine\resourcepacks;


interface ResourcePack{

    /**
     * Возвращает путь к пакету ресурсов. Это может быть файл или каталог, в зависимости от типа пакета.
     */
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

    /**
     * Возвращает фрагмент ZIP-архива пакета ресурсов в виде массива байтов для отправки клиентам.
     *
     * Обратите внимание, что пакеты ресурсов **всегда** должны быть в формате zip-архива для отправки.
     * Для этой цели загрузчику ресурсов папки может потребоваться выполнить сжатие «на лету».
     *
     * @param int $start Смещение для начала чтения чанка с
     * @param int $length Максимальная длина возвращаемых данных.
     *
     * @return строка массив байтов
     * @throws \InvalidArgumentException, если чанка не существует
     */
	public function getPackChunk(int $start, int $length) : string;
}