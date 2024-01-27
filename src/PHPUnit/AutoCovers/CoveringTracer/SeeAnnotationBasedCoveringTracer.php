<?php

namespace Tests\PHPUnit\AutoCovers\CoveringTracer;

use Doctrine\Common\Annotations\TokenParser;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPUnit\Event\Event;
use PHPUnit\Event\TestSuite\Finished as TestSuiteFinished;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
use ReflectionClass;
use Tests\PHPUnit\AutoCovers\Metadata\MetadataModifier;
use Tests\PHPUnit\AutoCovers\PhpDoc\PhpDocStringParser;
use Tests\PHPUnit\AutoCovers\Util\ReflectionHelper;

class SeeAnnotationBasedCoveringTracer implements CoveringTracer
{
	private const CALLABLE_PATTERN = '/^([a-z0-9\\_]+)(::|@)([a-z0-9_]+)/i';

	public function __construct(
		private readonly MetadataModifier $metadataModifier,
		private readonly PhpDocStringParser $phpDocStringParser,
	) {}

	public function isAvailable(string $className): bool
	{
		return str_contains(ReflectionHelper::classDocComment($className) ?? '', '@see ');
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

		$seeReferences = $this->parseSeeReferences($testSuite->className());

		[$classes, $functions] = $this->groupSeeReferencesByType($seeReferences);

		$this->metadataModifier->addCoversClass($testSuite->file(), $testSuite->className(), $classes);
		$this->metadataModifier->addCoversFunction($testSuite->file(), $testSuite->className(), $functions);
	}

	/**
	 * @param class-string $className
	 *
	 * @return string[]
	 */
	private function parseSeeReferences(string $className): array
	{
		$reflection = new ReflectionClass($className);

		$namespace = preg_quote($reflection->getNamespaceName());
		$content = file_get_contents($reflection->getFileName());
		$content = preg_replace('/^.*?(\bnamespace\s+' . $namespace . '\s*[;{].*)$/s', '\\1', $content);
		$tokenizer = new TokenParser('<?php ' . $content);

		$uses = $tokenizer->parseUseStatements($reflection->getNamespaceName());

		$phpDoc = $this->phpDocStringParser->parse($reflection);

		return array_map(
			function (PhpDocTagNode $tag) use ($uses) {
				$value = (string) $tag->value;

				if (preg_match(self::CALLABLE_PATTERN, $value, $matches)) {
					$value = $matches[1];
				}

				if (str_starts_with($value, '\\')) {
					return $value;
				}

				return $uses[mb_strtolower($value)] ?? $value;
			},
			$phpDoc->getTagsByName('@see')
		);
	}

	/**
	 * @return array{ class-string[], string[] }
	 */
	private function groupSeeReferencesByType(array $seeReferences): array
	{
		$classes = [];
		$functions = [];

		foreach ($seeReferences as $seeReference) {
			if (function_exists($seeReference)) {
				$functions[] = $seeReference;

				continue;
			}

			if (class_exists($seeReference)) {
				$classes[] = $seeReference;
			}
		}

		return [$classes, $functions];
	}
}
