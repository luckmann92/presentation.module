<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Controllers\Task;

use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Laravel\Illuminate\Exceptions\ApiException;
use Illuminate\Http\Request;
use PresentModule\App\Services\GroupShowService;
use PresentModule\App\Services\Task\TaskFieldsService;

class TaskFieldsController extends ApiController
{
	protected $request;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function group()
    {
        try {
            return self::response(app()->make(TaskFieldsService::class)->group());
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }

    public function setFieldSettings()
    {
        try {
            return self::response(app()->make(TaskFieldsService::class)->setFieldSettings($this->request));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }

    public function setFieldRelation()
	{
		try {
			return self::response(app()->make(TaskFieldsService::class)->setRelation($this->request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function setSectionRelation()
	{
		try {
			return self::response(app()->make(TaskFieldsService::class)->setSectionRelation($this->request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function getTaskViewComponent($id)
	{
		try {
			return self::response(app()->make(TaskFieldsService::class)->taskViewComponent($id));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function setSettings()
	{
		try {
			return self::response(app()->make(TaskFieldsService::class)->setSettings($this->request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}

	public function getSomething()
	{
		try {
			return self::response(app()->make(TaskFieldsService::class)->getSomething($this->request));
		} catch (ApiException $e) {
			return self::unsuccessfulResponse(['error' => $e->getMessage()]);
		}
	}
}
