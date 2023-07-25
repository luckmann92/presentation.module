<?php

namespace PresentModule\App\Services\Dependent;

use Laravel\Illuminate\App\Models\B24\UserField;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Models\Task\TaskFieldSectionRelation;


class TaskFieldSectionRelationService extends BaseDependentService
{
	public function fields()
	{
		$fields = [[
			'FIELD_NAME' => 'FIELD_NAME',
			'EDIT_FORM_LABEL' => 'Поля',
		]];

		foreach ($this->sections() as $section) {
			$fields[] = [
				'FIELD_NAME' => 'SECTION_'.$section->ID,
				'EDIT_FORM_LABEL' => $section->UF_NAME,
			];
		}

		return $fields;
	}

	public function rows()
	{
		$rows = [];

		$relations = TaskFieldSectionRelation::all();

		$fields = UserField::where('ENTITY_ID', '=', 'TASKS_TASK')
			->with('lang')
			->orderBy('SORT')
			->get();

		foreach ($fields as $field) {
			if ($field->FIELD_NAME == 'UF_TASK_WEBDAV_FILES' || $field->FIELD_NAME == 'UF_MAIL_MESSAGE'
				|| $field->FIELD_NAME == 'UF_CRM_TASK') {
				continue;
			}

			$data = ['ID' => $field->ID, 'FIELD_NAME' => $field->lang->LIST_COLUMN_LABEL ?: $field->FIELD_NAME];

			foreach ($this->sections() as $section) {
				$isChange = (bool)$relations->where('UF_FIELD_ID', '=', $field->ID)
					->where('UF_SECTION_ID', '=', $section->ID)->first();

				$data['SECTION_'.$section->ID] =
					'<label class="ui-ctl-radio">
						<input type="radio" name="'.$field->ID.'" class="ui-ctl-element" '.($isChange ? 'checked' : '').'
							data-section-id="'.$section->ID.'" data-field-id="'.$field->ID.'" onchange="setSectionFieldRelation(event)">
					</label>';
			}

			$rows[] = ['data' => $data];
		}

		return $rows;
	}

	public function sections()
	{
		return FieldSection::all();
	}
}
