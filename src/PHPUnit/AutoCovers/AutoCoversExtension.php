<?php

namespace Tests\PHPUnit\AutoCovers;

use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade as ExtensionFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Tests\PHPUnit\AutoCovers\CoveringTracer\ConditionalCoveringTracer;
use Tests\PHPUnit\AutoCovers\CoveringTracer\CoverageBasedCoveringTracer;
use Tests\PHPUnit\AutoCovers\CoveringTracer\CoveringTracer;
use Tests\PHPUnit\AutoCovers\CoveringTracer\SeeAnnotationBasedCoveringTracer;
use Tests\PHPUnit\AutoCovers\CoveringTracer\UnitNamespaceBasedCoveringTracer;
use Tests\PHPUnit\AutoCovers\Metadata\AttributeMetadataModifier;
use Tests\PHPUnit\AutoCovers\PhpDoc\PhpDocStringParser;
use Tests\PHPUnit\AutoCovers\Util\DisableCoverageGenerationSubscriber;
use Tests\PHPUnit\AutoCovers\Util\FirstAvailableCoveringTracer;
use Tests\PHPUnit\AutoCovers\Util\NoCoverageMetadataFilterCoveringTracer;

class AutoCoversExtension implements Extension
{
	public function bootstrap(
		Configuration $configuration,
		ExtensionFacade $facade,
		ParameterCollection $parameters
	): void {
		if (!getenv('AUTO_COVER')) {
			return;
		}

		$facade->requireCodeCoverageCollection();

		$facade->registerSubscriber(new DisableCoverageGenerationSubscriber());
		$facade->registerTracer(
			new NoCoverageMetadataFilterCoveringTracer(
				new FirstAvailableCoveringTracer($this->coveringTracers())
			)
		);
	}

	/**
	 * @return CoveringTracer[]
	 */
	private function coveringTracers(): array
	{
		$metadataModifier = new AttributeMetadataModifier();
		$constExprParser = new ConstExprParser();
		$typeParser = new TypeParser($constExprParser);
		$phpDocParser = new PhpDocParser($typeParser, $constExprParser);
		$phpDocStringParser = new PhpDocStringParser(new Lexer(), $phpDocParser);

		return [
			new ConditionalCoveringTracer(
				$this->unitTestClassNameToSource(...),
				new SeeAnnotationBasedCoveringTracer($metadataModifier, $phpDocStringParser)
			),
			new UnitNamespaceBasedCoveringTracer(
				$metadataModifier,
				$this->unitTestClassNameToSource(...)
			),
			new CoverageBasedCoveringTracer(
				$metadataModifier,
				$this->fileNameToClass(...),
				$this->testToSourcesRoot(...),
			),
		];
	}

	private function unitTestClassNameToSource(string $testClassName): ?string
	{
		if (str_starts_with($testClassName, 'Tests\\Backend\\Unit')) {
			return Str::replaceFirst('Tests\\Backend\\Unit', 'App', $testClassName);
		}

		if (str_contains($testClassName, '\\Tests\\Unit')) {
			return Str::replaceFirst('\\Tests\\Unit', '', $testClassName);
		}

		return null;
	}

	private function testToSourcesRoot(string $testClassName): string
	{
		if (str_starts_with($testClassName, 'Tests\\Backend')) {
			return 'App';
		}

		return Str::before($testClassName, '\\Tests\\');
	}

	private function fileNameToClass(string $fileName): string
	{
		return Str::of($fileName)
			->after(getcwd() . '/')
			->replace('/', '\\')
			->replaceEnd('.php', '')
			->ucfirst()
			->toString();
	}
}
