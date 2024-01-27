<?php

namespace Tests\PHPUnit\AutoCovers\CoveringTracer;

use Illuminate\Support\Str;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\Test\Finished as TestFinished;
use PHPUnit\Event\Test\Prepared as TestPrepared;
use PHPUnit\Runner\CodeCoverage;
use Tests\PHPUnit\AutoCovers\Metadata\MetadataModifier;

class CoverageBasedCoveringTracer implements CoveringTracer
{
	/**
	 * @param (callable(string): class-string) $fileToClassNameMapper
	 * @param (callable(class-string): string) $testToSourcesRootMapper
	 */
	public function __construct(
		private readonly MetadataModifier $metadataModifier,
		private readonly mixed $fileToClassNameMapper,
		private readonly mixed $testToSourcesRootMapper,
	) {}

	public function isAvailable(string $className): bool
	{
		return true;
	}

	public function trace(Event $event): void
	{
		if ($event instanceof TestPrepared) {
			$this->clearCodeCoverage();
		}

		if ($event instanceof TestFinished) {
			$test = $event->test();

			if (!$test instanceof TestMethod) {
				return;
			}

			$coveredClasses = $this->collectCoveredClasses();

			$this->metadataModifier->addCoversClass(
				$test->file(),
				$test->className(),
				$this->filterCoveredClasses($test->className(), $coveredClasses),
			);
		}
	}

	private function clearCodeCoverage(): void
	{
		CodeCoverage::instance()->driver()->stop();

		$codeCoverage = CodeCoverage::instance()->codeCoverage();
		$codeCoverage->start(
			id: (fn () => $this->currentId)->call($codeCoverage),
			size: (fn () => $this->currentSize)->call($codeCoverage),
			clear: true,
		);
	}

	/**
	 * @return class-string[]
	 */
	private function collectCoveredClasses(): array
	{
		$coveredFiles = CodeCoverage::instance()
			->codeCoverage()
			->getData(true)
			->coveredFiles();

		return array_map(($this->fileToClassNameMapper)(...), $coveredFiles);
	}

	/**
	 * @param class-string[] $coveredClasses
	 *
	 * @return class-string[]
	 */
	private function filterCoveredClasses(string $testClassName, array $coveredClasses): array
	{
		$rootNamespace = ($this->testToSourcesRootMapper)($testClassName);

		// Classes should already be loaded (otherwise they wouldn't be covered), but the reason autoload is set to false
		// is to avoid unintentionally loading non-classes (non autoloadable) files which may cause errors.
		return array_filter(
			$coveredClasses,
			fn (string $className) => Str::startsWith($className, $rootNamespace) && class_exists($className, autoload: false)
		);
	}
}
