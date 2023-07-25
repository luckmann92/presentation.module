<?php

namespace PresentModule\App\Http\Controllers;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use PresentModule\App\Services\GroupShowService;

class GroupController extends ApiController
{
	public function getData($id)
	{
		try {
			return self::response(app()->make(GroupShowService::class)->make($id));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}
}
