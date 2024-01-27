<?php

namespace Tests\PHPUnit\AutoCovers\CoveringTracer;

use PHPUnit\Event\Tracer\Tracer;

interface CoveringTracer extends Tracer
{
	public function isAvailable(string $className): bool;
}
