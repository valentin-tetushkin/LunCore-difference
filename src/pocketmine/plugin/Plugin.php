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

/**
 * Plugin related classes
 */

namespace pocketmine\plugin;

use pocketmine\command\CommandExecutor;


/**
 * Рекомендуется использовать PluginBase для фактического плагина
 *
 */
interface Plugin extends CommandExecutor {

    /**
     * Вызывается при загрузке плагина перед вызовом onEnable()
     */
	public function onLoad();

    /**
     * Вызывается, когда плагин включен
     */
	public function onEnable();

	/**
	 * @return mixed
	 */
	public function isEnabled();

    /**
     * Вызывается, когда плагин отключен
     * Используйте это, чтобы освободить открытые вещи и завершить действия
     */
	public function onDisable();

	/**
	 * @return mixed
	 */
	public function isDisabled();

    /**
     * Получает папку данных плагина для сохранения файлов и конфигурации
     */
	public function getDataFolder();

	/**
	 * @return PluginDescription
	 */
	public function getDescription();

    /**
     * Получает встроенный ресурс в файле плагина.
     *
	 * @param string $filename
	 */
	public function getResource($filename);

    /**
     * Сохраняет встроенный ресурс в его относительное местоположение в папке данных
     *
     * @param строка $filename
     * @param логический $replace
     */
	public function saveResource($filename, $replace = false);

    /**
     * Возвращает все ресурсы, упакованные с плагином
     */
	public function getResources();

	/**
	 * @return \pocketmine\utils\Config
	 */
	public function getConfig();

	/**
	 * @return mixed
	 */
	public function saveConfig();

	/**
	 * @return mixed
	 */
	public function saveDefaultConfig();

	/**
	 * @return mixed
	 */
	public function reloadConfig();

	/**
	 * @return \pocketmine\Server
	 */
	public function getServer();

	/**
	 * @return mixed
	 */
	public function getName();

	/**
	 * @return PluginLogger
	 */
	public function getLogger();

	/**
	 * @return PluginLoader
	 */
	public function getPluginLoader();

}