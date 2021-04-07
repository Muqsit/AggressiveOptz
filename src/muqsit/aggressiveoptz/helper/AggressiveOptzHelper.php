<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper;

use muqsit\aggressiveoptz\AggressiveOptzAPI;
use muqsit\aggressiveoptz\helper\world\AggressiveOptzWorldCacheManager;

final class AggressiveOptzHelper{

	private AggressiveOptzWorldCacheManager $world_cache_manager;

	public function __construct(){
		$this->world_cache_manager = new AggressiveOptzWorldCacheManager();
	}

	public function init(AggressiveOptzAPI $api) : void{
		$this->world_cache_manager->init($api);
	}

	public function getWorldCacheManager() : AggressiveOptzWorldCacheManager{
		return $this->world_cache_manager;
	}
}