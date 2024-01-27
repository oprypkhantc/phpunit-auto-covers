<?php

namespace Tests\PHPUnit\AutoCovers\Metadata;

interface MetadataModifier
{
	/**
	 * @param class-string                          $className
	 * @param class-string|array<int, class-string> $coveredClassNames
	 */
	public function addCoversClass(string $file, string $testClassName, string|array $coveredClassNames): void;

	/**
	 * @param class-string              $testClassName
	 * @param string|array<int, string> $coveredFunctions
	 */
	public function addCoversFunction(string $file, string $testClassName, string|array $coveredFunctions): void;
}
