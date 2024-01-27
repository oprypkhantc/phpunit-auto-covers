<?php

namespace Tests\PHPUnit\AutoCovers\Util;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Event;
use PHPUnit\Event\Test\Finished as TestFinished;
use PHPUnit\Event\Test\Prepared as TestPrepared;
use PHPUnit\Event\TestSuite\Finished as TestSuiteFinished;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;

class TestEventsHelper
{
	public static function testClassName(Event $event): ?string
	{
		return match (true) {
			$event instanceof TestPrepared && $event->test() instanceof TestMethod                      => $event->test()->className(),
			$event instanceof TestFinished && $event->test() instanceof TestMethod                      => $event->test()->className(),
			$event instanceof TestSuiteFinished && $event->testSuite() instanceof TestSuiteForTestClass => $event->testSuite()->className(),
			default                                                                                     => null,
		};
	}
}
