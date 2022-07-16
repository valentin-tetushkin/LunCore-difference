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
use pocketmine\plugin\PluginException;

abstract class MetadataStore {
	/** @var \SplObjectStorage[] */
	private $metadataMap;

	/**
	 * Adds a metadata value to an object.
	 *
	 * @param Metadatable   $subject
	 * @param string        $metadataKey
	 * @param MetadataValue $newMetadataValue
	 */
	public function setMetadata(Metadatable $subject, string $metadataKey, MetadataValue $newMetadataValue){
		$owningPlugin = $newMetadataValue->getOwningPlugin();
		if($owningPlugin === null){
			throw new PluginException("Plugin cannot be null");
		}

		$key = $this->disambiguate($subject, $metadataKey);
		if(!isset($this->metadataMap[$key])){
			$entry = new \SplObjectStorage();
			$this->metadataMap[$key] = $entry;
		}else{
			$entry = $this->metadataMap[$key];
		}
		$entry[$owningPlugin] = $newMetadataValue;
	}

	/**
     * Возвращает все значения метаданных, прикрепленные к объекту. Если несколько
     * имеют прикрепленные метаданные, каждое значение будет включено.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return MetadataValue[]
	 */
	public function getMetadata(Metadatable $subject, string $metadataKey){
		$key = $this->disambiguate($subject, $metadataKey);
        return $this->metadataMap[$key] ?? [];
	}

	/**
     * Проверяет, установлен ли для объекта атрибут метаданных.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return bool
	 */
	public function hasMetadata(Metadatable $subject, string $metadataKey) : bool{
		return isset($this->metadataMap[$this->disambiguate($subject, $metadataKey)]);
	}

	/**
     * Удаляет элемент метаданных, принадлежащий плагину, из темы.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 * @param Plugin      $owningPlugin
	 */
	public function removeMetadata(Metadatable $subject, string $metadataKey, Plugin $owningPlugin){
		$key = $this->disambiguate($subject, $metadataKey);
		if(isset($this->metadataMap[$key])){
			unset($this->metadataMap[$key][$owningPlugin]);
			if($this->metadataMap[$key]->count() === 0){
				unset($this->metadataMap[$key]);
			}
		}
	}

	/**
     * Делает недействительными все метаданные в хранилище метаданных, которые происходят из
     * данный плагин. Это заставит каждый недействительный элемент метаданных
     * быть пересчитан при следующем доступе.
	 *
	 * @param Plugin $owningPlugin
	 */
	public function invalidateAll(Plugin $owningPlugin){
		/** @var MetadataValue[] $values */
		foreach($this->metadataMap as $values){
			if(isset($values[$owningPlugin])){
				$values[$owningPlugin]->invalidate();
			}
		}
	}

	/**
     * Создает уникальное имя для объекта, получающего метаданные, комбинируя
     * уникальные данные субъекта с помощью metadataKey.
	 *
	 * @param Metadatable $subject
	 * @param string      $metadataKey
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public abstract function disambiguate(Metadatable $subject, $metadataKey);
}