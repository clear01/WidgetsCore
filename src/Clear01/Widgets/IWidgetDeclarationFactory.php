<?php

namespace Clear01\Widgets;

/**
 * This class is used for smart widget creation. It's supposed to create one ore more widget declarations.
 *
 * Implementations should be opened to extending, but closed to reducing returned declarations.
 * It means once some declaration is returned, a declaration with given type ID should be always returned anytime the create method is called in the future.
 */
interface IWidgetDeclarationFactory
{
	/** @return WidgetDeclaration|WidgetDeclaration[] */
	public function create();
}