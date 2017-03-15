<?php

namespace Clear01Tests\Widgets;

use Clear01\Widgets\IWidgetRecord;

class DummyWidgetRecord implements IWidgetRecord {

	protected $widgetId;

	protected $widgetTypeId;

	protected $widgetState;

	protected $widgetPosition;

	protected $widgetContext;

	/**
	 * DummyWidgetRecord constructor.
	 * @param $widgetId
	 * @param $widgetTypeId
	 * @param $widgetState
	 * @param $widgetPosition
	 * @param $widgetContext
	 */
	public function __construct($widgetId, $widgetTypeId, $widgetState, $widgetPosition, $widgetContext)
	{
		$this->widgetId = $widgetId;
		$this->widgetTypeId = $widgetTypeId;
		$this->widgetState = $widgetState;
		$this->widgetPosition = $widgetPosition;
		$this->widgetContext = $widgetContext;
	}

	/**
	 * @return mixed
	 */
	public function getWidgetId()
	{
		return $this->widgetId;
	}

	/**
	 * @return mixed
	 */
	public function getWidgetTypeId()
	{
		return $this->widgetTypeId;
	}

	/**
	 * @return mixed
	 */
	public function getWidgetState()
	{
		return $this->widgetState;
	}

	/**
	 * @return mixed
	 */
	public function getWidgetPosition()
	{
		return $this->widgetPosition;
	}

	/**
	 * @return mixed
	 */
	public function getWidgetContext()
	{
		return $this->widgetContext;
	}

}