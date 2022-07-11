<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper\world;

final class AggressiveOptzChunkCache{

	/** @var array<string, mixed> */
	private array $cache = [];

	public function set(string $key, mixed $value) : void{
		$this->cache[$key] = $value;
	}

	public function remove(string $key) : void{
		unset($this->cache[$key]);
	}

	public function get(string $key, mixed $default = null) : mixed{
		return $this->cache[$key] ?? $default;
	}
}