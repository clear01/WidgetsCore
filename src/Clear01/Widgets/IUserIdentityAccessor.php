<?php

namespace Clear01\Widgets;

interface IUserIdentityAccessor
{
	/** @return string|int */
	public function getUserId();
}