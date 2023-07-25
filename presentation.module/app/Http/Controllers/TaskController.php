<?php

namespace PresentModule\App\Http\Controllers;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use PresentModule\App\Services\Dependent\TaskDeadlinesService;
use PresentModule\App\Services\TaskEditService;

class TaskController extends ApiController
{
	public function index()
	{
		try {
			return self::response(app()->make(TaskEditService::class)->make());
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function setDeadline(Request $request)
	{
		try {
			return self::response(app()->make(TaskDeadlinesService::class)->setDeadline($request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function getDeadlines(Request $request)
	{
		try {
			return self::response(app()->make(TaskDeadlinesService::class)->getDeadlines($request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function getTimestamps(Request $request)
	{
		try {
			return self::response(app()->make(TaskDeadlinesService::class)->getTimestamps($request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}
}
