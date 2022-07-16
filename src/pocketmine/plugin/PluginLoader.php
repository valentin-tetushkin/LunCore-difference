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

namespace pocketmine\plugin;

/**
 * Работает с различными типами плагинов
 */
interface PluginLoader {

	/**
	 * Loads the plugin contained in $file
	 *
	 * @param string $file
	 *
	 * @return Plugin
	 */
	public function loadPlugin($file);

	/**
	 * Gets the PluginDescription from the file
	 *
	 * @param string $file
	 *
	 * @return PluginDescription
	 */
	public function getPluginDescription($file);

	/**
	 * Returns the filename regex patterns that this loader accepts
	 *
	 * @return string
	 */
	public function getPluginFilters();

	/**
	 * Returns whether this PluginLoader can load the plugin in the given path.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function canLoadPlugin(string $path) : bool;

	/**
	 * @param Plugin $plugin
	 *
	 * @return void
	 */
	public function enablePlugin(Plugin $plugin);

	/**
	 * @param Plugin $plugin
	 *
	 * @return void
	 */
	public function disablePlugin(Plugin $plugin);


}