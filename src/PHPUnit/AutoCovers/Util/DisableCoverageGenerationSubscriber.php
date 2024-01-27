<?php

namespace Tests\PHPUnit\AutoCovers\Util;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use PHPUnit\Runner\CodeCoverage;

class DisableCoverageGenerationSubscriber implements FinishedSubscriber
{
	public function notify(Finished $event): void
	{
		// Skip generation of coverage as the purpose of it being active is solely to collect coverage, not to display it.
		CodeCoverage::instance()->driver()->stop();
		CodeCoverage::instance()->deactivate();
	}
}
