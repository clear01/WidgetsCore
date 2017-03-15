<?php

namespace Clear01Tests\Widgets;

use Clear01\Widgets\IWidgetDeclarationFactory;
use Clear01\Widgets\WidgetDeclaration;

class DummyDeclarationFactory implements IWidgetDeclarationFactory {

	protected $ids;

	protected $unique;

	protected $instances;

	/**
	 * DummyDeclarationFactory constructor.
	 * @param $ids
	 * @param $unique
	 */
	public function __construct($ids, $unique)
	{
		$this->ids = $ids;
		$this->unique = $unique;
	}

	/** @return WidgetDeclaration|WidgetDeclaration[] */
	public function create()
	{
		$declarations = [];
		foreach($this->ids as $id) {
			$declarations[] = new WidgetDeclaration(
				$id,
				$this->unique,
				function() use ($id) {
					return ($this->instances[$id] = new \stdClass());
				}
			);
		}
		return $declarations;
	}

	public function getInstance($id) {
		if(!isset($this->instances[$id])) {
			throw new \InvalidArgumentException('Invalid instance id');
		}
		return $this->instances[$id];
	}
}