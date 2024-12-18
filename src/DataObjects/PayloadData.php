<?php
declare(strict_types=1);

namespace Akbarali\NatsSender;

use Akbarali\DataObject\DataObjectBase;

class PayloadData extends DataObjectBase
{
	
	public readonly string  $body;
	public array            $headers = [];
	public readonly ?string $subject;
	public readonly int     $natsTimeOut; //MicroSecond
	
	public function getBody(): array
	{
		return json_decode($this->body, true);
	}
	
}