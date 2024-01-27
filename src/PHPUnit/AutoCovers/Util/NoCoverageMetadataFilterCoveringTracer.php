<?php

namespace Tests\PHPUnit\AutoCovers\Util;

use PHPUnit\Event\Event;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Metadata\Parser\Registry;

class NoCoverageMetadataFilterCoveringTracer implements Tracer
{
	public function __construct(
		private readonly Tracer $delegate,
	) {}

	public function trace(Event $event): void
	{
		$testClassName = TestEventsHelper::testClassName($event);

		if (!$testClassName) {
			return;
		}

		$metadata = Registry::parser()->forClass($testClassName);

		if (
			$metadata->isCovers()->isNotEmpty() ||
			$metadata->isCoversClass()->isNotEmpty() ||
			$metadata->isCoversFunction()->isNotEmpty() ||
			$metadata->isCoversNothing()->isNotEmpty()
		) {
			return;
		}

		$this->delegate->trace($event);
	}
}
