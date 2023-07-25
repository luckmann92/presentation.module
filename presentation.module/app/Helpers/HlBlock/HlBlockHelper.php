<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Helpers\HlBlock;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

class HlBlockHelper
{
    public static function getTableNameById($id)
    {
        $hlBlock = HLBT::getList([
            'filter' => ['ID' => $id]
        ])->fetch();

        return $hlBlock['TABLE_NAME'];
    }

	public static function getHlBlockIdByName($name)
	{
		return HLBT::getRow(['filter' => ['NAME' => $name]])['ID'];
	}

	public static function getHlBlockByName($name)
	{
		return HLBT::getRow(['filter' => ['NAME' => $name]]);
	}

	public static function getHlBlockIdByTableName($name)
	{
		return HLBT::getRow(['filter' => ['TABLE_NAME' => $name]])['ID'];
	}

    public static function getFieldNameById($id)
    {
        $arField = \CUserTypeEntity::GetList([], ['ID' => $id])->Fetch();

        if ($arField['FIELD_NAME']) {
            return $arField['FIELD_NAME'];
        }
        return false;
    }

    public static function getFieldById($id)
    {
        return \CUserTypeEntity::GetList([], ['ID' => $id])->Fetch();
    }

    public static function getEntityClass($HLBlockId)
    {
        $HlBlock = HLBT::getById($HLBlockId)->fetch();
        return HLBT::compileEntity($HlBlock)->getDataClass();
    }

    public static function getFields($entityId): array
    {
        $entity = new \CUserTypeManager;
        return $entity->GetUserFields('HLBLOCK_' . $entityId, 0, LANGUAGE_ID);
    }
}
