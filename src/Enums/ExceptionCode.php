<?php
declare(strict_types=1);

namespace Akbarali\NatsSender\Enums;

use Illuminate\Support\Facades\Route;

enum ExceptionCode: int
{
	case UnknownExceptionCode       = -1000;
	case SenderResponseNull         = -1001;
	case SenderResponseNotSupported = -2000;
	
	public function getStatusCode(): int
	{
		$value = $this->value;
		
		return match (true) {
			$value === -10000 => 404,
			$value === -20000 => 507,
			default           => 500,
		};
	}
	
	public function getMessage(): string
	{
		$key         = "exceptions.{$this->value}.message";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "Something went wrong: ".$this->value;
		}
		
		return $translation;
	}
	
	public function getDescription(): string
	{
		$key         = "exceptions.{$this->value}.description";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "No additional description provided: ".$this->value;
		}
		
		return $translation;
	}
	
	public static function getDescriptionByInternalCode(int $internalCode): string
	{
		$key         = "exceptions.{$internalCode}.description";
		$translation = trans($key);
		
		if ($key === $translation) {
			return "No additional description provided: ".$internalCode;
		}
		
		return $translation;
	}
	
	
	public function getDescriptionParams(array $params): string
	{
		$key         = "exceptions.{$this->value}.description";
		$translation = trans($key, $params);
		
		if ($key === $translation) {
			return "No additional description provided: ".$this->value;
		}
		
		return $translation;
	}
	
	public function getLink(): ?string
	{
		if (Route::has('docs.exceptions.code')) {
			return route('docs.exceptions.code', [
				'code' => $this->value,
			]);
		}
		
		return null;
	}
	
	public static function findExceptionCode(int $code): ExceptionCode
	{
		foreach (self::cases() as $value) {
			if ($value->value === $code) {
				return $value;
			}
		}
		
		return self::UnknownExceptionCode;
	}
	
	public static function count(): int
	{
		return count(self::cases());
	}
}