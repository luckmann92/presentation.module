<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Handlers\UserFields;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\UserField\Types\EnumType;
use Laravel\Illuminate\App\Models\B24\UserField;

Loader::includeModule('highloadblock');

class CUserTypeHlBlockDetail extends \CUserTypeHlblock
{
    const USER_TYPE_ID = "hlblock_detail";

    public const DISPLAY_LIST = 'LIST';
    public const DISPLAY_CHECKBOX = 'CHECKBOX';

    public static function GetUserTypeDescription(): array
    {
        return array(
            "USER_TYPE_ID" => self::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => 'Детальная информация HL элемента',
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_INT,
        );
    }

    public static function getHlRows($userfield, $clearValues = false): array
    {
        global $USER_FIELD_MANAGER;

        $rows = array();

        $hlblock_id = (int)$userfield['SETTINGS']['HLBLOCK_ID'];
        $hlfield_id = (int)$userfield['SETTINGS']['HLFIELD_ID'];

		$fieldData = $userfield;
		$fieldData['SETTINGS']['ADD_FIELD'] = self::getAdditionalFields($fieldData);

		if ($hlfield_id <= 0)
        {
            $hlfield_id = 0;
        }

        if (!empty($hlblock_id))
        {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlblock_id)->fetch();
        }

        if (!empty($hlblock))
        {
            $userfield = null;

            if ($hlfield_id > 0)
            {
                $iterator = Main\UserFieldTable::getList([
                    'select' => [
                        '*',
                    ],
                    'filter' => [
                        '=ENTITY_ID' => HighloadBlockTable::compileEntityId($hlblock['ID']),
                        '=ID' => $hlfield_id,
                    ],
                ]);
                $row = $iterator->fetch();
                unset($iterator);
                if (!empty($row))
                {
                    $row['USER_TYPE'] = $USER_FIELD_MANAGER->GetUserType($row['USER_TYPE_ID']);
                    $userfield = $row;
                }
                else
                {
                    $hlfield_id = 0;
                }
            }

            if ($hlfield_id == 0)
            {
                $userfield = array('FIELD_NAME' => 'ID');
            }

            if ($userfield)
            {
				$select = ['ID', $userfield['FIELD_NAME']];
				if ($fieldData['SETTINGS']['ADD_FIELD']) {
					if (is_array($fieldData['SETTINGS']['ADD_FIELD'])) {
						$select = array_merge($select, $fieldData['SETTINGS']['ADD_FIELD']);
					} else {
						$select[] = $fieldData['SETTINGS']['ADD_FIELD'];
					}
				}

                // validated successfully. get data
                $hlDataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
                $rows = $hlDataClass::getList(array(
                    'select' => $select,
                    'order' => 'ID'
                ))->fetchAll();

                foreach ($rows as &$row)
                {
                    $row['ID'] = (int)$row['ID'];
                    if ($userfield['FIELD_NAME'] == 'ID')
                    {
                        $row['VALUE'] = $row['ID'];
                    }
                    else
                    {
                        //see #0088117
                        if ($userfield['USER_TYPE_ID'] != 'enumeration' && $clearValues)
                        {
                            $row['VALUE'] = $row[$userfield['FIELD_NAME']];
                        }
                        else
                        {
                            $row['VALUE'] = $USER_FIELD_MANAGER->getListView($userfield, $row[$userfield['FIELD_NAME']]);
                        }

						if ($fieldData['SETTINGS']['ADD_FIELD']) {
							foreach ($fieldData['SETTINGS']['ADD_FIELD'] as $addField) {
								if ($row[$addField]) {
									$row['VALUE'] .= ', '.self::getAdditionalFieldValue($addField, $row[$addField]);
								}
							}
						} else {
							$row['VALUE'] .= ' ['.$row['ID'].']';
						}
                    }
                }
            }
        }

        return $rows;
    }

    protected static function getAdditionalFields($fieldData)
	{
		$addFields = [];
		$field = UserField::find($fieldData['ID']);

		switch ($field->FIELD_NAME) {
			case 'UF_OBJECT':
				$addFields = ['UF_UID'];
				break;
		}

		return $addFields;
	}

	protected static function getAdditionalFieldValue($fieldCode, $value)
	{
		switch ($fieldCode) {
			case 'UF_UID':
				return '№'.$value;
		}

		return $value;
	}
}
