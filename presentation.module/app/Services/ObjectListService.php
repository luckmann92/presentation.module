<?php
namespace PresentModule\App\Services;

use Laravel\Illuminate\App\Services\Service;
use PresentModule\App\Models\Contract\Contract;

class ObjectListService extends Service
{
	public const ADDITIONAL_FIELDS = [
		[
			'model' => Contract::class,
			'localField' => 'UF_CONTRACT',
			'fields' => ['UF_RENAMING_REASON', 'UF_DS_VALUE', 'UF_CONTRACTS_LINK']
		],
	];
}
