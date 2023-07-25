<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Controllers\Task\File\Relation;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Bitrix\Main\Context;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use PresentModule\App\Services\Task\File\Relation\TaskFileRelationService;

class TaskFileRelationController extends ApiController
{
    public function index($taskId)
    {
        try {
            return self::response(app()->make(TaskFileRelationService::class)->index($taskId));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }

    public function store($taskId)
    {
        try {
            return self::response(app()->make(TaskFileRelationService::class)->afterTaskUpdate($taskId));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
