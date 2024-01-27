<?php

namespace Tests\PHPUnit\AutoCovers\CoveringTracer;

use Illuminate\Support\Str;
use PHPUnit\Event\Event;
use PHPUnit\Event\TestSuite\Finished as TestSuiteFinished;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
use Tests\PHPUnit\AutoCovers\Metadata\MetadataModifier;

class UnitNamespaceBasedCoveringTracer implements CoveringTracer
{
	/**
	 * @param (callable(class-string): class-string|null) $classNameMapper
	 */
	public function __construct(
		private readonly MetadataModifier $metadataModifier,
		private readonly mixed $classNameMapper,
	) {}

	public function isAvailable(string $className): bool
	{
		return $this->coveredClassName($className) !== null;
	}

	public function trace(Event $event): void
	{
		if (!$event instanceof TestSuiteFinished) {
			return;
		}

		$testSuite = $event->testSuite();

		if (!$testSuite instanceof TestSuiteForTestClass) {
			return;
		}

		$coveredClassName = $this->coveredClassName($testSuite->className());

		if (!$coveredClassName) {
			return;
		}

		$this->metadataModifier->addCoversClass($testSuite->file(), $testSuite->className(), $coveredClassName);
	}

	private function coveredClassName(string $testClassName): ?string
	{
		$className = ($this->classNameMapper)($testClassName);

		if (!$className) {
			return null;
		}

		return Str::replaceEnd('Test', '', $className);
	}
}
