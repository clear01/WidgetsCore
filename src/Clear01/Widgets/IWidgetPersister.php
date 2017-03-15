<?php

namespace Clear01\Widgets;

/**
 * This interface defines storing, loading and managing particular widget instance configurations.
 */
interface IWidgetPersister
{
	/**
	 * @param $context string|int Widget context (ex.: user identity)
	 * @return IWidgetRecord[]
	 */
	public function getUserWidgetRecords($context);

	/**
	 * @param $widgetId string ID of the widget instance
	 * @return IWidgetRecord|null
	 */
	public function getSingleWidget($widgetId);

	/**
	 * This method adds available widget to user section.
	 * @param $widgetTypeId string ID of the widget type.
	 * @param $context string|int Widget context (ex.: user identity)
	 * @param $insertBeforeWidgetId string|null ID of the widget to be the new widget inserted before. NULL ~= insert at the end
	 * @return IWidgetRecord inserted widget record instance
	 */
	public function insertWidgetRecord($widgetTypeId, $context, $insertBeforeWidgetId = null);

	/**
	 * This method removes saved widget from user section.
	 * @param $widgetId string ID of the widget instance
	 * @return void
	 */
	public function removeWidgetRecord($widgetId);

	/**
	 * This method changes the widget order.
	 * @param $widgetId string ID of the widget instance to be moved
	 * @param $relatedWidgetId string|null ID of the insertion position reference widget instance. If null, widget will be moved at the end.
	 * @return mixed
	 */
	public function moveWidgetBefore($widgetId, $relatedWidgetId = null);

	/**
	 * This method should be called to persist widget's internal state.
	 * @param $widgetId string ID of the widget instance
	 * @param $state string serialized widget state.
	 * @return void
	 */
	public function saveWidgetState($widgetId, $state);

}