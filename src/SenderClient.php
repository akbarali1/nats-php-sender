<?php
declare(strict_types=1);

namespace Akbarali\NatsSender;

use Akbarali\NatsSender\DataObjects\PayloadData;
use Akbarali\NatsSender\Exceptions\NotSupportedException;
use Akbarali\NatsSender\Exceptions\SenderException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Predis\Client;
use Predis\PubSub\Consumer;

class SenderClient
{
	protected string $redisChannelName;
	protected string $redisResponseChannelName;
	
	public function __construct()
	{
		$config                         = config('nats', []);
		$this->redisChannelName         = $config['redis']['channel_name'] ?? 'requests_channel';
		$this->redisResponseChannelName = $config['redis']['response_channel_name'] ?? 'r_ch_';
	}
	
	public static function getInstance(): static
	{
		return new static();
	}
	
	/**
	 * @throws NotSupportedException
	 * @throws SenderException
	 */
	public function sendMessageRabbit(string $subscribeName, mixed $request, array $headers = []): PayloadData
	{
		$requestId       = Str::uuid()->toString();
		$responseChannel = [$this->redisResponseChannelName.$requestId];
		$start           = microtime(true);
		
		/** @var Client $client */
		$client = Redis::connection('nats_sender')->client();
		/** @var Consumer $loop */
		$loop = $client->pubSubLoop();
		$loop->subscribe($responseChannel);
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
		if (is_null($response)) {
			throw SenderException::senderResponseNull();
		}
		$response = json_decode($response, true);
		$end      = microtime(true);
		if (isset($response['payload']['subject'])) {
			return PayloadData::fromArray($response['payload'] + [
					'natsTimeOut'  => $response['nats_timeout'],
					'totalTimeOut' => number_format(($end - $start) * 1000000, 0, '.', ''),
					'requestId'    => $response['id'],
				]);
		}
		
		throw NotSupportedException::responseNotSupported();
	}
	
}