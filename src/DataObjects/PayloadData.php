<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\DataObjects;

use Akbarali\DataObject\DataObjectBase;

class PayloadData extends DataObjectBase
{
	public readonly string  $requestId;
	public readonly ?string $subject;
	public readonly int     $natsTimeOut;  //MicroSecond
	public readonly int     $totalTimeOut; //MicroSecond
	public array            $headers = [];
	public readonly string  $body;
	
	public function getBody(): array
	{
		return json_decode($this->body, true);
	}
	
}