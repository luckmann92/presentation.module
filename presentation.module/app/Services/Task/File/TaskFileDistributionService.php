<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Task\File;

use Bitrix\Main\Engine\CurrentUser;
use \Bitrix\Disk;
use Bitrix\Main\Loader;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use PresentModule\App\Models\Object\ConstructionObject;
use PresentModule\App\Models\Task\File\FileVersion;
use PresentModule\App\Models\Task\File\GroupObjectDirRelation;
use PresentModule\App\Services\Task\FieldSectionService;

Loader::includeModule('tasks');

class TaskFileDistributionService
{
    protected $group;
    protected $task;
    protected $taskId;
    protected $groupId;
    protected $storage;
    protected $companyId;

    public function init($taskId)
    {
        $this->taskId = $taskId;

        $obTask = new \CTaskItem($taskId, 1);
        $this->task = $obTask->getData();

        if ($this->task['GROUP_ID'] > 0)
        {
            $this->groupId = $this->task['GROUP_ID'];
            $this->group = \CSocNetGroup::getById($this->groupId);
            $this->storage = Disk\Driver::getInstance()->getStorageByGroupId($this->groupId);
            $this->companyId = $this->task['UF_RSO'];
        }
    }

    public function distributionFiles()
    {
        $sections = app()->make(FieldSectionService::class)->index($this->groupId);

        $userFieldIds = UserField::where('ENTITY_ID', 'TASKS_TASK')
            ->where('USER_TYPE_ID', 'disk_file')
            ->get()
            ->pluck('ID')
            ->toArray();

        foreach ($sections as $section)
        {
			$versionFields = FileVersion::whereIn('FIELD_ID', array_keys($section['fields']))
				->where('TASK_ID', '=', $this->taskId)
				->get();

			if (!$versionFields->isEmpty()) {
				foreach ($versionFields as $versionField) {
					if (!in_array($versionField->FIELD_ID, $userFieldIds)) {
						continue;
					}

					$folderId = $this->getFolderIdForVersion($section, $versionField);

					if ($folderId) {
						foreach (unserialize($versionField->FILE_ID) as $fileId) {
							$attachedObject = Disk\AttachedObject::getById($fileId);

							if ($attachedObject) {
								$fileObject = Disk\File::loadById($attachedObject->getObjectId());

								if ($fileObject) {
									$fileObject->moveToAnotherFolder(
										Disk\Folder::loadById($folderId),
										CurrentUser::get()->getId()
									);
								}
							}
						}
					}
				}
			}

            foreach ($section['fields'] as $fieldId => $code)
            {
                if (in_array($fieldId, $userFieldIds) && isset($this->task[$code]) && !empty($this->task[$code]))
                {
					$folderId = $this->getFolderId($section);

					if ($code == 'UF_INITIAL_DATA') {
						$folderId = $this->getFolderIdForInitialData();
					}

                    foreach ($this->task[$code] as $attachedId) {
                        if ($folderId) {
                            $attachedObject = Disk\AttachedObject::getById($attachedId);

                            if ($attachedObject) {
                                $fileObject = Disk\File::loadById($attachedObject->getObjectId());

                                if ($fileObject) {
                                    $fileObject->moveToAnotherFolder(
                                        Disk\Folder::loadById($folderId),
                                        CurrentUser::get()->getId()
                                    );
                                }
                            }
                        }
                    }
                }
            }
		}
    }

    public function getFolderIdForVersion($section, $versionField)
	{
		$rootFolderId = $this->getRootFolderId();
		$companyFolderId = $this->getCompanyFolderId($rootFolderId);
		$sectionFolderId = $this->getFolderId($section);

		if ($companyFolderId && $sectionFolderId)
		{
			$result = GroupObjectDirRelation::where('GROUP_ID', $this->groupId)
				->where('ENTITY_ID', $versionField->VERSION)
				->where('TYPE', 'sectionVersion')
				->where('PARENT_ID', $sectionFolderId)
				->first();

			if (!$result) {
				$rightsManager = Disk\Driver::getInstance()->getRightsManager();
				$rootFolder = Disk\Folder::loadById($sectionFolderId);

				$folder = $rootFolder->addSubFolder([
					'NAME' => 'Версия '.$versionField->VERSION,
					'CREATED_BY' => CurrentUser::get()->getId()
				],
					[
						[
							'ACCESS_CODE' => 'G'.$this->groupId,
							'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
						]
					]
				);

				if ($folder)
				{
					(new EloquentRepository())->setModel(GroupObjectDirRelation::class)->insert([
						'GROUP_ID' => $this->groupId,
						'ENTITY_ID' => $versionField->VERSION,
						'DIR_ID' => $folder->getId(),
						'PARENT_ID' => $sectionFolderId,
						'TYPE' => 'sectionVersion'
					]);

					return $folder->getId();
				}

				return false;
			}

			return $result->DIR_ID;
		}

		return $this->getRootFolderId();
	}

    public function getFolderIdForInitialData()
	{
		$rootFolderId = $this->getRootFolderId();
		$companyFolderId = $this->getCompanyFolderId($rootFolderId);

		if ($companyFolderId)
		{
			$result = GroupObjectDirRelation::where('GROUP_ID', $this->groupId)
				->where('ENTITY_ID', 1)
				->where('TYPE', 'initialData')
				->where('PARENT_ID', $companyFolderId)
				->first();

			if (!$result) {
				$rightsManager = Disk\Driver::getInstance()->getRightsManager();
				$rootFolder = Disk\Folder::loadById($companyFolderId);

				$folder = $rootFolder->addSubFolder([
					'NAME' => 'Исходные данные',
					'CREATED_BY' => CurrentUser::get()->getId()
				],
					[
						[
							'ACCESS_CODE' => 'G'.$this->groupId,
							'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
						]
					]
				);

				if ($folder)
				{
					(new EloquentRepository())->setModel(GroupObjectDirRelation::class)->insert([
						'GROUP_ID' => $this->groupId,
						'ENTITY_ID' => 1,
						'DIR_ID' => $folder->getId(),
						'PARENT_ID' => $companyFolderId,
						'TYPE' => 'initialData'
					]);

					return $folder->getId();
				}

				return false;
			}

			return $result->DIR_ID;
		}

		return $this->getRootFolderId();
	}

    public function getFolderId($section)
    {
    	$rootFolderId = $this->getRootFolderId();
		$companyFolderId = $this->getCompanyFolderId($rootFolderId);

		if ($section['id'] == 1) {
            return $companyFolderId;
        }

        if ($companyFolderId)
        {
            $result = GroupObjectDirRelation::where('GROUP_ID', $this->groupId)
                ->where('ENTITY_ID', $section['id'])
                ->where('TYPE', 'section')
                ->where('PARENT_ID', $companyFolderId)
                ->first();

            if (!$result) {
                $rightsManager = Disk\Driver::getInstance()->getRightsManager();

                $rootFolder = Disk\Folder::loadById($companyFolderId);

                $folder = $rootFolder->addSubFolder([
                    'NAME' => $this->clearDirName($section['name']), //Подставляем идентификаторы для удобства поиска
                    'CREATED_BY' => CurrentUser::get()->getId()
                ],
                    [
                        [
                            'ACCESS_CODE' => 'G'.$this->groupId,
                            'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
                        ]
                    ]
                );

                //Случае ошибки создания проверяем наличие по идентификаторам
                if ($rootFolder->getErrors())
                {
                    $folder = $rootFolder->getChild(['?NAME' => $this->clearDirName($section['name'])]);
                }

                if ($folder)
                {
                    (new EloquentRepository())->setModel(GroupObjectDirRelation::class)->insert([
                        'GROUP_ID' => $this->groupId,
                        'ENTITY_ID' => $section['id'],
                        'DIR_ID' => $folder->getId(),
                        'PARENT_ID' => $companyFolderId,
                        'TYPE' => 'section'
                    ]);

                    return $folder->getId();
                }

                return false;
            }

            return $result->DIR_ID;
        }

        return $this->getRootFolderId();
    }

    public function getCompanyFolderId($rootFolderId)
    {
        if ($this->companyId)
        {
            $result = GroupObjectDirRelation::where('GROUP_ID', $this->groupId)
                ->where('ENTITY_ID', $this->companyId)
                ->where('TYPE', 'company')
                ->where('PARENT_ID', $rootFolderId)
                ->first();

            if (!$result)
            {
                $rightsManager = Disk\Driver::getInstance()->getRightsManager();
                $company = \CCrmCompany::GetByID($this->companyId);

                $rootFolder = Disk\Folder::loadById($this->getRootFolderId());

                $subFolderName = $this->clearDirName($company['TITLE']);

                $folder = $rootFolder->addSubFolder([
                    'NAME' => trim($subFolderName).' '.$this->groupId.'_'.$this->companyId, //Подставляем идентификаторы для удобства поиска
                    'CREATED_BY' => CurrentUser::get()->getId()
                ],
                    [
                        [
                            'ACCESS_CODE' => 'G'.$this->groupId,
                            'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
                        ]
                    ]
                );

                //Случае ошибки создания проверяем наличие по идентификаторам
                if ($rootFolder->getErrors())
                {
                    $folder = $rootFolder->getChild(['?NAME' => ' '.$this->groupId.'_'.$this->companyId]);
                }

                if ($folder)
                {
                    (new EloquentRepository())->setModel(GroupObjectDirRelation::class)->insert([
                        'GROUP_ID' => $this->groupId,
                        'ENTITY_ID' => $this->companyId,
                        'DIR_ID' => $folder->getId(),
                        'PARENT_ID' => $rootFolderId,
                        'TYPE' => 'company'
                    ]);

                    return $folder->getId();
                }

                return false;
            }

            return $result->DIR_ID;
        }

        return $this->getRootFolderId();
    }

    public function getRootFolderId()
    {
        $result = GroupObjectDirRelation::where('GROUP_ID', $this->groupId)
            ->where('ENTITY_ID', $this->task['UF_OBJECT'])
            ->where('TYPE', 'root')
            ->first();

        if (!$result)
        {
            $rightsManager = Disk\Driver::getInstance()->getRightsManager();
            $object = ConstructionObject::find($this->task['UF_OBJECT']);

            $folder = $this->storage->addFolder([
                'NAME' => $this->clearDirName($object->UF_ADDRESS),
                'CREATED_BY' => CurrentUser::get()->getId()
            ],
                [
                    [
                        'ACCESS_CODE' => 'G'.$this->groupId,
                        'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
                    ]
                ]
            );

            $errors = $this->storage->getErrors();
            if ($folder === null && !empty($errors))
            {
                $folder = $this->storage->getChild(['?NAME' => $this->clearDirName($object->UF_ADDRESS)]);
            }

            (new EloquentRepository())->setModel(GroupObjectDirRelation::class)->insert([
                'GROUP_ID' => $this->groupId,
                'ENTITY_ID' => $this->task['UF_OBJECT'],
                'DIR_ID' => $folder->getId(),
                'TYPE' => 'root'
            ]);

            return $folder->getId();
        }

        return $result->DIR_ID;
    }

    public function clearDirName($name)
    {
		$name = str_replace('/', ' ', $name);
		$name = preg_replace('/\r\n|\r|\n/u', ' ', $name);

		return preg_replace('/[^a-zA-ZА-Яа-я0-9-_.,\s$]/u', '', $name);
    }
}
