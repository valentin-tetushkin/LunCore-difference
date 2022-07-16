<?php

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

use function strlen;
use function substr;

class Datagram extends Packet{
	const BITFLAG_VALID = 0x80;
	const BITFLAG_ACK = 0x40;
	const BITFLAG_NAK = 0x20; // hasBAndAS for ACKs

	/*
	 * These flags can be set on regular datagrams, but they are useless as per the public version of RakNet
	 * (the receiving client will not use them or pay any attention to them).
	 */
	const BITFLAG_PACKET_PAIR = 0x10;
	const BITFLAG_CONTINUOUS_SEND = 0x08;
	const BITFLAG_NEEDS_B_AND_AS = 0x04;

	/** @var int */
	public $headerFlags = 0;

	/** @var (EncapsulatedPacket|string)[] */
	public $packets = [];

	/** @var int|null */
	public $seqNumber = null;

	protected function encodeHeader(){
		$this->putByte(self::BITFLAG_VALID | $this->headerFlags);
	}

	protected function encodePayload(){
		$this->putLTriad($this->seqNumber);
		foreach($this->packets as $packet){
			$this->put($packet instanceof EncapsulatedPacket ? $packet->toBinary() : $packet);
		}
	}

	public function length(){
		$length = 4;
		foreach($this->packets as $packet){
			$length += $packet instanceof EncapsulatedPacket ? $packet->getTotalLength() : strlen($packet);
		}

		return $length;
	}

	protected function decodeHeader(){
		$this->headerFlags = $this->getByte();
	}

	protected function decodePayload(){
		$this->seqNumber = $this->getLTriad();

		while(!$this->feof()){
			$offset = 0;
			$data = substr($this->buffer, $this->offset);
			$packet = EncapsulatedPacket::fromBinary($data, $offset);
			$this->offset += $offset;
			if($packet->buffer === ''){
				break;
			}
			$this->packets[] = $packet;
		}
	}

	public function clean(){
		$this->packets = [];
		$this->seqNumber = null;
		return parent::clean();
	}
}