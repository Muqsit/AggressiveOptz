<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper\world;

use pocketmine\world\World;

final class AggressiveOptzWorldCache{

	/** @var AggressiveOptzChunkCache[] */
	private array $chunks = [];

	/** @var mixed[] */
	private array $cache = [];

	public function __construct(World $world){
		foreach($world->getLoadedChunks() as $chunk_hash => $_){
			World::getXZ($chunk_hash, $chunkX, $chunkZ);
			$this->onChunkLoad($chunkX, $chunkZ);
		}
	}

	public function onChunkLoad(int $x, int $z) : void{
		$this->chunks[World::chunkHash($x, $z)] = new AggressiveOptzChunkCache();
	}

	public function onChunkUnload(int $x, int $z) : void{
		unset($this->chunks[World::chunkHash($x, $z)]);
	}

	public function getChunk(int $x, int $z) : ?AggressiveOptzChunkCache{
		return $this->chunks[World::chunkHash($x, $z)] ?? null;
	}

	/**
	 * @param string $key
	 * @param mixed|null $value
	 */
	public function set(string $key, $value) : void{
		$this->cache[$key] = $value;
	}

	public function remove(string $key) : void{
		unset($this->cache[$key]);
	}

	/**
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function get(string $key, $default = null){
		return $this->cache[$key] ?? $default;
	}
}