<?php

namespace Clear01\Widgets;

interface IComponentStateSerializer
{

	/**
	 * This method saves widget's state as string.
	 * @param $widget object|IWidgetComponent
	 * @return string serialized widget state
	 */
	public function serializeWidgetState($widget);

	/**
	 * This method restores widget state form serialized data.
	 * @param $widget object|IWidgetComponent
	 * @param $state string
	 * @return void
	 */
	public function restoreSerializedWidgetState($widget, $state);

}