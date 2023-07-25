<?php

namespace PresentModule\DB;

class contracts
{
	const HLBLOCK_NAME = 'tdb_contracts';
	const ENTITY_NAME = 'Договоры';

	public static function getFields()
	{
		return [
			[
				'CODE' => 'UF_RENAMING_REASON',
				'TYPE' => 'string',
				'NAME' => 'Основание переименования',
				'SHOW_FILTER' => 'S',
			],
			[
				'CODE' => 'UF_CONTRACTS_LINK',
				'TYPE' => 'string',
				'NAME' => 'Ссылка на договоры',
				'SHOW_FILTER' => 'S',
			],
			[
				'CODE' => 'UF_DS_VALUE',
				'TYPE' => 'string',
				'NAME' => 'Количество ДС',
				'SHOW_FILTER' => 'S',
			],
		];
	}
}
