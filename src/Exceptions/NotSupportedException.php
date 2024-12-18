<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Exceptions;

use Akbarali\NatsSender\Enums\ExceptionCode;

class NotSupportedException extends InternalException
{
	
	public static function responseNotSupported(): static
	{
		return static::new(
			code: ExceptionCode::SenderResponseNotSupported,
		);
	}
	
}