<?php

namespace Tests\PHPUnit\AutoCovers\Util;

use ReflectionClass;

class ReflectionHelper
{
	public static function classDocComment(string $className): ?string
	{
		return (new ReflectionClass($className))->getDocComment() ?: null;
	}
}
