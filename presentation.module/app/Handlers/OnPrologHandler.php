<?php
namespace PresentModule\App\Handlers;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Laravel\Illuminate\App\Models\B24\Disk\DiskAttachedObject;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Models\Main\Option;
use PresentModule\App\Handlers\UserFields\CUserTypeAddressYandexMap;
use PresentModule\App\Handlers\UserFields\CUserTypeGroupRequiredFields;
use PresentModule\App\Handlers\UserFields\CUserTypeHlBlockDetail;
use PresentModule\App\Handlers\UserFields\CUserTypeTaskGroup;
use PresentModule\App\Handlers\UserFields\CUserTypeUfSelect;
use PresentModule\App\Handlers\UserFields\FieldsHandler;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Models\ExternalUser;
use PresentModule\App\Models\Task\File\Relation\TaskFileRelation;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Services\Task\FieldSectionService;
use PresentModule\App\Services\Task\File\Relation\TaskFileRelationService;

class OnPrologHandler
{
	private const MODULE_ID = 'presentation.module';

    public static function onProlog()
    {
    	self::addHandlers();
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$url = $request->getRequestUri();
        Extension::load('presentation.module');
		\Bitrix\Main\UI\Extension::load("ui.hint");

		if (strpos($url, '/tasks/task/edit/') !== false) {
			Extension::load('presentation.module');
			self::includeDependentFilesScripts($request);
		}

		if (strpos($url, '/tasks/task/view/') !== false) {
			Extension::load('presentation.module');

			self::scriptsForUsers();
		}

		if (strpos($url, '/workgroups/group/') !== false && strpos($url, '/edit/') !== false) {
			Extension::load('presentation.module');
		}
    }

    public static function addHandlers()
	{
		$eventManager = EventManager::getInstance();

		$eventManager->addEventHandler(
			'main',
			'OnUserTypeBuildList',
			[CUserTypeUfSelect::class, 'GetUserTypeDescription']
		);
		$eventManager->addEventHandler(
			'main',
			'OnUserTypeBuildList',
			[CUserTypeTaskGroup::class, 'GetUserTypeDescription']
		);
		$eventManager->addEventHandler(
			'main',
			'OnUserTypeBuildList',
			[CUserTypeHlBlockDetail::class, 'GetUserTypeDescription']
		);
		$eventManager->addEventHandler(
			'main',
			'OnUserTypeBuildList',
			[CUserTypeAddressYandexMap::class, 'GetUserTypeDescription']
		);
		$eventManager->addEventHandler(
			'main',
			'OnAfterUserTypeAdd',
			[FieldsHandler::class, 'onAfterUserTypeAdd']
		);
		$eventManager->addEventHandler(
			'tasks',
			'OnBeforeTaskAdd',
			[TasksHandler::class, 'onBeforeTaskAdd']
		);
		$eventManager->addEventHandler(
			'tasks',
			'OnTaskAdd',
			[TasksHandler::class, 'onTaskAdd']
		);
		$eventManager->addEventHandler(
			'tasks',
			'OnTaskUpdate',
			[TasksHandler::class, 'onTaskUpdate']
		);
		$eventManager->addEventHandler(
			'tasks',
			'OnBeforeTaskUpdate',
			[TasksHandler::class, 'onBeforeTaskUpdate']
		);
		$eventManager->addEventHandler(
			'socialnetwork',
			'OnSocNetGroupAdd',
			[GroupHandler::class, 'onSocNetGroupAdd']
		);
	}

	protected static function checkIterateSections($url)
	{
		Loader::includeModule('tasks');

		preg_match('/task\/view\/(\d*)/i', $url, $matches);
		$taskId = $matches[1];

		$task = Task::find($taskId);
		if (!$task || !$task->GROUP_ID) {
			return false;
		}

		$sections = app()->make(FieldSectionService::class)->index($task->GROUP_ID, false, true);

		return array_search(1, array_column($sections, 'isIterate')) !== false;
	}

	protected static function includeDependentFilesScripts($request)
	{
		$url = $request->getRequestUri();

		preg_match('/task\/edit\/(\d*)/i', $url, $matches);
		$taskId = $matches[1];

		if (!$taskId && $taskId !== '0') {
			return false;
		}

		$task = Task::find($taskId);
		if (!$request->get('GROUP_ID')) {
			return false;
		}

		$sectionsSettings = FieldSection::all();
		$dependentFields = app()->make(TaskFileRelationService::class)->index($taskId);
		foreach ($dependentFields as $k => $dependentField) {
			$sectionSettings = $sectionsSettings->where('ID', $dependentField['id'])->first();
			if ($sectionSettings->UF_RESULT_FIELD) {
				$field = UserField::find($sectionSettings->UF_RESULT_FIELD);
				$dependentFields[$k]['resultField'] = $field->FIELD_NAME;
			}

			$dependentFields[$k]['fields'] = array_values($dependentField['fields']);
		}


		$relations = json_decode($request->get('relations'), true);
		if ($relations) {
			foreach ($dependentFields as $k => $dependentField) {
				$dependentFields[$k]['relations'] = [];
			}

			foreach ($relations as $relation) {
				$dependentKey = array_search((int)$relation['sectionId'], array_column($dependentFields, 'id'));
				$dependentFields[$dependentKey]['relations'][] = $relation;
			}
		} else {
			foreach ($dependentFields as $k => $dependentField) {
				foreach ($dependentField['relations'] as $i => $relation) {
					foreach ($relation as $code => $id) {
						if ($code == 'id') {
							continue;
						}

						$attach = DiskAttachedObject::where('OBJECT_ID', $id)
							->where('ENTITY_ID', $taskId)
							->where('MODULE_ID', 'tasks')
							->first();

						$dependentFields[$k]['relations'][$i][$code] = $attach->ID;
					}

					$relationData = TaskFileRelation::find($relation['id']);
					if ($relationData->UF_CHILD_TASK_ID) {
						$task = Task::find($relationData->UF_CHILD_TASK_ID);
						$dependentFields[$k]['relations'][$i]['childTask'] = '№'.$task->ID.': '.$task->TITLE;
					}
				}
			}
		}

		$assetManager = \Bitrix\Main\Page\Asset::getInstance();
		$assetManager->addString('
                <script>
                    BX.ready(function () {
                        let dependent = new Presentation.Core.DependentFiles('.\CUtil::PhpToJSObject($dependentFields).', '.$taskId.', '.!!$relations.');
    					dependent.init();
                    });
                </script>
            ');
	}

	public static function scriptsForUsers() {
		if (!self::userIsManager()) {
			$assetManager = \Bitrix\Main\Page\Asset::getInstance();
			$assetManager->addString('
                <script>
                    BX.ready(function () {
                        let taskRights = new Presentation.Core.TaskRights();
    					taskRights.init();
                    });
                </script>
            ');
		}
	}

	public static function userIsManager() {
    	return (bool)\CIntranetUtils::getSubordinateEmployees(CurrentUser::get()->getId())->Fetch() || CurrentUser::get()->isAdmin();
	}
}
