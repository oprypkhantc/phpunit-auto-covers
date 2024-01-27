<?php

namespace Tests\PHPUnit\AutoCovers\Util;

use PHPUnit\Event\Event;
use PHPUnit\Event\Tracer\Tracer;
use Tests\PHPUnit\AutoCovers\CoveringTracer\CoveringTracer;

class FirstAvailableCoveringTracer implements Tracer
{
	/**
	 * @param CoveringTracer[] $tracers
	 */
	public function __construct(
		private readonly array $tracers,
	) {}

	public function trace(Event $event): void
	{
		$testClassName = TestEventsHelper::testClassName($event);

		if (!$testClassName) {
			return;
		}

		foreach ($this->tracers as $tracer) {
			if (!$tracer->isAvailable($testClassName)) {
				continue;
			}

			$tracer->trace($event);

			break;
		}
	}
}
