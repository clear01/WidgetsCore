<?php

namespace Clear01Tests\Widgets;

use Clear01\Widgets\IComponentStateSerializer;
use Clear01\Widgets\IUserIdentityAccessor;
use Clear01\Widgets\IWidgetPersister;
use Clear01\Widgets\WidgetDeclaration;
use Clear01\Widgets\WidgetManager;
use Mockery\Mock;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class WidgetManagerTest extends TestCase
{
	protected function createWidgetManager(&$userIdentityAccessor = null, &$persister = null, &$stateSerializer = null) {
		$manager = new WidgetManager(
			$userIdentityAccessor = \Mockery::mock(IUserIdentityAccessor::class),
			$persister = \Mockery::mock(IWidgetPersister::class),
			$stateSerializer = \Mockery::mock(IComponentStateSerializer::class)
		);
		return $manager;
	}

	public function testWidgetFiltering() {
		/** @var Mock $persisterMock */
		/** @var Mock $userIdentityAccessorMock */
		$persisterMock = $userIdentityAccessorMock = null;

		$manager = $this->createWidgetManager($userIdentityAccessorMock, $persisterMock);
		$instance1 = null;
		$widgetDeclaration1 = new WidgetDeclaration('type1', false, function() use ($instance1) {
			return ($instance1 = new \stdClass());
		});
		$manager->addWidgetDeclaration($widgetDeclaration1);

		$instance2 = null;
		$widgetDeclaration2 = new WidgetDeclaration('type2', false, function() use ($instance2) {
			return ($instance2 = new \stdClass());
		});
		$manager->addWidgetDeclaration($widgetDeclaration2);

		$manager->addWidgetFactory($declarationFactory = new DummyDeclarationFactory(['type3', 'type4'], false));

		$typeWhitelist = ['type2', 'type4'];

		$manager->addWidgetDeclarationFilterCallback(function(&$widgetDeclarations) use ($typeWhitelist) {
			$filtered = [];
			/** @var WidgetDeclaration $widgetDeclaration */
			foreach($widgetDeclarations as $widgetDeclaration) {
				if(in_array($widgetDeclaration->getWidgetTypeId(), $typeWhitelist)){
					$filtered[] = $widgetDeclaration;
				}
			}
			$widgetDeclarations = $filtered;
		});

		$userIdentityAccessorMock->shouldReceive('getUserId')->andReturn(1);

		$persisterMock->shouldReceive('getSingleWidget')->with($widget1Id = 12345)->once()->andReturn($record1 = new DummyWidgetRecord($widget1Id, 'type1', '', 1, 1));
		$persisterMock->shouldReceive('getSingleWidget')->with($widget2Id = 23456)->once()->andReturn($record2 = new DummyWidgetRecord($widget2Id, 'type4', '', 2, 1));
		$persisterMock->shouldReceive('getUserWidgetRecords')->andReturn([$record1, $record2]);

		$availableWidgets = $manager->getAvailableWidgets();
		Assert::equal($typeWhitelist, array_keys($availableWidgets));

		Assert::exception(function() use ($manager, $widget1Id) {
			$manager->getSingleWidgetInstance($widget1Id);
		}, \Exception::class);

		Assert::equal([$widget2Id], $manager->getUserWidgetIds());

		Assert::same($manager->getSingleWidgetInstance($widget2Id), $declarationFactory->getInstance('type4'));
	}

	public function testUniqueness() {
		/** @var Mock $persisterMock */
		/** @var Mock $userIdentityAccessorMock */
		$persisterMock = $userIdentityAccessorMock = null;

		$manager = $this->createWidgetManager($userIdentityAccessorMock, $persisterMock);

		$instance1 = null;
		$widgetDeclaration1 = new WidgetDeclaration('type1', false, function() use ($instance1) {
			return ($instance1 = new \stdClass());
		});
		$manager->addWidgetDeclaration($widgetDeclaration1);

		$instance2 = null;
		$widgetDeclaration2 = new WidgetDeclaration('type2', true, function() use ($instance2) {
			return ($instance2 = new \stdClass());
		});
		$manager->addWidgetDeclaration($widgetDeclaration2);

		$userIdentityAccessorMock->shouldReceive('getUserId')->andReturn(1);

		$persisterMock->shouldReceive('getSingleWidget')->with($widget1Id = 12345)->once()->andReturn($record1 = new DummyWidgetRecord($widget1Id, 'type1', '', 1, 1));
		$persisterMock->shouldReceive('getSingleWidget')->with($widget2Id = 23456)->once()->andReturn($record2 = new DummyWidgetRecord($widget2Id, 'type2', '', 2, 1));
		$persisterMock->shouldReceive('getUserWidgetRecords')->andReturn([$record1, $record2]);

		$availableWidgets = $manager->getAvailableWidgets();

		Assert::equal(['type1'], array_keys($availableWidgets));
	}

	public function testContextPrefix()
	{
		/** @var Mock $persisterMock */
		/** @var Mock $userIdentityAccessorMock */
		$persisterMock = $userIdentityAccessorMock = null;

		$manager = $this->createWidgetManager($userIdentityAccessorMock, $persisterMock);

		$userIdentityAccessorMock->shouldReceive('getUserId')->andReturn(1);

		$context = null;
		$persisterMock->shouldReceive('getUserWidgetRecords')->andReturnUsing(function ($val) use (&$context) {
			$context = $val;
			return [];
		});

		$manager->setContextPrefix('context1');
		$manager->getUserWidgetIds();
		Assert::contains('context1', $context);

		$manager->setContextPrefix('context2');
		$manager->getUserWidgetIds();
		Assert::contains('context2', $context);
	}

	public function testDelegation() {
		/** @var Mock $persisterMock */
		/** @var Mock $userIdentityAccessorMock */
		$manager = new WidgetManager(
			$userIdentityAccessorMock = \Mockery::mock(IUserIdentityAccessor::class),
			$persisterMock = \Mockery::spy(IWidgetPersister::class),
			$stateSerializerMock = \Mockery::spy(IComponentStateSerializer::class)
		);

		$manager->addWidgetDeclaration(new WidgetDeclaration('type1', false, function() {
			return new \stdClass();
		}));
		$persisterMock->shouldReceive('getSingleWidget')->with($widget1Id = 12345)->once()->andReturn($record1 = new DummyWidgetRecord($widget1Id, 'type1', 'asd', 1, 1));
		$persisterMock->shouldReceive('getSingleWidget')->with($widget2Id = 1234)->once()->andReturn($record2 = new DummyWidgetRecord($widget2Id, 'type1', 'asd', 2, 1));
		$persisterMock->shouldReceive('getUserWidgetRecords')->andReturn([$record1]);
		$userIdentityAccessorMock->shouldReceive('getUserId')->andReturn(1);

		// ---

		$manager->getUserWidgetIds();
		$persisterMock->shouldHaveReceived('getUserWidgetRecords');

		Assert::exception( function() use ($manager) { $manager->getSingleWidgetInstance(123456); }, \Exception::class);
		$persisterMock->shouldHaveReceived('getSingleWidget');

		Assert::noError( function() use ($manager) { $manager->getSingleWidgetInstance(12345); });
		$stateSerializerMock->shouldHaveReceived('restoreSerializedWidgetState');

		$manager->saveWidgetState(12345, new \stdClass());
		$stateSerializerMock->shouldHaveReceived('serializeWidgetState');
		$persisterMock->shouldHaveReceived('saveWidgetState');

		$persisterMock->shouldReceive('insertWidgetRecord')->andReturn(new DummyWidgetRecord($idToInsert = 938, 'type1', 'dsa', 3, 'anotherContext'));
		$insertedId = $manager->insertWidget('type1');
		$persisterMock->shouldHaveReceived('insertWidgetRecord');
		Assert::equal($insertedId, $idToInsert);

		$manager->removeWidget(12345);
		$persisterMock->shouldHaveReceived('removeWidgetRecord');

		$manager->moveWidgetBefore(12345, 1234);
		$persisterMock->shouldHaveReceived('moveWidgetBefore');
	}

}

\run(new WidgetManagerTest());