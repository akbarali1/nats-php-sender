<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Managers;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
	private const CACHE_KEY = 'nats:redis:config';
	
	public function setCache(int $pid): void
	{
		$processIds   = $this->getProcessIds();
		$processIds[] = $pid;
		
		$this->updateCache(array_unique($processIds));
	}
	
	public function forgetCache(int $runningId): void
	{
		$processIds = $this->getProcessIds();
		
		if (empty($processIds)) {
			return;
		}
		
		$filteredIds = array_filter($processIds, static fn($id) => $id !== $runningId);
		
		if (empty($filteredIds)) {
			Cache::forget(self::CACHE_KEY);
		} else {
			$this->updateCache($filteredIds);
		}
	}
	
	public function getConfig(): array
	{
		return Cache::get(self::CACHE_KEY, []);
	}
	
	public function getProcessIds(): array
	{
		return $this->getConfig()['pids'] ?? [];
	}
	
	private function updateCache(array $processIds): void
	{
		Cache::put(self::CACHE_KEY, ['pids' => $processIds]);
	}
}