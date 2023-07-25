<?php

namespace PresentModule\App\Services\Dependent;

use Laravel\Illuminate\App\Models\B24\Group\Group;
use Laravel\Illuminate\App\Models\B24\UserField;
use Illuminate\Support\Collection;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;
use PresentModule\App\Models\Task\TaskUserFields;
use Bitrix\Main\Engine\CurrentUser;

class ProjectTaskFieldRelationService extends BaseDependentService
{
	protected Collection $groups;

	public function __construct()
	{
		$this->groups = $this->groups();
	}

	public function fields()
	{
		$fields = [
			[
				'FIELD_NAME' => 'FIELD_NAME',
				'EDIT_FORM_LABEL' => 'Поле',
			],
			[
				'FIELD_NAME' => 'FIELD_CODE',
				'EDIT_FORM_LABEL' => 'Код поля',
			],
			[
				'FIELD_NAME' => 'FIELD_SECTION',
				'EDIT_FORM_LABEL' => 'Секция поля',
			],
		];

		foreach ($this->groups as $group) {
			$fields[] = [
				'FIELD_NAME' => 'GROUP_'.$group->ID,
				'EDIT_FORM_LABEL' => $group->NAME,
			];
		}

		return $fields;
	}

	public function rows()
	{
		$rows = [];

		$relations = ProjectTaskFieldRelation::all();

		$fields = TaskUserFields::taskUserFields()
			->with('lang')
			->orderBy('SORT')
			->select(['ID', 'FIELD_NAME'])
			->get();

		foreach ($fields as $field) {
			if ($field->FIELD_NAME == 'UF_TASK_WEBDAV_FILES' || $field->FIELD_NAME == 'UF_MAIL_MESSAGE'
				|| $field->FIELD_NAME == 'UF_CRM_TASK') {
				continue;
			}

			$data = $columns = [
				'ID' => $field->ID,
				'FIELD_NAME' => $field->lang->LIST_COLUMN_LABEL ?: $field->FIELD_NAME,
				'FIELD_SECTION' => $field->section ? $field->section->UF_NAME : '',
				'FIELD_CODE' => $field->FIELD_NAME,
			];

			foreach ($this->groups as $group) {
				$isChange = (bool)$relations->where('UF_FIELD_ID', '=', $field->ID)
					->where('UF_GROUP_ID', '=', $group->ID)->first();

				$data['GROUP_'.$group->ID] = $isChange ? 'Да' : '';

				$columns['GROUP_'.$group->ID] =
					'<label class="ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element" '.($isChange ? 'checked' : '').'
							data-group-id="'.$group->ID.'" data-field-id="'.$field->ID.'" onchange="setGroupFieldRelation(event)">
					</label>';
			}

			$rows[] = ['data' => $data, 'columns' => $columns];
		}

		return $rows;
	}

	public function groups()
	{
		return Group::where('ACTIVE', '=', 'Y')
			->where('CLOSED', '=', 'N')->get();
	}

	public function gridParams()
	{
		$gridParams = parent::gridParams();

		if (CurrentUser::get()->isAdmin()) {
			$gridParams['HEAD_NAV'] = true;
			$gridParams['EXPORT_EXCEL'] = true;
		}

		return $gridParams;
	}
}
