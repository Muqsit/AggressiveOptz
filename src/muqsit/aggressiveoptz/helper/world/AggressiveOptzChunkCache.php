<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper\world;

final class AggressiveOptzChunkCache{

	/** @var mixed[] */
	private array $cache = [];

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