<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Console;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Payload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NatsChannelSender extends Command
{
	protected        $signature   = 'nats:redis:sender';
	protected        $description = 'Redis Nats sender';
	protected array  $redisChannelName, $natsConfiguration;
	protected string $redisResponseChannelName;
	protected Client $natsClient;
	
	public function __construct()
	{
		$this->natsConfiguration        = config('nats', []);
		$this->redisChannelName         = [$this->natsConfiguration['redis']['channel_name'] ?? 'requests_channel'];
		$this->redisResponseChannelName = $this->natsConfiguration['redis']['response_channel_name'] ?? 'response_channel_';
		
		parent::__construct();
	}
	
	public function handle(): void
	{
		$configuration = new Configuration($this->natsConfiguration['configuration'] ?? []);
		$configuration->setDelay($this->natsConfiguration['connection']['delay'] ?? 1);
		$this->natsClient = new Client($configuration);
		if (($this->natsConfiguration['connection']['name'] ?? null) !== null) {
			$this->natsClient->setName($this->natsConfiguration['connection']['name'] ?? null);
		}
		$this->natsClient->ping();
		
		$shallStopWorking = false;
		$this->listenForSignals($shallStopWorking);
		$this->info("{$this->signature} -- started");
		try {
			pcntl_signal_dispatch();
			Redis::createSubscription($this->redisChannelName, function ($message) {
				$requestData  = json_decode($message, true);
				$requestId    = $requestData['id'];
				$data         = $requestData['data'];
				$subscribe    = $data['subscribe'];
				$start        = microtime(true);
				$sendPayload  = new Payload($data['params'], $data['headers']);
				$response     = $this->natsClient->dispatch($subscribe, $sendPayload);
				$payload      = $response instanceof Payload ? $response : json_decode($response);
				$end          = microtime(true);
				$sendingRedis = Redis::connection('nats_sender')->publish($this->redisResponseChannelName.$requestId, json_encode([
					'id'           => $requestId,
					'nats_timeout' => number_format(($end - $start) * 1000000, 0, '.', ''),
					"payload"      => $payload,
				]));
				if ($sendingRedis !== 1) {
					$this->error("Qabul qilmadi.");
					Log::error("Redis so'rov qabul qilmay qoldi.");
					Log::error($message);
				}
			});
			$this->info("{$this->signature} -- end");
		} catch (\Throwable $exception) {
			Log::error($exception);
			$this->error($exception->getMessage());
		}
	}
	
	private function disconnect(): void
	{
		Redis::unsubscribe($this->redisChannelName);
		$this->natsClient->disconnect();
	}
	
	protected function listenForSignals(bool &$shallStopWorking): void
	{
		// сигнал об остановке от supervisord
		pcntl_signal(SIGTERM, function () use (&$shallStopWorking) {
			$this->info("Received SIGTERM\n");
			$shallStopWorking = true;
			$this->disconnect();
		});
		
		// обработчик для ctrl+z
		pcntl_signal(SIGTSTP, function () use (&$shallStopWorking) {
			$this->info("Received SIGTSTP\n");
			$shallStopWorking = true;
			$this->disconnect();
		});
		
		// Close Terminal
		pcntl_signal(SIGHUP, function () use (&$shallStopWorking) {
			$this->info("Received SIGHUP\n");
			$shallStopWorking = true;
			$this->disconnect();
		});
		
		// обработчик для ctrl+c
		pcntl_signal(SIGINT, function () use (&$shallStopWorking) {
			$this->info("Received SIGINT\n");
			$shallStopWorking = true;
			$this->disconnect();
		});
		
		// Continue Process
		//pcntl_signal(SIGCONT, function () {
		//	$this->info("Received SIGCONT\n");
		//});
	}
	
}
