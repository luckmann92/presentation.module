<?php
namespace PresentModule\App\Handlers;

use PresentModule\App\Models\Group\Type;

class GroupHandler
{
	public static function onSocNetGroupAdd($id, $fields)
	{
		if (!$fields['UF_GROUP_TYPE']) {
			return true;
		}

		$groupType = Type::with('stages')->find($fields['UF_GROUP_TYPE']);

		$sort = 100;
		foreach ($groupType->stages as $stage) {
			\Laravel\Illuminate\App\Models\B24\Task\TaskStage::create([
				'TITLE' => $stage->UF_NAME,
				'SORT' => $sort,
				'COLOR' => sprintf("%06X", mt_rand(0, 0xFFFFFF)),
				'ENTITY_ID' => $id,
				'ENTITY_TYPE' => 'G',
			]);

			$sort += 100;
		}
	}
}
