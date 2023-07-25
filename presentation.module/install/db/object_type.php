<?php

namespace PresentModule\DB;

class object_type
{
    const HLBLOCK_NAME = 'tdb_object_types';
    const ENTITY_NAME = 'Тип объекта';

	public static function getFields()
    {
        return [
			[
				'CODE' => 'UF_NAME',
				'TYPE' => 'string',
				'NAME' => 'Название',
				'SHOW_FILTER' => 'S',
			],
        ];
    }
}
