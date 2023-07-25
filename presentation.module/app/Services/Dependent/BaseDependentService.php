<?php

namespace PresentModule\App\Services\Dependent;

use Laravel\Illuminate\App\Services\Service;

class BaseDependentService extends Service
{
	public function fields()
	{
		return [];
	}

	public function filterFields()
	{
		return [];
	}

	public function rows()
	{
		return [];
	}

	public function gridParams()
	{
		return [
			'HEAD_NAV' => false,
			'FILTER' => false,
			'BUTTONS' => false,
			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_ROW_CHECKBOXES'       => false,
			'SHOW_ROW_ACTIONS_MENU'     => false,
			'SHOW_GRID_SETTINGS_MENU'   => true,
			'SHOW_NAVIGATION_PANEL'     => false,
			'SHOW_PAGINATION'           => false,
			'SHOW_SELECTED_COUNTER'     => false,
			'SHOW_TOTAL_COUNTER'        => false,
			'SHOW_PAGESIZE'             => false,
			'SHOW_ACTION_PANEL'         => false,
			'ALLOW_COLUMNS_SORT'        => false,
			'ALLOW_COLUMNS_RESIZE'      => false,
			'ALLOW_HORIZONTAL_SCROLL'   => true,
			'ALLOW_SORT'                => false,
			'ALLOW_PIN_HEADER'          => true,
		];
	}
}
