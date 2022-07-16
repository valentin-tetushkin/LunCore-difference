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

namespace pocketmine\nbt\tag;


use JsonSerializable;

abstract class NamedTag extends Tag implements JsonSerializable{

	protected $__name;

	/**
	 * @param string                                                                  $name
	 * @param bool|float|int|ByteTag|ShortTag|array|CompoundTag|ListTag|string $value
	 */
	public function __construct($name = "", $value = null){
		$this->__name = ($name === null or $name === false) ? "" : $name;
		if($value !== null){
			$this->value = $value;
		}
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->__name;
	}

	/**
	 * @param $name
	 */
	public function setName($name){
		$this->__name = $name;
	}

	public function jsonSerialize(){
        return [
            "tag" => get_class($this),
            "name" => $this->getName(),
            "value" => $this->getValue()
        ];
    }

    /**
     * Сравнивает этот NamedTag с заданным NamedTag и определяет, равны ли они, на основе имени, типа
     * и значение.
	 *
	 * @param NamedTag $that
	 *
	 * @return bool
	 */
	public function equals(NamedTag $that) : bool{
		return $this->__name === $that->__name and $this->equalsValue($that);
	}

    /**
     * Сравнивает этот NamedTag с заданным NamedTag и определяет, равны ли они, основываясь только на типе и значении.
     * Сложные типы тегов должны переопределять это, чтобы обеспечить правильное сравнение значений.
	 *
	 * @param NamedTag $that
	 *
	 * @return bool
	 */
	protected function equalsValue(NamedTag $that) : bool{
		return $that instanceof $this and $this->getValue() === $that->getValue();
	}
}