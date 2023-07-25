<?php

namespace PresentModule\App\Http\Controllers\Task\External\Reference;

use Illuminate\Http\Request;
use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use PresentModule\App\Services\Exchange\External\GroupIndexService;

class GroupController extends ApiController
{
    public function index(Request $request)
    {
        try {
            return self::response(app()->make(GroupIndexService::class)->index($request->all()));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
