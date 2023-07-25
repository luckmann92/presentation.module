<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Task\File\Relation;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Disk;
use PresentModule\App\Models\Task\File\Relation\TaskFileRelation;
use Bitrix\Main\Request;
use PresentModule\App\Models\Task\FieldSection;
use Laravel\Illuminate\App\Models\B24\UserField;
use PresentModule\App\Models\Task\ObserverTaskField;
use PresentModule\App\Models\Task\TaskFieldSectionRelation;
use PresentModule\App\Models\Task\TaskMappingField;
use PresentModule\App\Services\Task\FieldSectionService;
use PresentModule\App\Services\Task\ObserverTaskFieldService;

Loader::includeModule('disk');
Loader::includeModule('tasks');

/**
 * Сервис создания связи Договор (необяз) - Счет - Платежка - Акт - СФ (Счет-фактура)
 * Class DocFileRelationService
 * @package PresentModule\App\Services\Task\File\Relation
 */
class TaskFileRelationService
{
    protected $errors;
    protected $taskId = false;
    protected Request $request;
    protected bool $isEngineeringTask  = false;
    protected $task = [];
    protected array $requestRowRelations = [];
    protected array $sections = [];
    protected array $sectionCodeFields = [
        'contract', 'invoice', 'paymentOrder', 'act', 'sf'
    ];

    public function afterTaskAdd($taskId)
    {
        $this->init($taskId);

        if (!$this->isEngineeringTask) {
            return;
        }

        if ($this->requestRowRelations) {
            logdb(['ID' => $this->taskId, 'relations' => $this->requestRowRelations], 'RELATIONS_TASK_ADD');
            $this->syncRequestRowRelations();
        }

        //Синхроним отслеживаемые Поля
        $this->syncObserverTaskFieldsByTargetTask();
    }

    public function afterTaskUpdate($taskId)
    {
        $this->init($taskId);

        //Если ИЗ
        if ($this->isEngineeringTask)
        {
            //Обрабатываем request и синхроним Связи
            if ($this->requestRowRelations) {
                logdb(['ID' => $this->taskId, 'relations' => $this->requestRowRelations], 'RELATIONS_TASK_UPDATE_EN');
                $this->syncRequestRowRelations();
            }

            //Синхроним отслеживаемые Поля
            $this->syncObserverTaskFieldsByTargetTask();
        }
        else
        {
            list($targetTaskId, $triggerFieldId) = explode('_', $this->task['UF_TASK_FIELD']);

            $parentTask = \CTasks::GetByID($targetTaskId, false)->Fetch();
            if (!$parentTask) {
                return;
            }
            //Если OT ищем relation
            $relation = TaskFileRelation::where('UF_CHILD_TASK_ID', $this->taskId)->first();

            //Получаем новые Поля для relation
            $updateRelationFields = $this->getUpdateRelationFieldsByObserverTask();

            logdb(['ID' => $this->taskId, 'relation' =>  $updateRelationFields], 'RELATION_TASK_UPDATE_DOU');

            //Получаем Поля для обновления ИЗ
            $updateTaskFields = $this->getUpdateFieldsForTargetTask($updateRelationFields, $relation);

            if ($updateTaskFields)
            {
                $this->request->set('isAutoCreate', true);
                $obTask = new \CTasks();
                $obTask->Update($relation->UF_TASK_ID, $updateTaskFields);
            }
            if ($updateRelationFields) {
                if ($relation) {
                    $relation->update($updateRelationFields);
                } else {

                    if (!empty($this->task['UF_TASK_FIELD']))
                    {
                        list($targetTaskId, $triggerFieldId) = explode('_', $this->task['UF_TASK_FIELD']);

                        if (empty($updateRelationFields['UF_TASK_ID']) || !isset($updateRelationFields['UF_TASK_ID'])) {
                            $sectionRelation = TaskFieldSectionRelation::where('UF_FIELD_ID', $triggerFieldId)->first();
                            if ($sectionRelation) {
                                $updateRelationFields['UF_SECTION'] = $sectionRelation->UF_SECTION_ID;
                            }
                        }

                        $updateRelationFields['UF_TASK_ID'] = $targetTaskId;
                        $updateRelationFields['UF_CHILD_TASK_ID'] = $this->taskId;

                        TaskFileRelation::create($updateRelationFields);
                    }
                }
            }
        }
    }

    protected function syncObserverTaskFieldsByTargetTask()
    {
        $excludeCodes = ['UF_ORIGINAL_TASK_ID', 'UF_DOCS_CREATOR', 'UF_TASK_FIELD', 'UF_ORIGINAL_TASK_SECTION_ID'];

        $relations = TaskFileRelation::where('UF_TASK_ID', $this->taskId)->get();

        foreach ($relations as $relation)
        {
            $updateObserverTaskFields = [];

            $section = $this->getSectionById($relation->UF_SECTION);

            $obTask = \CTaskItem::getInstance($relation->UF_CHILD_TASK_ID, 1);
            $childTask = $obTask->getData();

            list($targetTaskId, $triggerFieldId) = explode('_', $childTask['UF_TASK_FIELD']);
            $triggerField = ObserverTaskField::where('UF_TARGET_FIELD_ID', $triggerFieldId)->first();

            if ($triggerField) {
                $observerUserFields = UserField::whereIn('ID', unserialize($triggerField->UF_OBSERVER_FIELD_IDS))->get();

                foreach ($observerUserFields as $observerUserField) {
                    $code = $observerUserField->FIELD_NAME;

                    if (!in_array($code, $section['mappingFields']))
                    {
                        if (is_array($this->task[$code]))
                        {
                            if ($childTask[$code] != $this->task[$code]) {
                                if ($observerUserField->USER_TYPE_ID == 'disk_file')
                                {
                                    foreach ($this->task[$code] as $i => $attachedId)
                                    {
                                        $updateObserverTaskFields[$code][$i] = 'n'.$this->getObjectIdByAttachmentId($attachedId);
                                    }
                                } else {
                                    $updateObserverTaskFields[$code] = $this->task[$code];
                                }
                            }
                        }
                        else
                        {
                            if ($childTask[$code] != $this->task[$code]) {
                                $updateObserverTaskFields[$code] = $this->task[$code];
                            }
                        }
                    }

                }
            }

            if ($updateObserverTaskFields)
            {
                foreach ($excludeCodes as $i => $code) {
                    if (isset($updateObserverTaskFields[$code])) {
                        unset($updateObserverTaskFields[$code]);
                    }
                }
                logdb([
                    'ID' => $relation->UF_CHILD_TASK_ID,
                    'fields' => json_encode($updateObserverTaskFields, JSON_UNESCAPED_UNICODE)],
                    'UPDATE_FIELDS_AFTER_RELATIONS'
                );
                $this->request->set('isAutoCreate', true);
                $obTask = new \CTasks();
                $obTask->Update($relation->UF_CHILD_TASK_ID, $updateObserverTaskFields);
            }
        }
    }

    protected function getUpdateRelationFieldsByObserverTask(): array
    {
        $currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
        $currentDateTime = date('Y-m-d H:i:s');

        $updateRelationFields['UF_CHANGED_BY'] = $currentUserId;
        $updateRelationFields['UF_CHANGED_DATE'] = $currentDateTime;

        $sectionId = FieldSection::where('UF_NAME', $this->task['UF_ORIGINAL_TASK_SECTION_ID'])
            ->select(['ID'])->first()->ID;
        $section = $this->getSectionById($sectionId);

        if ($sectionId) {
            $updateRelationFields['UF_SECTION'] = $sectionId;
        }

        foreach ($section['mappingFields'] as $code => $codeField) {
            $uField = UserField::where('ENTITY_ID', 'TASKS_TASK')
                ->where('FIELD_NAME', $codeField)
                ->first();

            $mappingField = $this->getFieldByObserverFieldId($uField->ID);

            $value = $this->task[$mappingField->FIELD_NAME] ?: '';

            if (isset($value) && is_array($value) && !empty($value)) {
                $attachedId = $value[array_key_first($value)];
                $value = $this->getObjectIdByAttachmentId($attachedId);
            }
            $updateRelationFields[$code] = $value;
        }
        return $updateRelationFields;
    }

    protected function getUpdateFieldsForTargetTask($relationFields, $relation): array
    {
        $updateTaskTargetFields = [];

        list($targetTaskId, $triggerFieldId) = explode('_', $this->task['UF_TASK_FIELD']);

        $obTask = \CTaskItem::getInstance($targetTaskId, 1);
        $targetTask = $obTask->getData();

        $section = $this->getSectionById($relation->UF_SECTION);

        foreach ($section['mappingFields'] as $code => $codeField) {
            $uField = UserField::where('ENTITY_ID', 'TASKS_TASK')
                ->where('FIELD_NAME', $codeField)
                ->first();

            $mappingField = $this->getFieldByObserverFieldId($uField->ID);

            $currentObjectId = $this->getObjectIdByAttachmentId($this->task[$mappingField->FIELD_NAME][0]);


            if ($currentObjectId != $relation->$code) {
                if (is_array($targetTask[$codeField])) {
                    foreach ($targetTask[$codeField] as $i => $attachmentId) {
                        if ($this->getObjectIdByAttachmentId($attachmentId) != $relation->$code) {
                            $updateTaskTargetFields[$codeField][$i] = $attachmentId;
                        }
                    }
                }
                $updateTaskTargetFields[$codeField][] = 'n' . $currentObjectId;
            }
        }
        return $updateTaskTargetFields;
    }

    protected function syncRequestRowRelations()
    {
        foreach ($this->requestRowRelations as $i => $row)
        {
            if ((!isset($row['id']) || empty($row['id'])) && (!isset($row['action']) || empty($row['action']))) {
                $row['action'] = 'create';
            }

            $relationFields = $this->prepareRelationRowData($row);

            switch ($row['action'])
            {
                case 'create':
                    $relationFields['UF_CHILD_TASK_ID'] = (string)$this->createObserverTaskByRelationFields($relationFields);
                    TaskFileRelation::create($relationFields);
                    break;
                case 'update':
                    if ($row['id'])
                    {
                        $relation = TaskFileRelation::find($row['id']);
                        if ($relation) {
                            $this->syncObserverTaskFilesRelationFields($relationFields, $relation->UF_CHILD_TASK_ID);
                            $relation->update($relationFields);
                        }
                    }
                    break;
                case 'delete':
                case 'remove':
                    if ($row['id'])
                    {
                        $relation = TaskFileRelation::find($row['id']);
                        if ($relation) {
                            \CTask::Delete($relation->UF_TASK_CHILD_ID);
                            $relation->delete();
                        }
                    }
                    break;
            }
        }
    }

    protected function removeDuplicateAndEmptyRows($rows): array
    {
        $uniqueRows = [];

        foreach ($rows as $key => $row) {
            $isDuplicate = false;

            foreach ($uniqueRows as $uniqueRow) {
                if ($row === $uniqueRow) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $uniqueRows[$key] = $row;
            }
        }


        foreach ($uniqueRows as $i => $uniqueRow) {
            $isEmpty = true;

            foreach ($this->sectionCodeFields as $code) {
                if ($uniqueRow[$code]) {
                    $isEmpty = false;
                }
            }

            if ($isEmpty) {
                unset($uniqueRows[$i]);
            }
        }

        return $uniqueRows;
    }

    /**
     * @param $taskId
     * @throws \CTaskAssertException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function init($taskId)
    {
        $this->request = Context::getCurrent()->getRequest();
        $json = json_decode($this->request->get('relations'), true);
        $this->requestRowRelations = $json ?: [];
        //Нужно для тестов
        //$this->requestRowRelations = $this->request->getJsonList()->toArray()['relations'];
        if ($this->requestRowRelations) {
            $this->requestRowRelations = $this->removeDuplicateAndEmptyRows($this->requestRowRelations);
        }
        logdb(['ID' => $taskId, 'relations' => $this->requestRowRelations], 'INIT_REQUEST_RELATIONS');

        if ($taskId > 0) {
            $this->taskId = $taskId;
            $obTask = \CTaskItem::getInstance($this->taskId, 1);
            $this->task = $obTask->getData();

            if (empty($this->task['UF_TASK_FIELD']) || !isset($this->task['UF_TASK_FIELD'])) {
                $this->isEngineeringTask = true;
            }
        }

        $this->sections = app()->make(FieldSectionService::class)->index();
    }

    public function prepareRelationRowData($row): array
    {
        $currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
        $currentDateTime = date('Y-m-d H:i:s');

        $fields = [
            'UF_SECTION' => $row['sectionId'],
            'UF_TASK_ID' => $this->taskId ?: ''
        ];

        if (isset($row['action'])) {
            switch($row['action']) {
                case 'create':
                    $fields['UF_CREATED_BY'] = $currentUserId;
                    $fields['UF_CREATED_DATE'] = $currentDateTime;
                    break;
                case 'update':
                    $fields['UF_CHANGED_BY'] = $currentUserId;
                    $fields['UF_CHANGED_DATE'] = $currentDateTime;
                    break;
            }
        }

        foreach ($this->sectionCodeFields as $code)
        {
            $value = $row[$code];

            $fieldCode = 'UF_'.strtoupper(ltrim(preg_replace('/[A-Z]/', '_$0', $code)));

            if ($value) {
                if (substr($value, 0, 1) == 'n') {
                    $value = substr($value, 1);
                } else {
                    $value = $this->getObjectIdByAttachmentId($value);
                }
            }
            $fields[$fieldCode] = $value;
        }

        return $fields;
    }

    protected function createObserverTaskByRelationFields($relationFields)
    {
        $triggerField = app()->make(ObserverTaskFieldService::class)->getBySectionId($relationFields['UF_SECTION']);
        $newTaskFields = array_merge(
            $this->getObserverNewTaskData($triggerField),
            $this->getUpdateFieldsByRelationFields($relationFields)
        );

        $rsChildTask = \CTasks::GetList([], ['UF_TASK_FIELD' => $this->taskId.'_'.$triggerField->UF_TARGET_FIELD_ID])
            ->Fetch();

        if (is_array($rsChildTask) && !empty($rsChildTask))
        {
            if ($rsChildTask['ID']) {
                return $rsChildTask['ID'];
            }
        }

        $this->request->set('isAutoCreate', true);

        $obNewTask = new \CTasks();
        $nTaskId = $obNewTask->Add($newTaskFields, ['CLONE_DISK_FILE_ATTACHMENT' => 'Y']);
        return $nTaskId;
    }

    protected function syncObserverTaskFilesRelationFields($relationFields, $observerTaskId): bool
    {
        $this->request->set('isAutoCreate', true);
        $obTask = new \CTasks();

        return $obTask->Update($observerTaskId, $this->getUpdateFieldsByRelationFields($relationFields));
    }

    protected function getUpdateFieldsByRelationFields($relationFields): array
    {
        $section = $this->getSectionById($relationFields['UF_SECTION']);
        $updateTaskFields = [];

        foreach ($section['mappingFields'] as $code => $codeField) {
            $uField = UserField::where('ENTITY_ID', 'TASKS_TASK')
                ->where('FIELD_NAME', $codeField)
                ->first();

            $mappingField = $this->getFieldByObserverFieldId($uField->ID);

            if ($relationFields[$code]) {
                $updateTaskFields[$mappingField->FIELD_NAME] = ['n'.$relationFields[$code]];
            }
        }

        return $updateTaskFields;
    }

    public function createTaskByRelationId($relationId)
    {
        $obTask = \CTaskItem::getInstance($this->taskId, 1);
        $task = $obTask->getData();
        $relation = TaskFileRelation::find($relationId);

        if ($relation) {

            //$section = $this->getBySectionId($relation->UF_SECTION);
            /*    $observerUField = UserField::where('ENTITY_ID', 'TASKS_TASK')
                    ->where('FIELD_NAME', $section['mappingFields']['UF_INVOICE'])
                    ->select('ID')
                    ->first();*/


            /*   $triggerField = ObserverTaskField::where('UF_TARGET_FIELD_ID', $triggerFieldId)->first();

                     if ($triggerField) {
                         $observerUserFields = UserField::whereIn('ID', unserialize($triggerField->UF_OBSERVER_FIELD_IDS))->get();
                         $triggerField->OBSERVER_FIELDS = $observerUserFields;
                     }

                     foreach ($triggerField->OBSERVER_FIELDS as $observerField) {
                         $code = $observerField->FIELD_NAME;

                         if ($targetTask[$code] != $this->task[$code]) {
                             $updateTaskTargetFields[$code] = $targetTask[$code];
                         }
                     }
             */
            $triggerField = app()->make(ObserverTaskFieldService::class)->getBySectionId($relation->UF_SECTION);



            dd($observerTaskField->UF_OBSERVER_FIELD_IDS);

            dd($relation['UF_SECTION']);
        }

        $section = $this->sections->where('ID', $relation->UF_SECTION)->first();

        if ($section) {
            $fields = [];

            //  $triggerField = $section->
            //Подставляем конкретные измененные поля по задаче
            foreach ($this->sectionCodeFields as $code) {
                //Код поля в relation
                $uFieldCode = 'UF_'.strtoupper(ltrim(preg_replace('/[A-Z]/', '_$0', $code)));
                //Код поля в section
                $uFieldCodeSection = $uFieldCode.'_FIELD';

                $uField = UserField::find($section->$uFieldCodeSection);

                dd($this->task);
                // $fields[$uField->FIELD_NAME] = $relation->$uFieldCode;
            }

            dd($fields);
        }





        //$this->sections
    }

    protected function getFieldByObserverFieldId($fieldId, $isMain = false)
    {
        $id = $fieldId;

        if ($isMain) {
            $ufField = TaskMappingField::where('UF_MAPPING_FIELD_ID',  $fieldId)->first();
        } else {
            $ufField = TaskMappingField::where('UF_MAPPING_FIELD_IDS', 'LIKE', '%"'.$fieldId.'"%')->first();
        }


        if ($ufField) {
            $id = $ufField->UF_MAPPING_FIELD_ID;
        }

        return UserField::where('ID', $id)->first();
    }

    protected function getObserverNewTaskData($trigger, $targetTask = false): array
    {
        if (!$targetTask) {
            $targetTask = $this->task;
        }
        $site = \CSite::GetByID(SITE_ID)->Fetch();
        $sectionRelation = TaskFieldSectionRelation::where('UF_FIELD_ID', $trigger->UF_TARGET_FIELD_ID)->first();
        if ($sectionRelation) {
            $section = FieldSection::where('ID', $sectionRelation->UF_SECTION_ID)->first();
            if ($section) {
                $result['UF_ORIGINAL_TASK_SECTION_ID'] = $section->UF_NAME ?: '';
            }
        }

        $result['UF_ORIGINAL_TASK_ID'] = 'http://'.$site['SERVER_NAME'].'/workgroups/group/'.$targetTask['GROUP_ID'].'/tasks/task/view/'.$targetTask['ID'].'/';
        $result['UF_DOCS_CREATOR'] = $targetTask['RESPONSIBLE_ID'];
        $result['RESPONSIBLE_ID'] = 9;
        $result['CREATED_BY'] = $targetTask['RESPONSIBLE_ID'];
        $result['UF_TASK_FIELD'] = $targetTask['ID'].'_'.$trigger->UF_TARGET_FIELD_ID;
        $result['GROUP_ID'] = $trigger->UF_GROUP_ID;
        $result['TITLE'] = 'Auto: '. htmlspecialchars_decode($targetTask['TITLE']);

        return $result;
    }

    /**
     * @param $sectionId
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getSectionById($sectionId): array
    {
        $sections = $this->sections ?: [];
        if (empty($sections)) {
            $sections = app()->make(FieldSectionService::class)->index();
        }
        foreach ($sections as $section) {
            if ($section['id'] == $sectionId) {
                return $section;
            }
        }
        return [];
    }

    /**
     * Поиск связей без задач
     * @param false $taskId
     * @return array
     */
    protected function findEmptyRelationsByTaskById($taskId = false): array
    {
        if ($taskId > 0 && $taskId != $this->taskId) {
            $this->init($taskId);
        }

        $_fileObjects = [];
        $_matchRelations = [];

        foreach ($this->sections as $section) {
            if ($section['id'] == 1) {
                continue;
            }

            //Заполняем поля объектами файлов по указанным полям в Секции
            foreach ($section['mappingFields'] as $sectionFieldCode => $mappingFieldCode) {
                if (isset($this->task[$mappingFieldCode]) && is_array($this->task[$mappingFieldCode])) {
                    foreach ($this->task[$mappingFieldCode] as $i => $attachmentId) {
                        $_fileObjects[$section['id']][$sectionFieldCode][$i] = $this->getObjectIdByAttachmentId($attachmentId);
                    }
                }
            }
        }

        //Получаем все связи без связи с задачами
        $relations = TaskFileRelation::where('UF_TASK_ID', false)->get();

        //Проверяем совпадение связей с задачей
        foreach ($relations->toArray() as $_relation) {
            foreach ($this->sections as $section) {
                $isMatch = true;

                foreach ($section['mappingFields'] as $sectionFieldCode => $mappingFieldCode) {
                    if (!$isMatch || isset($_matchRelations[$_relation['ID']])) {
                        continue;
                    }
                    if (is_array($_fileObjects[$section['id']][$sectionFieldCode])) {
                        if (!in_array($_relation[$sectionFieldCode], $_fileObjects[$section['id']][$sectionFieldCode])) {
                            $isMatch = false;
                        }
                    }
                }
                if ($isMatch) {
                    $_matchRelations[$_relation['ID']] = $_relation;
                }
            }
        }
        return $_matchRelations;
    }

    public function getObjectIdByAttachmentId($attachmentId)
    {
        $attachedObject = Disk\AttachedObject::getById($attachmentId);
        if ($attachedObject) {
            $fileObject = Disk\File::loadById($attachedObject->getObjectId());
            if ($fileObject) {
                return $fileObject->getId();
            }
        }
        return false;
    }


    public function make(Request $request, $taskId)
    {
        $errors = [];

        $this->init($taskId);

        $rows = $request->toArray();

        if (empty($rows)) {
            return false;
        }

        $response = [];
        foreach ($rows as $i => $row) {
            if ((!isset($row['id']) || empty($row['id'])) && (!isset($row['action']) || empty($row['action']))) {
                $row['action'] = 'create';
            }

            $fields = $this->prepareRowData($row);
            dd($fields);
            switch ($row['action']) {
                case 'create':
                    $result = TaskFileRelation::create($fields);
                    if (!$result || !$result->ID) {
                        $errors[$i] = 'Ошибка создания связи';
                    } else {
                        $response[] = $result->ID;
                    }
                    break;
                case 'update':
                    $result = false;
                    $relation = TaskFileRelation::find($row['id']);
                    if ($relation) {
                        $result = $relation->update($fields);
                    }

                    if (!$result) {
                        $errors[$i] = 'Ошибка обновления связи ['.$row['id'].']';
                    } else {
                        $response[] = (int)$row['id'];
                    }

                    break;
                case 'delete':
                    $result = false;
                    $relation = TaskFileRelation::find($row['id']);
                    if ($relation) {
                        $result = $relation->delete();
                    }

                    if (!$result) {
                        $errors[$i] = 'Ошибка удаления связи ['.$row['id'].']';
                    }
                    break;
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        return $response;
    }


    public function prepareFieldsWithMapping($trigger, $task = false): array
    {
        $newTaskFields = [];
        if (!$task) {
            $task = $this->task;
        }
        if ($trigger) {
            $newTaskFields = $this->getObserverNewTaskData($trigger, $task);

            if (!empty($trigger->OBSERVER_FIELDS))
            {
                foreach ($trigger->OBSERVER_FIELDS as $observerTaskField)
                {
                    if (isset($task[$observerTaskField->FIELD_NAME])) {
                        $value = $task[$observerTaskField->FIELD_NAME];

                        switch ($observerTaskField->USER_TYPE_ID)
                        {
                            case 'disk_file':
                                if (is_array($value)) {
                                    foreach ($value as $key => $vl) {
                                        if (!empty($vl) && strpos($vl, 'n') === false) {
                                            $attachedObject = Disk\AttachedObject::getById($vl);
                                            if ($attachedObject) {
                                                $fileObject = Disk\File::loadById($attachedObject->getObjectId());
                                                if ($fileObject) {
                                                    $value[$key] = 'n'.$fileObject->getId();
                                                } else {
                                                    unset($value[$key]);
                                                }
                                            } else {
                                                unset($value[$key]);
                                            }
                                        }
                                    }
                                }
                                break;
                        }

                        $mappingField = $this->getFieldByObserverFieldId($observerTaskField->ID);

                        $newTaskFields[$mappingField->FIELD_NAME] = $value;

                    }
                }


            }
        }

        return $newTaskFields;
    }

    public function index($taskId)
    {
        $result = [];

        $this->init($taskId);

        foreach ($this->sections as $section) {
            //Собираем поля
            $fields = [];
            foreach ($this->sectionCodeFields as $code) {
                $fieldCode = 'UF_' . strtoupper(ltrim(preg_replace('/[A-Z]/', '_$0', $code)));
                $fields[$code] = [
                    'type' => $code,
                    'code' => $section['mappingFields'][$fieldCode]
                ];
            }

            //Получаем связи
            $relations = [];
            $fileRelations = TaskFileRelation::where('UF_TASK_ID', $this->taskId)
                ->where('UF_SECTION', $section['id'])->get();

            if ($fileRelations) {

                foreach ($fileRelations as $fileRelation) {
                    $childTask = [];
                    if ($fileRelation->UF_CHILD_TASK_ID) {
                        $obTask = \CTaskItem::getInstance($fileRelation->UF_CHILD_TASK_ID, 1);
                        $childTask = $obTask->getData();
                    }

                    $relation = [
                        'id' => $fileRelation->ID,
                        'taskId' => $fileRelation->UF_CHILD_TASK_ID,
                        'groupId' => $childTask['GROUP_ID'] ?: false
                    ];

                    foreach ($fields as $code => $field) {
                        $codeField = 'UF_' . strtoupper(ltrim(preg_replace('/[A-Z]/', '_$0', $code)));
                        $relation[$code] = $fileRelation->$codeField;
                    }
                    $relations[] = $relation;
                }
            }

            $row = [
                'name' => $section['name'],
                'id' => $section['id'],
                'fields' => $fields,
                'relations' => $relations
            ];

            $result[] = $row;

        }

        return $result;
    }

    public function index2($taskId)
    {
        return FieldSection::all();
    }
}
