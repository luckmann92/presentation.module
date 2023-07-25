<?php
namespace PresentModule\App\Observers;

use Laravel\Illuminate\Exceptions\ApiException;
use PresentModule\App\Models\Task\File\FileVersion;

class TaskFieldSectionRelationObserver
{
	public function saving($relation)
	{
		$fieldFileVersions = FileVersion::where('FIELD_ID', '=', $relation->UF_FIELD_ID)->get();
		if (!$fieldFileVersions->isEmpty()) {
			throw new ApiException('У поля есть версии');
		}
	}
}
