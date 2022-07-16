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

use ArrayAccess;
use Countable;
use pocketmine\nbt\NBT;

#include <rules/NBT.h>

class ListTag extends NamedTag implements ArrayAccess, Countable{

	private $tagType = NBT::TAG_End;

	/**
	 * ListTag constructor.
	 *
	 * @param string $name
	 * @param array  $value
	 */
	public function __construct($name = "", $value = []){
		parent::__construct($name);
		foreach($value as $k => $v){
			$this->{$k} = $v;
		}
	}

	/**
	 * @return array
	 */
	public function &getValue(){
		$value = [];
		foreach($this as $k => $v){
			if($v instanceof Tag){
				$value[$k] = $v;
			}
		}

		return $value;
	}

	public function setValue($value){
		if(is_array($value)){
			foreach($value as $name => $tag){
				if($tag instanceof NamedTag){
					$this->{$name} = $tag;
				}
			}
		}
	}

	/**
	 * @return int
	 */
	public function getCount(){
		$count = 0;
		foreach($this as $tag){
			if($tag instanceof Tag){
				++$count;
			}
		}

		return $count;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset){
		return isset($this->{$offset});
	}

	/**
	 * @param mixed $offset
	 *
	 * @return null
	 */
	public function offsetGet($offset){
		if(isset($this->{$offset}) and $this->{$offset} instanceof Tag){
			if($this->{$offset} instanceof ArrayAccess){
				return $this->{$offset};
			}else{
				return $this->{$offset}->getValue();
			}
		}

		return null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value){
		if($value instanceof Tag){
			$this->{$offset} = $value;
		}elseif($this->{$offset} instanceof Tag){
			$this->{$offset}->setValue($value);
		}
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset){
		unset($this->{$offset});
	}

	/**
	 * @param int $mode
	 *
	 * @return int
	 */
	public function count($mode = COUNT_NORMAL){
		$count = 0;
		for($i = 0; isset($this->{$i}); $i++){
			if($mode === COUNT_RECURSIVE and $this->{$i} instanceof Countable){
				$count += count($this->{$i});
			}else{
				$count++;
			}
		}

		return $count;
	}

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_List;
	}

    /**
     * @param int $type
     */
	public function setTagType(int $type){
		$this->tagType = $type;
	}

	/**
	 * @return mixed
	 */
	public function getTagType() : int{
		return $this->tagType;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){
		$this->value = [];
		$this->tagType = $nbt->getByte();
		$size = $nbt->getInt($network);

		$tagBase = NBT::createTag($this->tagType);
		for($i = 0; $i < $size && !$nbt->feof(); ++$i){
			$tag = clone $tagBase;
			$tag->read($nbt, $network);
			$this->{$i} = $tag;
		}
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return bool
	 */
	public function write(NBT $nbt, bool $network = false){
		if($this->tagType === NBT::TAG_End){ //previously empty list, try detecting type from tag children
			$id = NBT::TAG_End;
			foreach($this as $tag){
				if($tag instanceof Tag and !($tag instanceof EndTag)){
					if($id === NBT::TAG_End){
						$id = $tag->getType();
					}elseif($id !== $tag->getType()){
						return false;
					}
				}
			}
			$this->tagType = $id;
		}

		$nbt->putByte($this->tagType);

		/** @var Tag[] $tags */
		$tags = [];
		foreach($this as $tag){
			if($tag instanceof Tag){
				$tags[] = $tag;
			}
		}
		$nbt->putInt(count($tags), $network);
		foreach($tags as $tag){
			$tag->write($nbt, $network);
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		$str = get_class($this) . "{\n";
		foreach($this as $tag){
			if($tag instanceof Tag){
				$str .= get_class($tag) . ":" . $tag->__toString() . "\n";
			}
		}
		return $str . "}";
	}

	public function __clone(){
		foreach($this as $key => $tag){
			if($tag instanceof Tag){
				$this->{$key} = clone $tag;
			}
		}
	}
}
