<?php
namespace PresentModule\App\Handlers;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UI\Extension;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Models\Main\Option;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use PresentModule\App\Models\Group\Group;
use PresentModule\App\Models\Object\ConstructionObject;
use PresentModule\App\Models\Task\File\GroupObjectDirRelation;
use PresentModule\App\Observers\TaskFieldSectionRelationObserver;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Services\Dependent\TaskDeadlinesService;
use PresentModule\App\Services\Task\DouTaskObserverService;
use PresentModule\App\Services\Task\FieldSectionService;
use PresentModule\App\Services\Task\File\Relation\TaskFileRelationService;
use PresentModule\App\Services\Task\File\TaskFileDistributionService;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;
use PresentModule\App\Services\GroupShowService;
use PresentModule\App\Services\Task\ObserverTaskFieldService;
use PresentModule\App\Services\Task\TaskFieldsService;

class TasksHandler
{
    public static function onBeforeTaskUpdate($id, &$fields)
    {
    	if (!self::checkRights()) {
    		return false;
		}

        global $APPLICATION;

        if (!self::isAutoCreate())
        {
            $errors = app()->make(TaskFieldsService::class)->checkRequiredFields($fields, $id);

            if ($errors) {
                $APPLICATION->ThrowException($errors);
                return false;
            }

            $objectFields = self::checkObjectFields($fields);
            $fields = array_merge($fields, $objectFields);

            // app()->make(ObserverTaskFieldService::class)->init($id, $fields);
            app()->make(TaskDeadlinesService::class)->checkDeadlineChange($id, $fields['DEADLINE']);

            if (!$fields['SE_PROJECT']['ID']) {
                return true;
            }
        }
    }

    public static function onTaskAdd($id, $fields)
    {
        if (!self::isAutoCreate()) {
            self::checkInitialData($id);
            app()->make(TaskFieldsService::class)->checkFullnessRequiredFields($id);

			app()->make(TaskFileDistributionService::class)->init($id);

            $deadlinesActive = Option::where('MODULE_ID', '=', 'presentation.module')
                ->where('NAME', '=', 'DEADLINES_ACTIVE')->first();
            if ($deadlinesActive && $deadlinesActive->VALUE == 'Y') {
                app()->make(TaskDeadlinesService::class)->checkDeadline($id);
            }

            //Сервис создания связей файлов
            app()->make(TaskFileRelationService::class)->afterTaskAdd($id);
        }
    }

    public static function onTaskUpdate($id)
    {
        if (!self::isAutoCreate()) {
            self::checkInitialData($id);
            app()->make(TaskFieldsService::class)->checkFullnessRequiredFields($id);

            $deadlinesActive = Option::where('MODULE_ID', '=', 'presentation.module')
                ->where('NAME', '=', 'DEADLINES_ACTIVE')->first();
            if ($deadlinesActive && $deadlinesActive->VALUE == 'Y') {
                app()->make(TaskDeadlinesService::class)->checkDeadline($id);
            }
            app()->make(TaskFileDistributionService::class)->init($id);

			//Сервис создания связей файлов
            app()->make(TaskFileRelationService::class)->afterTaskUpdate($id);
        }
    }

    public static function onBeforeTaskAdd(&$fields)
    {
		if (!self::checkRights()) {
			return false;
		}

        if (!self::isAutoCreate()) {
            global $APPLICATION;
            $errors = app()->make(TaskFieldsService::class)->checkRequiredFields($fields);

            if ($errors) {
                $APPLICATION->ThrowException($errors);
                return false;
            }

            $objectFields = self::checkObjectFields($fields);
            $fields = array_merge($fields, $objectFields);

            $fields['ALLOW_CHANGE_DEADLINE'] = false;

            if (!$fields['SE_PROJECT']['ID']) {
                return true;
            }

            $initiatorFields = app()->make(TaskFieldsService::class)->checkInitiator($fields);
			$fields = array_merge($fields, $initiatorFields);
        }
    }

    public static function checkInitialData($id) {
        $task = Task::with('userFieldsData')->find($id);
        $result = array_merge(
            unserialize($task->userFieldsData->UF_INITIAL_DATA) ?: [],
            unserialize($task->userFieldsData->UF_ORGANIZATION_RELF) ?: [],
            unserialize($task->userFieldsData->UF_TITUL) ?: [],
        );
        $result = array_unique($result);
        $task->userFieldsData->UF_INITIAL_DATA = serialize($result);
        $task->userFieldsData->save();
    }

    public static function checkObjectFields($fields)
    {
        if (!$fields['UF_OBJECT']) {
            return [];
        }

        $objectFields = [];
        $object = ConstructionObject::find($fields['UF_OBJECT']);
        $objectFields['UF_OBJECT_ID'] = $object->UF_UID;
        $objectFields['UF_STARTS_SMR'] = $object->UF_STARTS_SMR ? date('d.m.Y', strtotime($object->UF_STARTS_SMR)) : '';

        return $objectFields;
    }

    public static function isAutoCreate(): bool
    {
        $request = Context::getCurrent()->getRequest();
        return $request->get('isAutoCreate') ?: false;
    }

    protected static function checkRights()
	{
		$externalUserId = Option::where('MODULE_ID', '=', 'presentation.openid')
			->where('NAME', '=', 'EXTERNAL_USER_ID')
			->first();
		if ($externalUserId && $externalUserId->VALUE == CurrentUser::get()->getId()) {
			return false;
		}

		return true;
	}
}
