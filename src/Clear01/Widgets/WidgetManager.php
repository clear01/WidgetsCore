<?php

namespace Clear01\Widgets;

class WidgetManager implements IWidgetManager
{
	/** @var IUserIdentityAccessor */
	protected $userIdentityAccessor;

	/** @var  IWidgetPersister */
	protected $widgetPersister;

	/** @var  IComponentStateSerializer */
	protected $componentStateSerializer;

	// ---

	/** @var  WidgetDeclaration[] */
	protected $widgetDeclarations = [];

	/** @var IWidgetDeclarationFactory[] */
	protected $widgetFactories = [];

	/** @var callable[] */
	protected $widgetDeclarationFilterCallbacks = [];

	/** @var string Context prefix to be used by persister */
	protected $contextPrefix = '';

	/** @var bool If factories were called */
	private $widgetDeclarationsLocked = false;

	/** @var [] Ids of the widget types that have already been filtered. Prevention from redundant $widgetDeclarationFilterCallbacks calls */
	protected $userWidgetTypeIdsWhitelist = [];

	/**
	 * WidgetManager constructor.
	 * @param IUserIdentityAccessor $userIdentityAccessor
	 * @param IWidgetPersister $widgetPersister
	 * @param IComponentStateSerializer $componentStateSerializer
	 */
	public function __construct(IUserIdentityAccessor $userIdentityAccessor, IWidgetPersister $widgetPersister, IComponentStateSerializer $componentStateSerializer)
	{
		$this->userIdentityAccessor = $userIdentityAccessor;
		$this->widgetPersister = $widgetPersister;
		$this->componentStateSerializer = $componentStateSerializer;
	}

	public function addWidgetDeclaration(WidgetDeclaration $declaration) {
		if($this->widgetDeclarationsLocked) {
			throw new \RuntimeException('At the moment, no other widget declarations can be used. Try to add your declarations earlier.');
		}
		$this->widgetDeclarations[] = $declaration;
	}

	public function addWidgetFactory(IWidgetDeclarationFactory $widgetFactory) {
		if($this->widgetDeclarationsLocked) {
			throw new \RuntimeException('At the moment, no other widget factories can be added. Try to add your factories earlier.');
		}
		$this->widgetFactories[] = $widgetFactory;
	}

	public function addWidgetDeclarationFilterCallback(callable $callback) {
		$this->widgetDeclarationFilterCallbacks[] = $callback;
	}

	protected function invokeWidgetFactories() {
		foreach($this->widgetFactories as $widgetFactory) {
			$created = $widgetFactory->create();
			if (!is_array($created)) {
				$created = [$created];
			}
			foreach ($created as $declaration) {
				if (!($declaration instanceof WidgetDeclaration)) {
					throw new \InvalidArgumentException('Declarations provided must be of type ' . WidgetDeclaration::class);
				}
				$this->widgetDeclarations[] = $declaration;
			}
		}
	}

	protected function lockDeclarations($ignoreAlreadyLocked = true) {
		if(!$ignoreAlreadyLocked && $this->widgetDeclarationsLocked) {
			throw new \RuntimeException('Widgets are already locked');
		}
		$this->invokeWidgetFactories();
		$this->widgetDeclarationsLocked = true;
	}

	/**
	 * This method should return all available widgets to be used.
	 * @return object|IWidgetComponent[] Array - [widgetTypeId => widgetInstance]
	 */
	public function getAvailableWidgets()
	{
		$this->lockDeclarations();

		$instances = [];

		$userWidgetTypeIds = $this->getUserWidgetTypeIds();

		$declarations = $this->filterDeclarations($this->widgetDeclarations);

		foreach($declarations as $widgetDeclaration) {
			if($widgetDeclaration->isUnique() && in_array($widgetDeclaration->getWidgetTypeId(), $userWidgetTypeIds)) {
				// skip already used unique widget
				continue;
			}
			$instances[$widgetDeclaration->getWidgetTypeId()] = $widgetDeclaration->createInstance();
		}

		return $instances;
	}

	/**
	 * This method returns all active widget ids (user widgets).
	 * @return string[] Array of user widget ids
	 */
	public function getUserWidgetIds()
	{
		$this->lockDeclarations();

		$widgetIds = [];
		/** @var WidgetDeclaration[] $declarationByWidgetTypeId */
		$declarationByWidgetTypeId = [];

		$widgetRecords = $this->fetchUserWidgetRecords();

		// load & filter declarations
		foreach($widgetRecords as $widgetRecord) {
			if(!isset($declarationByWidgetTypeId[$widgetRecord->getWidgetTypeId()])) {
				$declarationByWidgetTypeId[$widgetRecord->getWidgetTypeId()] = $this->getWidgetDeclarationByTypeId($widgetRecord->getWidgetTypeId());
			}
		}
		$filteredDeclarations = $this->filterDeclarations(array_values($declarationByWidgetTypeId));
		$declarationByWidgetTypeId = [];
		foreach($filteredDeclarations as $widgetDeclaration) {
			$declarationByWidgetTypeId[$widgetDeclaration->getWidgetTypeId()] = $widgetDeclaration;
		}

		foreach($widgetRecords as $widgetRecord) {
			if(!isset($declarationByWidgetTypeId[$widgetRecord->getWidgetTypeId()])){
				continue; // this declaration was filtered
			}
			$widgetIds[] = $widgetRecord->getWidgetId();
		}

		$this->userWidgetTypeIdsWhitelist = array_unique(array_merge($this->userWidgetTypeIdsWhitelist, array_keys($declarationByWidgetTypeId)));

		return $widgetIds;
	}

	public function insertWidget($widgetTypeId, $insertBeforeWidgetId = null)
	{
		$this->lockDeclarations();
		$declaration = $this->getWidgetDeclarationByTypeId($widgetTypeId);
		if(count($this->filterDeclarations([$declaration])) == 0) {
			throw new \InvalidArgumentException('Widget with typeId ' . $widgetTypeId . ' has been filtered in current context.');
		}
		if($insertBeforeWidgetId) {
			$this->fetchSingleWidgetRecord($insertBeforeWidgetId);
		}
		$record = $this->widgetPersister->insertWidgetRecord($widgetTypeId, $this->getContext(), $insertBeforeWidgetId);
		return $record->getWidgetId();
	}


	/**
	 * This method removes saved widget from user section.
	 * @param $widgetId string ID of the widget instance
	 * @return void
	 */
	public function removeWidget($widgetId)
	{
		$this->lockDeclarations();
		$this->fetchSingleWidgetRecord($widgetId);
		$this->widgetPersister->removeWidgetRecord($widgetId);
	}

	/**
	 * This method changes the widget order.
	 * @param $widgetId string ID of the widget instance to be moved
	 * @param $relatedWidgetId string ID of the insertion position reference widget instance.
	 * @return mixed
	 */
	public function moveWidgetBefore($widgetId, $relatedWidgetId)
	{
		$this->lockDeclarations();
		$this->fetchSingleWidgetRecord($widgetId);
		if($relatedWidgetId) {
			$this->fetchSingleWidgetRecord($relatedWidgetId);
		}
		$this->widgetPersister->moveWidgetBefore($widgetId, $relatedWidgetId);
	}

	/**
	 * This method should be called to persist widget's internal state.
	 * @param $widgetId string ID of the widget instance
	 * @param object $widget Widget component.
	 * @return void
	 */
	public function saveWidgetState($widgetId, $widget)
	{
		$this->lockDeclarations();
		$this->fetchSingleWidgetRecord($widgetId);
		$this->widgetPersister->saveWidgetState($widgetId, $this->componentStateSerializer->serializeWidgetState($widget));
	}

	/**
	 * This method returns single widget instance.
	 * @param $widgetId string ID of the widget instance
	 * @return object|IWidgetComponent
	 */
	public function getSingleWidgetInstance($widgetId)
	{
		$this->lockDeclarations();
		$widgetRecord = $this->fetchSingleWidgetRecord($widgetId);
		$widgetDeclaration = $this->getWidgetDeclarationByTypeId($widgetRecord->getWidgetTypeId());
		if(!count($this->filterDeclarations([$widgetDeclaration]))) {
			throw new \InvalidArgumentException(sprintf('Given widget type (%s) has been disabled.', $widgetDeclaration->getWidgetTypeId()));
		}
		$widgetInstance = $widgetDeclaration->createInstance();
		if($widgetRecord->getWidgetState()) {
			$this->componentStateSerializer->restoreSerializedWidgetState($widgetInstance, $widgetRecord->getWidgetState());
		}
		return $widgetInstance;
	}

	protected function getWidgetDeclarationByTypeId($widgetTypeId){
		foreach($this->widgetDeclarations as $declaration) {
			if($declaration->getWidgetTypeId() == $widgetTypeId) {
				return $declaration;
			}
		}
		throw new \InvalidArgumentException('Widget declaration with widget type id ' . $widgetTypeId . ' cannot be found.');
	}

	/** @return string[] */
	protected function getUserWidgetTypeIds() {
		$ids = [];
		foreach($this->widgetPersister->getUserWidgetRecords($this->getContext()) as $widgetRecord) {
			$ids[] = $widgetRecord->getWidgetTypeId();
		}
		return array_unique($ids);
	}

	/**
	 * @param $declarations WidgetDeclaration[]
	 * @return WidgetDeclaration[]
	 */
	protected function filterDeclarations($declarations) {
		// redundant filter call prevention
		if(count($declarations) == 1 && isset($declarations[0]) && in_array($declarations[0]->getWidgetTypeId(), $this->userWidgetTypeIdsWhitelist)) {
			return $declarations;
		}
		foreach($this->widgetDeclarationFilterCallbacks as $widgetDeclarationFilterCallback) {
			call_user_func_array($widgetDeclarationFilterCallback, [&$declarations]);
		}
		return $declarations;
	}

	protected function fetchUserWidgetRecords() {
		$widgetRecords = $this->widgetPersister->getUserWidgetRecords($this->getContext());

		usort($widgetRecords, function(IWidgetRecord $a, IWidgetRecord $b) {
			return $a->getWidgetPosition() - $b->getWidgetPosition();
		});

		return $widgetRecords;
	}

	protected function fetchSingleWidgetRecord($widgetId, $checkPermissions = true)
	{
		$widget = $this->widgetPersister->getSingleWidget($widgetId);
		if(!$widget) {
			throw new \InvalidArgumentException(sprintf('Widget with ID "%s" does not exist!', $widgetId));
		}
		if($checkPermissions) {
			if ($widget->getWidgetContext() != ($currentContext = $this->getContext())) {
				throw new \InvalidArgumentException(
					sprintf('Widget with ID "%s" cannot be managed in context "%s", because it was created in context "%s"!', $widgetId, $currentContext, $widget->getWidgetContext())
				);
			}
		}
		return $widget;
	}

	protected function getContext() {
		return $this->contextPrefix . $this->userIdentityAccessor->getUserId();
	}

	/**
	 * @return string Context prefix to be used by persister
	 */
	public function getContextPrefix()
	{
		return $this->contextPrefix;
	}

	/**
	 * @param string $contextPrefix Context prefix to be used by persister
	 */
	public function setContextPrefix($contextPrefix)
	{
		$this->contextPrefix = $contextPrefix;
	}

}