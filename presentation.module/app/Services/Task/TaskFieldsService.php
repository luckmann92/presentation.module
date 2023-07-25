<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Task;

use Bitrix\Im\User;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Laravel\Illuminate\App\Models\B24\Company\Company;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use Laravel\Illuminate\App\Models\B24\Task\TaskUserData;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Services\Service;
use Illuminate\Http\Request;
use PresentModule\App\Helpers\HlBlock\HlBlockHelper;
use PresentModule\App\Models\Group\Group;
use PresentModule\App\Models\Group\Stage;
use PresentModule\App\Models\Task\FieldSettings;
use PresentModule\App\Models\Task\ProjectSectionSettings;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Models\Task\TaskFieldSectionRelation;
use PresentModule\App\Models\Task\TaskUserFields;
use PresentModule\App\Services\Dependent\TaskDeadlinesService;

class TaskFieldsService extends Service
{
    public function setFieldSettings(Request $request)
    {
        $settings = FieldSettings::firstOrCreate([
            'UF_STAGE_ID' => $request->stageId,
            'UF_FIELD_ID' => $request->fieldId,
        ]);

        $settings->UF_MANDATORY = $request->checked === 'true';
        $settings->save();
    }

    public function setRelation(Request $request)
	{
		if ($request->checked === 'true') {
			ProjectTaskFieldRelation::firstOrCreate([
				'UF_GROUP_ID' => $request->groupId,
				'UF_FIELD_ID' => $request->fieldId,
			]);
		} else {
			$relation = ProjectTaskFieldRelation::where('UF_GROUP_ID', '=', $request->groupId)
				->where('UF_FIELD_ID', '=', $request->fieldId)->first();
			$relation->delete();

            //Снимаем обязательность поля по стадиям
            $stages = Stage::where('ENTITY_TYPE', 'G')
                ->where('ENTITY_ID', $request->groupId);

            $ufSettings = FieldSettings::where('UF_FIELD_ID', $request->fieldId)
                ->whereIn('UF_STAGE_ID', $stages->get()->pluck('ID'))
                ->where('UF_MANDATORY', 1)
                ->get();

            if ($ufSettings) {
                foreach ($ufSettings as $ufSetting) {
                    $ufSetting->UF_MANDATORY = false;
                    $ufSetting->save();
                }
            }
		}
	}

	public function setSectionRelation(Request $request)
	{
		$relation = TaskFieldSectionRelation::firstOrCreate([
			'UF_FIELD_ID' => $request->fieldId,
		]);

		$relation->UF_SECTION_ID = $request->sectionId;
		$relation->save();
	}

	public function taskViewComponent($id)
	{
		//ob_start();

		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'iit:task_fields',
			'',
			[
				'TASK_ID' => $id
			]
		);

		die;

		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	public function checkRequiredFields($fields, $id = false)
	{
        if (isset($fields['NOT_EVENT']) && ($fields['NOT_EVENT'] === 'Y' || $fields['NOT_EVENT'] === true)) {
            return;
        }
		$errors = [];

        $obRequest = Context::getCurrent()->getRequest();
        $stageId = ($obRequest->get('columnId') ?: $obRequest->get('stageId')) ?: $fields['STAGE_ID'];
        $groupId = $fields['GROUP_ID'] ?: ($obRequest->get('params')['GROUP_ID'] ?: false);
        $taskFields = [];

        $isJsonResponse = false;
        if ($obRequest->get('action') == 'moveStage' || $obRequest->get('action') == 'moveTask') {
            if ($obRequest->get('columnId') || $obRequest->get('stageId')) {
                $isJsonResponse = true;
            }
        }


		if ($id)
        {
            $obTask = new \CTaskItem($id, CurrentUser::get()->getId());
            $taskFields = $obTask->getData();

            if (!$groupId && isset($taskFields['GROUP_ID'])) {
                $groupId = $taskFields['GROUP_ID'];
            }

            if (!$stageId && isset($taskFields['STAGE_ID']) && $taskFields['STAGE_ID'] > 0) {
                $stageId = $taskFields['STAGE_ID'];
            }
        }

		if ($groupId == 327 && isset($fields['TITLE']) && strpos($fields['TITLE'], 'Auto: ') !== false)
        {
            return [];
        }

        if (!$stageId && $groupId)
        {
            $stage = Stage::where('ENTITY_TYPE', 'G')
                ->where('ENTITY_ID', $groupId)->orderBy('SORT')->first();
            $stageId = $stage['ID'] ?: false;
        }


		if ($stageId)
        {
            $requiredFields = FieldSettings::where('UF_STAGE_ID', $stageId)
                ->where('UF_MANDATORY', '1')
                ->with('field')
                ->get();

            foreach ($requiredFields as $requiredField)
            {
                $code = $requiredField->field->FIELD_NAME;
                $fieldName = $requiredField->field->lang->EDIT_FORM_LABEL ?: $code;

                //Проверка на наличии связи для отображения поля
                $fieldRelationGroup = ProjectTaskFieldRelation::where('UF_FIELD_ID', $requiredField->field->ID)
                    ->where('UF_GROUP_ID', $groupId)
                    ->first();

               // if ($fieldRelationGroup) {
                    if (isset($fields[$code]) && is_array($fields[$code])) {
                        if (empty(array_diff($fields[$code], ['']))) {
                            $errors[] = $fieldName;
                        }
                    } elseif ((!isset($fields[$code]) || empty($fields[$code])) && (!isset($taskFields[$code]) || empty($taskFields[$code]))) {
                        $errors[] = $fieldName;
                    }
                //}
            }
        }

		if ($isJsonResponse && $errors)
        {
            $result = [
                'result' => false,
                'type' => 'changeStage',
                'errors' => $errors,
            ];

            http_response_code('200');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            die();
        }

		return $errors ? 'Не заполнены следующие обязательные поля:'.PHP_EOL.implode(', ', $errors) : [];
	}

	public function getTaskFields(int $groupId)
	{
		return ProjectTaskFieldRelation::where('UF_GROUP_ID', $groupId)
			->with('field')
			->get()
			->pluck('field');
	}

	public function setSettings(Request $request)
	{
		$settings = ProjectSectionSettings::firstOrCreate([
			'UF_SECTION_ID' => $request->sectionId,
			'UF_PROJECT_ID' => $request->groupId,
		]);

		$settings->UF_IS_ITERATE = $request->checked === 'true';
		$settings->save();
	}

	public function getSomething(Request $request)
	{
		$tasksFields = TaskUserFields::whereIn('FIELD_NAME', $request->fields)->get();

		$select = array_merge(['VALUE_ID'], $request->fields);
		$tasksUserFields = TaskUserData::whereIn('VALUE_ID', $request->taskIds)
			->select($select)->get();

		$result = [];
		foreach ($tasksUserFields as $taskUserFields) {
			foreach ($tasksFields as $field) {
				if ($taskUserFields->{$field->FIELD_NAME}) {
					switch ($field->USER_TYPE_ID) {
						case 'hlblock':
						case 'hlblock_detail':
						case 'hlblocksearch':
							$table = HlBlockHelper::getTableNameById($field->SETTINGS['HLBLOCK_ID']);
							$fieldData = UserField::find($field->SETTINGS['HLFIELD_ID']);

							if (app()->has($table)) {
								$model = app()->make($table);
								$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] =
									$model->find($taskUserFields->{$field->FIELD_NAME})->{$fieldData->FIELD_NAME};
							} else {
								$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] = '';
							}
							break;
						case 'crm':
							$company = Company::find($taskUserFields->{$field->FIELD_NAME});
							$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] = $company->TITLE;
							break;
						default:
							if ($field->MULTIPLE == 'Y') {
								$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] =
									implode('; ', unserialize($taskUserFields->{$field->FIELD_NAME}));
							} else {
								$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] =
									$taskUserFields->{$field->FIELD_NAME};
							}
					}
				} else {
					$result[$taskUserFields->VALUE_ID][$field->FIELD_NAME] = '';
				}
			}
		}

		return $result;
	}

	public function checkFullnessRequiredFields($id)
	{
		$obRequest = Context::getCurrent()->getRequest();

		if ($obRequest->get('action') == 'moveStage' || $obRequest->get('action') == 'moveTask') {
			return;
		}

		$task = Task::with('userFieldsData')->find($id);

		if (!$task->GROUP_ID) {
			return;
		}

		$initialTaskStageId = $task->STAGE_ID;
		$taskStageId = $task->STAGE_ID;

		$stages = TaskStage::where('ENTITY_TYPE', 'G')
			->where('ENTITY_ID', $task->GROUP_ID)->orderBy('SORT')->get();

		if (!$taskStageId) {
			$taskStageId = $stages->first()->ID;
		}

		$taskStage = $stages->find($taskStageId);

		$prevRequiredFields = FieldSettings::where('UF_STAGE_ID', $taskStageId)
			->where('UF_MANDATORY', '1')
			->get();

		foreach ($stages->where('SORT', '>', $taskStage->SORT) as $stage) {
			$prevRequiredFields = $requiredFields ?: $prevRequiredFields;

			$requiredFields = FieldSettings::where('UF_STAGE_ID', $stage->ID)
				->where('UF_MANDATORY', '1')
				->with('field')
				->get();

			if ($requiredFields->isEmpty()) {
				break;
			}

			$prevRequiredFieldIds = $prevRequiredFields ? $prevRequiredFields->pluck('UF_FIELD_ID')->toArray() : [];
			$requiredFieldIds = $requiredFields ? $requiredFields->pluck('UF_FIELD_ID')->toArray() : [];

			sort($prevRequiredFieldIds);
			sort($requiredFieldIds);
			if (serialize($prevRequiredFieldIds) == serialize($requiredFieldIds)) {
				continue;
			}

			if ($stage->SYSTEM_TYPE == 'FAILURE') {
				continue;
			}

			$isFullness = true;
			foreach ($requiredFields as $requiredField) {
				$fieldCode = $requiredField->field->FIELD_NAME;

				$value = $task->userFieldsData->{$fieldCode};
				if ($requiredField->field->MULTIPLE === 'Y') {
					$value = unserialize($value);
				}

				if (!$value) {
					$isFullness = false;
				}
			}

			if ($isFullness) {
				$task->STAGE_ID = $stage->ID;
			}
		}

		$task->save();

		if ($initialTaskStageId != $task->STAGE_ID) {
			app()->make(TaskDeadlinesService::class)->checkDeadline($id);
		}
	}

	public function checkInitiator($fields): array {
		if (!$fields['SE_PROJECT']['ID']) {
			return [];
		}

		$group = Group::with('userFields')->find($fields['SE_PROJECT']['ID']);
		if ($group->userFields->UF_TASK_INITIATOR) {
			return [
				'CREATED_BY' => $group->userFields->UF_TASK_INITIATOR,
				'CHANGED_BY' => $group->userFields->UF_TASK_INITIATOR,
			];
		}

		return [];
	}
}
