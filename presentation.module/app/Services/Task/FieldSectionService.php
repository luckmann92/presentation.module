<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Task;

use Bitrix\Main\Loader;
use Laravel\Illuminate\App\Models\Bitrix\UserField;
use Laravel\Illuminate\App\Services\Service;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Models\Task\ProjectSectionSettings;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;


class FieldSectionService extends Service
{
    protected array $uFieldCodes = [
        'UF_CONTRACT', 'UF_INVOICE', 'UF_PAYMENT_ORDER', 'UF_ACT', 'UF_SF'
    ];
    /**
     * Получение Секций групп полей Задачи по GROUP_ID
     * @return array
     */
    public function index($groupId = false, $sectionId = false, $isSort = false)
    {
        $sectionFields = [];

        if ($groupId && $groupId > 0) {
            $groupFields = ProjectTaskFieldRelation::where('UF_GROUP_ID', $groupId)
                ->get()
                ->pluck('UF_FIELD_ID')
                ->toArray();
        }

        $repository = (new EloquentRepository())->setModel(new FieldSection());
        $sections = $repository->index([], 'groupFields', false);

        foreach ($sections as $section)
        {
            $code = \CUtil::translit($section->UF_NAME, 'ru');

            //Подставляем поля маппинга
            foreach ($this->uFieldCodes as $_code) {
                $_codeField = $_code.'_FIELD';
                $uField = UserField::find($section->$_codeField);
                $sectionFields[$code]['mappingFields'][$_code] = $uField->FIELD_NAME ?: false;
            }

            if ($section->fields)
            {
                $sectionFields[$code]['id'] = $section->ID;
				$sectionFields[$code]['name'] = $section->UF_NAME;
				$sectionFields[$code]['isIterate'] = false;

				if ($groupId && $groupId > 0) {
					$sectionSettings = ProjectSectionSettings::where('UF_SECTION_ID', '=', $section->ID)
						->where('UF_PROJECT_ID', '=', $groupId)->first();

					if ($sectionSettings && $sectionSettings->UF_IS_ITERATE) {
						$sectionFields[$code]['isIterate'] = true;
					}
				}

                foreach ($section->fields as $field)
                {
                    if ($groupId == 0)
                    {
                    	if ($isSort) {
							$sectionFields[$code]['fields'][] = $field->FIELD_NAME;
						} else {
							$sectionFields[$code]['fields'][$field->ID] = $field->FIELD_NAME;
						}
                    }
                    else
                    {
                        if (isset($groupFields)) {
                            if (is_array($groupFields) && in_array($field->ID, $groupFields)) {
								if ($isSort) {
									$sectionFields[$code]['fields'][] = $field->FIELD_NAME;
								} else {
									$sectionFields[$code]['fields'][$field->ID] = $field->FIELD_NAME;
								}
                            }
                        }
                    }
                }

                if (!isset($sectionFields[$code]['fields']))
                {
                    unset($sectionFields[$code]);
                }

                if ($sectionId && $section->ID == $sectionId)
                {
                    return $sectionFields[$code]['fields'];
                }
            }
        }

        return $sectionFields;
    }
}
