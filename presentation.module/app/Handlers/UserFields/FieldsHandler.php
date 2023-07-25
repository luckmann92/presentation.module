<?php
namespace PresentModule\App\Handlers\UserFields;

use PresentModule\App\Models\Task\FieldSettings;

class FieldsHandler
{
	public static function onAfterUserTypeAdd($fields)
	{
		if ($fields['ENTITY_ID'] != 'TASKS_TASK') {
			return true;
		}

		$fieldSettings = new FieldSettings();
		$fieldSettings->UF_FIELD_ID = $fields['ID'];
		$fieldSettings->save();
	}
}
