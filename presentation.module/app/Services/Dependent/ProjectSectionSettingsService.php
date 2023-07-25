<?php

namespace PresentModule\App\Services\Dependent;

use Laravel\Illuminate\App\Models\B24\Group\Group;
use Laravel\Illuminate\App\Models\B24\UserField;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Models\Task\ProjectSectionSettings;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;
use PresentModule\App\Services\Task\FieldSectionService;

class ProjectSectionSettingsService extends BaseDependentService
{
	public function fields()
	{
		$fields = [[
			'FIELD_NAME' => 'SECTION_NAME',
			'EDIT_FORM_LABEL' => 'Секции',
		]];

		foreach ($this->groups() as $group) {
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

		$settings = ProjectSectionSettings::all();

		$sections = FieldSection::all();

		foreach ($sections as $section) {
			$data = ['ID' => $section->ID, 'SECTION_NAME' => $section->UF_NAME];

			foreach ($this->groups() as $group) {
				$sectionsData = app()->make(FieldSectionService::class)->index($group->ID, false, true);
				$sectionsInGroup = array_column($sectionsData, 'id');

				if (in_array($section->ID, $sectionsInGroup)) {
					$isChange = (bool)$settings->where('UF_SECTION_ID', '=', $section->ID)
						->where('UF_PROJECT_ID', '=', $group->ID)->first();

					$data['GROUP_'.$group->ID] =
						'<label class="ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element" '.($isChange ? 'checked' : '').'
							data-group-id="'.$group->ID.'" data-section-id="'.$section->ID.'" onchange="setSectionProjectSettings(event)">
					</label>';
				} else {
					$data['GROUP_'.$group->ID] = '';
				}
			}

			$rows[] = ['data' => $data];
		}

		return $rows;
	}

	public function groups()
	{
		return Group::where('ACTIVE', '=', 'Y')
			->where('CLOSED', '=', 'N')->get();
	}
}
