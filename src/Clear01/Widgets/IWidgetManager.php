<?php

namespace Clear01\Widgets;

/**
 * This is the interface that the application should use to access widgets API.
 */
interface IWidgetManager
{
	/**
	 * This method should return all available widgets to be used.
	 * @return object[]|IWidgetComponent[] Array - [widgetTypeId => widgetInstance]
	 */
	public function getAvailableWidgets();

	/**
	 * This method returns all active widget ids (user widgets).
	 * @return string[] Array of user widget ids
	 */
	public function getUserWidgetIds();

	/**
	 * This method adds available widget to user section.
	 * @param $widgetTypeId string ID of the widget type.
	 * @param $insertBeforeWidgetId string|null ID of the widget to be the new widget inserted before. NULL ~= insert at the end
	 * @return string ID of the inserted widget instance
	 */
	public function insertWidget($widgetTypeId, $insertBeforeWidgetId = null);

	/**
	 * This method removes saved widget from user section.
	 * @param $widgetId string ID of the widget instance
	 * @return void
	 */
	public function removeWidget($widgetId);

	/**
	 * This method changes the widget order.
	 * @param $widgetId string ID of the widget instance to be moved
	 * @param $relatedWidgetId string ID of the insertion position reference widget instance.
	 * @return mixed
	 */
	public function moveWidgetBefore($widgetId, $relatedWidgetId);

	/**
	 * This method should be called to persist widget's internal state.
	 * @param $widgetId string ID of the widget instance
	 * @param object $widget Widget component.
	 * @return void
	 */
	public function saveWidgetState($widgetId, $widget);

	/**
	 * This method returns single widget instance.
	 * @param $widgetId string ID of the widget instance
	 * @return object|IWidgetComponent
	 */
	public function getSingleWidgetInstance($widgetId);

}