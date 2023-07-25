<?php

namespace PresentModule\DB;

use PresentModule\App\Helpers\HlBlock\HlHelper as HL;

class Install
{
    public static function execute()
    {
        $userTypeEnum = new \CUserFieldEnum();

        $arTables = self::getTables();

        foreach ($arTables as $tableClassName) {
            $arFields = $tableClassName::getFields();

            $hl = new HL();
            $arBlock = $hl->getBlockByTableName($tableClassName::HLBLOCK_NAME);
            if (!$arBlock) {
                $arBlockFields = [
                    'TABLE_NAME' => $tableClassName::HLBLOCK_NAME,
                    'NAME' => implode('', explode(' ', ucwords(str_replace('_', ' ', $tableClassName::HLBLOCK_NAME))))
                ];
                $result = $hl->addBlock($arBlockFields);

                if ($result['result']) {
                    $arBlockFields['ID'] = $result['id'];
                    $arBlock = $arBlockFields;
                }
            }

            $hl->setHlBlockId($arBlock['ID']);

            foreach ($arFields as $field) {
                $res = $hl->addField($field);

                switch ($field['TYPE']) {
                    case 'enumeration':
                        $userTypeEnum->SetEnumValues($res['id'], $field['ENUM']);
                        break;
                }
            }

            if (method_exists($tableClassName, 'getItems'))
			{
				foreach ($tableClassName::getItems() as $item)
				{
					$hl->add($item);
				}
			}
        }

		foreach ($arTables as $tableClassName) {
			$arInstallFields = $tableClassName::getFields();

			$hl = new HL();
			$arBlock = $hl->getBlockByTableName($tableClassName::HLBLOCK_NAME);
			$hl->setHlBlockId($arBlock['ID']);
			$arFields = $hl->getFields();

			foreach ($arInstallFields as $field) {
				switch ($field['TYPE']) {
					case 'hlblock':
						if ($field['SETTINGS']) {
							$obLinkedHl = new HL();
							$arLinkedBlock = $hl->getBlockByTableName($field['SETTINGS']['HLBLOCK_CODE']);
							$obLinkedHl->setHlBlockId($arLinkedBlock['ID']);
							$linkedBlockFields = $obLinkedHl->getFields();

							$fieldData = [
								'SETTINGS' => [
									'HLBLOCK_ID' => $arLinkedBlock['ID'],
									'HLFIELD_ID' => $linkedBlockFields[$field['SETTINGS']['HLFIELD_CODE']]['ID']
								]
							];
							$hl->updateField($arFields[$field['CODE']]['ID'], $fieldData);
						}
						break;
				}
			}
		}
    }

    public static function getTables()
    {
        $arResult = [];
        foreach (glob(__DIR__ . '/*.php') as $file) {
            $fileName = str_replace(__DIR__ . '/', '', $file);
            if (strpos($fileName, 'Install') === false) {
                require_once $file;
				$arResult[] = __NAMESPACE__ . '\\' . str_replace('.php', '', $fileName);
            }
        }

        return $arResult;
    }

    public static function unInstall()
    {
        $hl = new HL();

        foreach (self::getTables() as $tableClassName) {
            $arBlock = $hl->getBlockByTableName($tableClassName::HLBLOCK_NAME);
            if ($arBlock) {
                $hl->deleteBlock($arBlock['ID']);
            }
        }
    }
}
