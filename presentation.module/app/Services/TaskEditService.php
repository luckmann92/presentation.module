<?php

namespace PresentModule\App\Services;

use Laravel\Illuminate\App\Services\Service;
use PresentModule\App\Models\Group\Category;

class TaskEditService extends Service
{
	public const DEFAULT_STAGES = [
		[
			'TITLE' => 'Новые',
			'SORT' => 100,
			'COLOR' => '00C4FB',
		],
		[
			'TITLE' => 'Выполняются',
			'SORT' => 200,
			'COLOR' => '47D1E2',
		],
		[
			'TITLE' => 'Сделаны',
			'SORT' => 300,
			'COLOR' => '75D900',
		],
	];

	public function make()
	{
		return Category::relationships()->whereHas('children')->get()->toArray();
	}
}
