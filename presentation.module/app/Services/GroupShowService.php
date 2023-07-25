<?php

namespace PresentModule\App\Services;

use Laravel\Illuminate\App\Repositories\EloquentRepository;
use Laravel\Illuminate\App\Services\Service;
use PresentModule\App\Models\Group\Group;
use PresentModule\App\Models\Object\ConstructionObject;
use PresentModule\App\Services\Group\StageService;
use PresentModule\App\Services\Task\FieldSectionService;

class GroupShowService extends Service
{
	public function make($id)
	{
		$result = [];
		$group = Group::with('userFieldsWithObject')->find($id);
		$fields = (new EloquentRepository())->setModel(ConstructionObject::class)->getFields();
        $stages = app()->make(StageService::class)->index($id);

		foreach ($stages as $stage) {
            $result['stages'][] = [
                'id' => $stage->ID,
                'name' => $stage->TITLE
            ];
        }

        $result['sections'] = app()->make(FieldSectionService::class)->indexV2($id);

        /*
		foreach ($group->userFieldsWithObject->object->toArray() as $code => $value) {
			if ($code == 'ID' || in_array($code, ConstructionObject::RELATION_ALIASES)) {
				continue;
			}

			if (ConstructionObject::RELATION_ALIASES[$code]) {
				$result['obj'][$code] = [
					'name' => $fields[ConstructionObject::RELATION_ALIASES[$code]]['EDIT_FORM_LABEL'],
					'value' => $value ? $value['UF_NAME'] : 'Не выбрано'
				];
			} else {
				$result['obj'][$code] = [
					'name' => $fields[$code]['EDIT_FORM_LABEL'],
					'value' => $value
				];
			}
		}*/

		return $result;
	}
}
