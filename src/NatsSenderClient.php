<?php
declare(strict_types=1);

namespace Akbarali\NatsSender;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Predis\Client;
use Predis\PubSub\Consumer;

class NatsSenderClient
{
	protected array $redisChannelName;
	protected array $redisResponseChannelName;
	
	public static function getInstance(): static
	{
		return new static();
	}
	
	public function __construct()
	{
		$config                         = config('nats.redis', []);
		$this->redisChannelName         = $config['redis']['channel_name'] ?? ['requests_channel'];
		$this->redisResponseChannelName = $config['redis']['response_channel_name'] ?? 'response_channel_';
	}
	
	public function sendMessageRabbit(string $subscribeName, mixed $request, array $headers = []): string
	{
		$requestId       = Str::uuid()->toString();
		$responseChannel = $this->redisResponseChannelName.$requestId;
		
		/** @var Client $client */
		$client = Redis::connection('nats_sender')->client();
		/** @var Consumer $loop */
		$loop = $client->pubSubLoop();
		$loop->subscribe([$responseChannel]);
		$response = null;
		$i        = 0;
		foreach ($loop as $message) {
			if ($i === 0) {
				Redis::publish($this->redisChannelName, json_encode([
					'id'   => $requestId,
					'data' => [
						'subscribe' => $subscribeName,
						"headers"   => $headers,
						"params"    => $request,
					],
				]));
			}
			
			if ($message->kind === 'message' || $message->kind === 'pmessage') {
				$response = $message->payload;
				break;
			}
			$i++;
		}
		
		return $response;
	}
	
}