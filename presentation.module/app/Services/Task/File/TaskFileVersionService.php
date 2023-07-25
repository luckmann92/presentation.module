<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Task\File;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Laravel\Illuminate\App\Models\Bitrix\UserField;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use PresentModule\App\Models\Task\File\FileVersion;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Services\Task\FieldSectionService;
use ZipStream\File;

class TaskFileVersionService
{
    public function getVersions($taskId)
    {
        Loader::includeModule('tasks');

        $obTask = \CTaskItem::getInstance($taskId, 1);
        $task = $obTask->getData();

        $sections = app()->make(FieldSectionService::class)->index($task['GROUP_ID'], false, true);
        $fieldVersions = FileVersion::where('TASK_ID', '=', $taskId)->get();

        $preVersions = [];
        foreach ($fieldVersions as $fileVersion) {
            $preVersions[$fileVersion->FIELD_ID][$fileVersion->VERSION][] = $fileVersion->FILE_ID;
        }

        foreach ($preVersions as $fieldId => $versions) {
            foreach ($sections as $i => $section) {
                foreach ($section['fields'] as $id => $code) {
                    if ($id == $fieldId && isset($task[$code]) && !empty($task[$code])) {
                        $preVersions[$fieldId][max(array_keys($versions)) + 1] = $task[$code];
                    }
                }
            }
        }

        foreach ($preVersions as $fieldId => $versions) {
            foreach ($sections as $i => $section) {
                foreach ($section['fields'] as $id => $code) {
                    if ($id == $fieldId) {
                        $preVersions[$code] = $versions;
                        unset($preVersions[$id]);
                    }
                }
            }
        }

        return $preVersions;
    }

    public function getSectionVersion($taskId, $sectionId)
    {
        $version = 0;
        Loader::includeModule('tasks');

        $obTask = \CTaskItem::getInstance($taskId, 1);
        $task = $obTask->getData();
        $codeFields = app()->make(FieldSectionService::class)->index($task['GROUP_ID'], $sectionId, true);

        $fieldIds = UserField::whereIn('FIELD_NAME', array_values($codeFields))
            ->where('ENTITY_ID', '=', 'TASKS_TASK')
            ->get()
            ->pluck('ID')
            ->toArray();

        $item = FileVersion::where('TASK_ID', '=', $taskId)
            ->whereIn('FIELD_ID', $fieldIds)
            ->orderBy('VERSION', 'desc')
            ->first();

        if ($item) {
            $version = $item->VERSION;
        }

        return [
            'version' => $version
        ];
    }

    public function update($request)
    {
		$task = Task::with('userFieldsData')->find($request['TASK_ID']);

		$codeFields = app()->make(FieldSectionService::class)->index($task['GROUP_ID'], $request['SECTION_ID']);

        if ($codeFields) {
            $fields = UserField::whereIn('FIELD_NAME', array_values($codeFields))
                ->where('ENTITY_ID', '=', 'TASKS_TASK')
                ->get();

            $sectionVersion = $this->getSectionVersion($task['ID'], $request['SECTION_ID'])['version'];

			foreach ($fields as $field) {
				$value = $task->userFieldsData->{$field->FIELD_NAME};
				if ($field->MULTIPLE == 'Y') {
					$value = unserialize($value);
				}

				if ($value) {
					$this->addVersion($task->ID, $field->ID, $task->userFieldsData->{$field->FIELD_NAME}, $sectionVersion);
					$task->userFieldsData->{$field->FIELD_NAME} = null;
				}
			}

			$task->userFieldsData->save();
        }

		app()->make(TaskFileDistributionService::class)->init($task->ID);

		return $this->getSectionVersion($task->ID, $request['SECTION_ID']);
    }

    public function rollback(Request $request)
	{
		Loader::includeModule('tasks');

		$task = \CTaskItem::getInstance($request->TASK_ID, CurrentUser::get()->getId());
		$taskData = $task->getData();
		$newTaskData = [];

		$sectionFieldCodes = app()->make(FieldSectionService::class)->index($taskData['GROUP_ID'], $request->SECTION_ID);

		if ($sectionFieldCodes) {
			$sectionVersion = $this->getSectionVersion($taskData['ID'], $request->SECTION_ID)['version'];

			$fields = UserField::whereIn('FIELD_NAME', array_values($sectionFieldCodes))
				->where('ENTITY_ID', '=', 'TASKS_TASK')
				->get();

			$versionFields = FileVersion::whereIn('FIELD_ID', $fields->pluck('ID'))
				->where('VERSION', '=', $sectionVersion)
				->where('TASK_ID', '=', $request->TASK_ID)
				->get();

			foreach ($versionFields as $field) {
				$userField = $fields->find($field->FIELD_ID);
				$value = $field->FILE_ID;

				if ($userField->USER_TYPE_ID == 'date') {
					$value = date('d.m.Y', strtotime($value));
				}

				$newTaskData[$fields->find($field->FIELD_ID)->FIELD_NAME] = $value;
				$field->delete();
			}

			$task->update($newTaskData);
		}

		return $this->getSectionVersion($task['ID'], $request['SECTION_ID']);
	}

    public function getFieldVersion($taskId, $fieldId)
    {
        $item = FileVersion::where('TASK_ID', '=', $taskId)
            ->where('FIELD_ID', '=', $fieldId)
			->orderBy('VERSION', 'DESC')
            ->first();

        return $item ? $item->VERSION : 0;
    }

    public function addVersion($taskId, $fieldId, $fileId, $sectionVersion)
    {
        $repository = (new EloquentRepository())->setModel(FileVersion::class);
        $repository->insert([
            'TASK_ID' => $taskId,
            'FIELD_ID' => $fieldId,
            'FILE_ID' => $fileId,
            'VERSION' => ++$sectionVersion
        ]);

        return $sectionVersion;
    }
}
