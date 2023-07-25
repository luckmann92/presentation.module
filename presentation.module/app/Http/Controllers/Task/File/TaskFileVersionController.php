<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Controllers\Task\File;

use Illuminate\Http\Request;
use PresentModule\App\Models\Task\File\FileVersion;
use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use PresentModule\App\Http\Requests\Task\File\TaskFileVersion;
use PresentModule\App\Services\Task\File\TaskFileVersionService;

class TaskFileVersionController extends ApiController
{
    public function get(Request $request)
    {
        try {
            return self::response(app()->make(TaskFileVersionService::class)->getVersions(
                $request->get('TASK_ID')
            ));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $validated = TaskFileVersion::init($request->all());

        if (!$validated)
        {
            return self::unsuccessfulResponse();
        }

        try {
            return self::response(app()->make(TaskFileVersionService::class)->update($request->all()));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }

    public function rollback(Request $request)
    {
        $validated = TaskFileVersion::init($request->all());

        if (!$validated)
        {
            return self::unsuccessfulResponse();
        }

        try {
            return self::response(app()->make(TaskFileVersionService::class)->rollback($request));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
