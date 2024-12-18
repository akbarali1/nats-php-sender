<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Exceptions;

use Akbarali\NatsSender\Enums\ExceptionCode;

class SenderException extends InternalException
{
	
	public static function senderResponseNull(): static
	{
		return static::new(
			code: ExceptionCode::SenderResponseNull,
		);
		
	}
	
	
}