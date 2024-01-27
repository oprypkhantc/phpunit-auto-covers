<?php

namespace Tests\PHPUnit\AutoCovers\Metadata;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;

class AttributeMetadataModifier implements MetadataModifier
{
	/** @var array<class-string, class-string[]> */
	private array $markedClassCovered = [];

	/** @var array<class-string, string[]> */
	private array $markedFunctionCovered = [];

	/**
	 * @param class-string                          $testClassName
	 * @param class-string|array<int, class-string> $coveredClassNames
	 */
	public function addCoversClass(string $file, string $testClassName, string|array $coveredClassNames): void
	{
		$coveredClassNames = $this->normalizeCovered($coveredClassNames);
		$justNowMarkedCoveredClasses = $this->markedClassCovered[$testClassName] ?? [];

		$coveredClassNames = array_values(array_diff($coveredClassNames, $justNowMarkedCoveredClasses));

		if (!$coveredClassNames) {
			return;
		}

		$this->markedClassCovered[$testClassName] = [
			...($this->markedClassCovered[$testClassName] ?? []),
			...$coveredClassNames,
		];

		$testFileContents = file_get_contents($file);

		foreach ($coveredClassNames as $coveredClassName) {
			$testFileContents = $this->addAttributeForClass(
				$testFileContents,
				$testClassName,
				CoversClass::class,
				"\\{$coveredClassName}::class",
			);
		}

		$testFileContents = $this->removeSeeAnnotations($testFileContents, $testClassName);

		file_put_contents($file, $testFileContents);
	}

	public function addCoversFunction(string $file, string $testClassName, array|string $coveredFunctions): void
	{
		$coveredFunctions = $this->normalizeCovered($coveredFunctions);
		$justNowMarkedCoveredFunctions = $this->markedFunctionCovered[$testClassName] ?? [];

		$coveredFunctions = array_values(array_diff($coveredFunctions, $justNowMarkedCoveredFunctions));

		if (!$coveredFunctions) {
			return;
		}

		$this->markedFunctionCovered[$testClassName] = [
			...($this->markedFunctionCovered[$testClassName] ?? []),
			...$coveredFunctions,
		];

		$testFileContents = file_get_contents($file);

		foreach ($coveredFunctions as $coveredFunction) {
			$testFileContents = $this->addAttributeForClass(
				$testFileContents,
				$testClassName,
				CoversFunction::class,
				"'{$coveredFunction}'",
			);
		}

		$testFileContents = $this->removeSeeAnnotations($testFileContents, $testClassName);

		file_put_contents($file, $testFileContents);
	}

	/**
	 * @param array<int, string|null>|string $covered
	 *
	 * @return class-string[]
	 */
	private function normalizeCovered(array|string $covered): array
	{
		$covered = array_unique(Arr::wrap($covered));

		return array_filter($covered, fn (string|null $classOrFunction) => (bool) $classOrFunction);
	}

	/**
	 * Add attributes before test class definition
	 */
	private function addAttributeForClass(
		string $testFileContents,
		string $testClassName,
		string $attributeClass,
		string $attributeArguments
	): string {
		return preg_replace(
			"/\nclass " . preg_quote(class_basename($testClassName)) . ' /',
			"\n#[\\{$attributeClass}({$attributeArguments})]$0",
			$testFileContents,
			limit: 1
		);
	}

	private function removeSeeAnnotations(string $testFileContents, string $className): string
	{
		// Replace all @see annotations
		do {
			$testFileContents = preg_replace(
				"/(\\/\\*[\\s\\S]*) \\* {?@see .*\n([\\s\\S]*\\*\\/\n(?:#[^\n]+\n)*class " . preg_quote(class_basename($className)) . ' )/',
				'$1$2',
				$testFileContents,
				limit: -1,
				count: $replacements,
			);
		} while ($replacements >= 1);

		// Remove empty docblocks /** */
		return preg_replace(
			"/\\/\\*\\*\n \\*\\/\n((?:#[^\n]+\n)*class " . preg_quote(class_basename($className)) . ' )/',
			'$1',
			$testFileContents
		);
	}
}
