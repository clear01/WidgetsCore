<?php

namespace Clear01\Widgets;

/**
 * This interface should be implemented by the entity that particular widget persister works with.
 */
interface IWidgetRecord
{

	/**
	 * @return string ID of the widget type
	 */
	public function getWidgetTypeId();

	/**
	 * @return string ID of the particular widget instance
	 */
	public function getWidgetId();

	/**
	 * @return string serialized widget state
	 */
	public function getWidgetState();

	/**
	 * @return int zero-based position
	 */
	public function getWidgetPosition();

	/**
	 * @return string|int Identity of the user
	 */
	public function getWidgetContext();

}