<?php

namespace Clear01\Widgets;


class WidgetDeclaration
{
	/** @var  string Unique ID of the widget type */
	protected $widgetTypeId;

	/** @var  bool True if user can only have 1 instance of the widget type active at once. */
	protected $unique;

	/** @var callable that returns \Nette\Application\UI\Control|IWidget */
	protected $controlFactory;

	/**
	 * WidgetDeclaration constructor.
	 * @param string $widgetTypeId Unique ID of the widget type
	 * @param bool $unique True if user can only have 1 instance of the widget type active at once.
	 * @param callable $controlFactory callable that returns \Nette\Application\UI\Control|IWidget
	 */
	public function __construct($widgetTypeId, $unique, callable $controlFactory)
	{
		$this->widgetTypeId = $widgetTypeId;
		$this->unique = $unique;
		$this->controlFactory = $controlFactory;
	}


	/**
	 * @return string
	 */
	public function getWidgetTypeId()
	{
		return $this->widgetTypeId;
	}

	/**
	 * @return boolean
	 */
	public function isUnique()
	{
		return $this->unique;
	}

	/**
	 * @return IWidgetComponent|object
	 */
	public function createInstance()
	{
		$controlInstance = call_user_func($this->controlFactory);
		if(!is_object($controlInstance)) {
			throw new \RuntimeException('Instance of UI control must be returned from factory callback. Haven\'t you forgot to use the return statement?');
		}
		return $controlInstance;
	}

}