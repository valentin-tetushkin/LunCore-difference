<?php
/*
# ╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
# ║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
# ║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
# ║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
# ║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
# ╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine\nbt;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\EndTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\BinaryDataException;

#ifndef COMPILE
use pocketmine\utils\Binary;
#endif


#include <rules/NBT.h>

/**
 * Named Binary Tag encoder/decoder
 */
class NBT {

	const LITTLE_ENDIAN = 0;
	const BIG_ENDIAN = 1;
	const TAG_End = 0;
	const TAG_Byte = 1;
	const TAG_Short = 2;
	const TAG_Int = 3;
	const TAG_Long = 4;
	const TAG_Float = 5;
	const TAG_Double = 6;
	const TAG_ByteArray = 7;
	const TAG_String = 8;
	const TAG_List = 9;
	const TAG_Compound = 10;
	const TAG_IntArray = 11;

	public $buffer;
	public $offset;
	public $endianness;
	private $data;

	/**
	 * @param int $type
	 *
	 * @return Tag
	 */
	public static function createTag(int $type){
		switch($type){
			case self::TAG_End:
				return new EndTag();
			case self::TAG_Byte:
				return new ByteTag();
			case self::TAG_Short:
				return new ShortTag();
			case self::TAG_Int:
				return new IntTag();
			case self::TAG_Long:
				return new LongTag();
			case self::TAG_Float:
				return new FloatTag();
			case self::TAG_Double:
				return new DoubleTag();
			case self::TAG_ByteArray:
				return new ByteArrayTag();
			case self::TAG_String:
				return new StringTag();
			case self::TAG_List:
				return new ListTag();
			case self::TAG_Compound:
				return new CompoundTag();
			case self::TAG_IntArray:
				return new IntArrayTag();
			default:
				throw new \InvalidArgumentException("Unknown NBT tag type $type");
		}
	}

	/**
	 * @param ListTag $tag1
	 * @param ListTag $tag2
	 *
	 * @return bool
	 */
	public static function matchList(ListTag $tag1, ListTag $tag2){
		if($tag1->getName() !== $tag2->getName() or $tag1->getCount() !== $tag2->getCount()){
			return false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return false;
			}

			if($v instanceof CompoundTag){
				if(!self::matchTree($v, $tag2->{$k})){
					return false;
				}
			}elseif($v instanceof ListTag){
				if(!self::matchList($v, $tag2->{$k})){
					return false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param CompoundTag $tag1
	 * @param CompoundTag $tag2
	 *
	 * @return bool
	 */
	public static function matchTree(CompoundTag $tag1, CompoundTag $tag2){
		if($tag1->getName() !== $tag2->getName() or $tag1->getCount() !== $tag2->getCount()){
			return false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return false;
			}

			if($v instanceof CompoundTag){
				if(!self::matchTree($v, $tag2->{$k})){
					return false;
				}
			}elseif($v instanceof ListTag){
				if(!self::matchList($v, $tag2->{$k})){
					return false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param CompoundTag $tag1
	 * @param CompoundTag $tag2
	 * @param bool        $override
	 *
	 * @return CompoundTag
	 */
	public static function combineCompoundTags(CompoundTag $tag1, CompoundTag $tag2, bool $override = false) : CompoundTag{
		$tag1 = clone $tag1;
		foreach($tag2 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}
			if(!isset($tag1->{$k}) or (isset($tag1->{$k}) and $override)){
				$tag1->{$k} = clone $v;
			}
		}
		return $tag1;
	}

	/**
	 * @param $len
	 *
	 * @return bool|string
	 */
	public function get($len){
		if($len === 0){
			return "";
		}

		$buflen = strlen($this->buffer);
		if($len === true){
			$str = substr($this->buffer, $this->offset);
			$this->offset = $buflen;
			return $str;
		}
		if($len < 0){
			$this->offset = $buflen - 1;
			return "";
		}
		$remaining = $buflen - $this->offset;
		if($remaining < $len){
			throw new BinaryDataException("Not enough bytes left in buffer: need $len, have $remaining");
		}

		return $len === 1 ? $this->buffer[$this->offset++] : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	/**
	 * @param $v
	 */
	public function put($v){
		$this->buffer .= $v;
	}

	/**
	 * @return bool
	 */
	public function feof(){
		return !isset($this->buffer[$this->offset]);
	}

	/**
	 * NBT constructor.
	 *
	 * @param int $endianness
	 */
	public function __construct($endianness = self::LITTLE_ENDIAN){
		$this->offset = 0;
		$this->endianness = $endianness & 0x01;
	}

	/**
	 * @param      $buffer
	 * @param bool $doMultiple
	 * @param bool $network
	 */
	public function read($buffer, $doMultiple = false, bool $network = false){
		$this->offset = 0;
		$this->buffer = $buffer;
		$this->data = $this->readTag($network);
		if($doMultiple and !$this->feof()){
			$this->data = [$this->data];
			do{
				$tag = $this->readTag($network);
				if($tag !== null){
					$this->data[] = $tag;
				}
			}while(!$this->feof());
		}
		$this->buffer = "";
	}

	/**
	 * @param     $buffer
	 * @param int $compression
	 */
	public function readCompressed($buffer){
		$decompressed = zlib_decode($buffer);
		if($decompressed === false){
			throw new \UnexpectedValueException("Failed to decompress data");
		}
		return $this->read($decompressed);
	}

	/**
	 * @param bool $network
	 *
	 * @return string|bool
	 */
	public function write(bool $network = false){
		$this->offset = 0;
		$this->buffer = "";

		if($this->data instanceof CompoundTag){
			$this->writeTag($this->data, $network);

			return $this->buffer;
		}elseif(is_array($this->data)){
			foreach($this->data as $tag){
				$this->writeTag($tag, $network);
			}
			return $this->buffer;
		}

		return false;
	}

	/**
	 * @param int $compression
	 * @param int $level
	 *
	 * @return bool|string
	 */
	public function writeCompressed($compression = ZLIB_ENCODING_GZIP, $level = 7){
		if(($write = $this->write()) !== false){
			return zlib_encode($write, $compression, $level);
		}

		return false;
	}

	/**
	 * @param bool $network
	 *
	 * @return ByteArrayTag|ByteTag|DoubleTag|FloatTag|IntTag|LongTag|ShortTag
	 */
	public function readTag(bool $network = false){
		$tagType = $this->getByte();
		$tag = self::createTag($tagType);

		if($tag instanceof NamedTag){
			$tag->setName($this->getString($network));
			$tag->read($this, $network);
		}

		return $tag;
	}

	/**
	 * @param Tag  $tag
	 * @param bool $network
	 */
	public function writeTag(Tag $tag, bool $network = false){
		$this->putByte($tag->getType());
		if($tag instanceof NamedTag){
			$this->putString($tag->getName(), $network);
		}
		$tag->write($this, $network);
	}

	/**
	 * @return int
	 */
	public function getByte(){
		return Binary::readByte($this->get(1));
	}

	public function getSignedByte(){
		return Binary::readSignedByte($this->get(1));
	}

	/**
	 * @param $v
	 */
	public function putByte($v){
		$this->buffer .= Binary::writeByte($v);
	}

	/**
	 * @return int
	 */
	public function getShort(){
		return $this->endianness === self::BIG_ENDIAN ? Binary::readShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	/**
	 * @return int
	 */
	public function getSignedShort() : int{
		return $this->endianness === self::BIG_ENDIAN ? Binary::readSignedShort($this->get(2)) : Binary::readSignedLShort($this->get(2));
	}

	/**
	 * @param $v
	 */
	public function putShort($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeShort($v) : Binary::writeLShort($v);
	}

	/**
	 * @param bool $network
	 *
	 * @return int
	 */
	public function getInt(bool $network = false){
		if($network === true){
			return Binary::readVarInt($this->buffer, $this->offset);
		}
		return $this->endianness === self::BIG_ENDIAN ? Binary::readInt($this->get(4)) : Binary::readLInt($this->get(4));
	}

	/**
	 * @param      $v
	 * @param bool $network
	 */
	public function putInt($v, bool $network = false){
		if($network === true){
			$this->buffer .= Binary::writeVarInt($v);
		}else{
			$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeInt($v) : Binary::writeLInt($v);
		}
	}

	public function getLong(bool $network = false) : int{
		if($network){
			return Binary::readVarLong($this->buffer, $this->offset);
		}
		return $this->endianness === self::BIG_ENDIAN ? Binary::readLong($this->get(8)) : Binary::readLLong($this->get(8));
	}

	public function putLong($v, bool $network = false){
		if($network){
			$this->buffer .= Binary::writeVarLong($v);
		}else{
			$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeLong($v) : Binary::writeLLong($v);
		}
	}

	/**
	 * @return float
	 */
	public function getFloat(){
		return $this->endianness === self::BIG_ENDIAN ? Binary::readFloat($this->get(4)) : Binary::readLFloat($this->get(4));
	}

	/**
	 * @param $v
	 */
	public function putFloat($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeFloat($v) : Binary::writeLFloat($v);
	}

	/**
	 * @return mixed
	 */
	public function getDouble(){
		return $this->endianness === self::BIG_ENDIAN ? Binary::readDouble($this->get(8)) : Binary::readLDouble($this->get(8));
	}

	/**
	 * @param $v
	 */
	public function putDouble($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeDouble($v) : Binary::writeLDouble($v);
	}

	/**
	 * @param bool $network
	 *
	 * @return bool|string
	 */
	public function getString(bool $network = false){
		$len = $network ? Binary::readUnsignedVarInt($this->buffer, $this->offset) : $this->getShort();
		return $this->get($len);
	}

	/**
	 * @param      $v
	 * @param bool $network
	 */
	public function putString($v, bool $network = false){
		if($network === true){
			$len = strlen($v);
		    if($len > 32767){
			    throw new \InvalidArgumentException("NBT strings cannot be longer than 32767 bytes, got $len bytes");
		    }
		    $this->put(Binary::writeUnsignedVarInt($len));
		}else{
			$len = strlen($v);
		    if($len > 32767){
			    throw new \InvalidArgumentException("NBT strings cannot be longer than 32767 bytes, got $len bytes");
		    }
		    $this->putShort($len);
		}
		$this->buffer .= $v;
	}

	public function getArray(){
		$data = [];
		self::toArray($data, $this->data);
		return $data;
	}

	/**
	 * @param array $data
	 * @param Tag   $tag
	 */
	private static function toArray(array &$data, Tag $tag){
		/** @var CompoundTag[]|ListTag[]|IntArrayTag[] $tag */
		foreach($tag as $key => $value){
			if($value instanceof CompoundTag or $value instanceof ListTag or $value instanceof IntArrayTag){
				$data[$key] = [];
				self::toArray($data[$key], $value);
			}else{
				$data[$key] = $value->getValue();
			}
		}
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return null|ByteTag|FloatTag|IntTag|StringTag
	 */
	public static function fromArrayGuesser($key, $value){
		if(is_int($value)){
			return new IntTag($key, $value);
		}elseif(is_float($value)){
			return new FloatTag($key, $value);
		}elseif(is_string($value)){
			return new StringTag($key, $value);
		}elseif(is_bool($value)){
			return new ByteTag($key, $value ? 1 : 0);
		}

		return null;
	}

	/**
	 * @param Tag      $tag
	 * @param array    $data
	 * @param callable $guesser
	 */
	private static function fromArray(Tag $tag, array $data, callable $guesser){
		foreach($data as $key => $value){
			if(is_array($value)){
				$isNumeric = true;
				$isIntArray = true;
				foreach($value as $k => $v){
					if(!is_numeric($k)){
						$isNumeric = false;
						break;
					}elseif(!is_int($v)){
						$isIntArray = false;
					}
				}
				$node = $isNumeric ? ($isIntArray ? new IntArrayTag($key, []) : new ListTag($key, [])) : new CompoundTag($key, []);
				self::fromArray($node, $value, $guesser);
				$tag[$key] = $node;
			}else{
				$v = call_user_func($guesser, $key, $value);
				if($v instanceof Tag){
					$tag[$key] = $v;
				}
			}
		}
	}

	/**
	 * @param array         $data
	 * @param callable|null $guesser
	 */
	public function setArray(array $data, callable $guesser = null){
		$this->data = new CompoundTag("", []);
		self::fromArray($this->data, $data, $guesser ?? [self::class, "fromArrayGuesser"]);
	}

	/**
	 * @return CompoundTag|array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param CompoundTag|array $data
	 */
	public function setData($data){
		$this->data = $data;
	}

}
