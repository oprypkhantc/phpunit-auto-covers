<?php

namespace Tests\PHPUnit\AutoCovers\CoveringTracer;

use PHPUnit\Event\Event;

class ConditionalCoveringTracer implements CoveringTracer
{
	/**
	 * @param (callable(class-string): bool) $condition
	 */
	public function __construct(
		private readonly mixed $condition,
		private readonly CoveringTracer $delegate,
	) {}

	public function isAvailable(string $className): bool
	{
		return ($this->condition)($className) && $this->delegate->isAvailable($className);
	}

	public function trace(Event $event): void
	{
		$this->delegate->trace($event);
	}
}
