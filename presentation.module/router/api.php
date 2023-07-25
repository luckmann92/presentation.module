<?php

use Bitrix\Main\Loader;
use Laravel\Illuminate\App\Http\Controllers\AuthController;
use Illuminate\Routing\Router;
use PresentModule\App\Http\Controllers\HlBlockController;
use PresentModule\App\Http\Controllers\ParseController;
use PresentModule\App\Http\Controllers\PassportController;
use PresentModule\App\Http\Controllers\Task\External\Reference\TypeWorkController;
use PresentModule\App\Http\Controllers\TaskController;
use PresentModule\App\Http\Controllers\GroupController;
use PresentModule\App\Http\Controllers\Task\External\Reference\GroupController as ExternalGroupController;
use PresentModule\App\Http\Controllers\Task\External\Reference\RsoController as ExternalRsoController;
use PresentModule\App\Http\Controllers\Task\FieldSectionController;
use PresentModule\App\Http\Controllers\Task\TaskFieldsController;
use PresentModule\App\Http\Controllers\Task\File\TaskFileVersionController;
use PresentModule\App\Http\Controllers\Task\File\Relation\TaskFileRelationController;
use PresentModule\App\Http\Controllers\Task\External\TaskApiController;
use PresentModule\App\Services\Dependent\NotificationsService;

Loader::includeModule('presentation.module');
/** @var $router Router */

//$router->get('/parse', [ParseController::class, 'run']);

$router->middleware('bitrix-auth')->prefix('category')->group(
	function () use ($router) {
		$router->get('/', [TaskController::class, 'index']);
	}
);

$router->middleware('bitrix-auth')->prefix('group')->group(
	function () use ($router) {
		$router->get('/{id}', [GroupController::class, 'getData']);
		$router->post('/setNotifications', [NotificationsService::class, 'setNotifications']);
		//$router->get('/{id}/fields', [GroupController::class, 'getRequiredFields']);
	}
);

$router->prefix('type-work-list')->middleware('jwt')->group(
    function () use ($router) {
        $router->get('/', [TypeWorkController::class, 'index']);
    }
);
$router->prefix('group-list')->middleware('jwt')->group(
    function () use ($router) {
        $router->get('/', [ExternalGroupController::class, 'index']);
    }
);

$router->middleware('bitrix-auth')->prefix('task')->group(
    function () use ($router) {
        $router->get('/fields/group', [FieldSectionController::class, 'index']);
        $router->post('/fields/setRelation', [TaskFieldsController::class, 'setFieldRelation']);
        $router->post('/fields/setSectionRelation', [TaskFieldsController::class, 'setSectionRelation']);
        $router->post('/section/setSettings', [TaskFieldsController::class, 'setSettings']);
        $router->post('/field/setSettings', [TaskFieldsController::class, 'setFieldSettings']);
		$router->get('/components/taskView/{id}', [TaskFieldsController::class, 'getTaskViewComponent']);
		$router->post('/fields/getSomething', [TaskFieldsController::class, 'getSomething']);
		$router->post('/deadline', [TaskController::class, 'setDeadline']);
		$router->post('/getDeadlines', [TaskController::class, 'getDeadlines']);
		$router->post('/getTimestamps', [TaskController::class, 'getTimestamps']);

		$router->prefix('file')->group(
            function () use ($router) {
                $router->post('/version/update', [TaskFileVersionController::class, 'update']);
                $router->post('/version/rollback', [TaskFileVersionController::class, 'rollback']);
                $router->get('/version/get', [TaskFileVersionController::class, 'get']);

                $router->prefix('relation')->group(
                    function () use ($router) {
                        $router->post('/{taskId}/store', [TaskFileRelationController::class, 'store']);
                        $router->get('/{taskId}', [TaskFileRelationController::class, 'index']);
                    }
                );
            }
        );
    }
);

$router->prefix('auth')->group(
	function () use ($router) {
		$router->post('/login', [AuthController::class, 'login']);

		$router->middleware('jwt')->group(
			function () use ($router) {
				$router->post('/logout', [AuthController::class, 'logout']);
				$router->post('/refresh', [AuthController::class, 'refresh']);
				$router->post('/me', [AuthController::class, 'me']);
			}
		);
	}
);
