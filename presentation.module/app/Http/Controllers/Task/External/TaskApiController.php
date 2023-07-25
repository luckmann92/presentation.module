<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Controllers\Task\External;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Services\Task\External\TaskApiService;

class TaskApiController extends ApiController
{
    protected Task $taskModel;
    protected EloquentRepository $repository;

    public function __construct(Task $taskModel, EloquentRepository $repository)
    {
        $this->taskModel = $taskModel;
        $this->repository = $repository;

        $this->repository->setModel($taskModel);
    }

    public function index(Request $request)
    {
        try {
            return self::response(app()->make(TaskApiService::class)->index($request));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
