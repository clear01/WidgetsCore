<?php

namespace Clear01Tests\Widgets;

use Clear01\Widgets\WidgetDeclaration;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class WidgetDeclarationTest extends TestCase
{

	public function testGetters() {
		$declaration = new WidgetDeclaration(
			$widgetTypeId = "typeId123",
			$unique = true,
			function() { }
		);

		Assert::equal("typeId123", $declaration->getWidgetTypeId());
		Assert::equal(true, $declaration->isUnique());
	}

	public function testInstanceCreation() {
		$declaration = new WidgetDeclaration(
			$widgetTypeId = "typeId123",
			$unique = true,
			function() { }
		);

		Assert::exception(function() use ($declaration) {
			$declaration->createInstance();
		}, \Exception::class);

		$createdObject = null;
		$declaration = new WidgetDeclaration(
			$widgetTypeId = "typeId123",
			$unique = true,
			function() use (&$createdObject) { return ($createdObject = new \stdClass); }
		);

		Assert::equal(null, $createdObject);
		$createdInstance = $declaration->createInstance();
		Assert::notSame(null, $createdInstance);
		Assert::same($createdInstance, $createdObject);
	}

}

\run(new WidgetDeclarationTest());