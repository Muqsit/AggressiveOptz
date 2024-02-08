<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;

final class LazyEncodedPacket implements ClientboundPacket{

	public const NETWORK_ID = -1;

	private ?string $encoded = null;

	public function __construct(
		readonly private PacketSerializerContext $context,
		readonly private ClientboundPacket $inner
	){}

	public function pid() : int{
		return $this->inner->pid();
	}

	public function getName() : string{
		return $this->inner->getName();
	}

	public function canBeSentBeforeLogin() : bool{
		return $this->inner->canBeSentBeforeLogin();
	}

	public function decode(PacketSerializer $in) : void{
		$this->inner->decode($in);
	}

	public function encode(PacketSerializer $out) : void{
		if($this->encoded === null){
			$serializer = PacketSerializer::encoder($this->context);
			$this->inner->encode($serializer);
			$this->encoded = $serializer->getBuffer();
		}
		$out->put($this->encoded);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $this->inner->handle($handler);
	}
}