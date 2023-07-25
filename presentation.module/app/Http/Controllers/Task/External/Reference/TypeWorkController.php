<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
namespace PresentModule\App\Http\Controllers\Task\External\Reference;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Illuminate\Http\Request;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use PresentModule\App\Services\Exchange\External\Reference\TypeWorkService;

class TypeWorkController extends ApiController
{
    public function index(Request $request)
    {
        try {
            return self::response(app()->make(TypeWorkService::class)->index(
                $request->all()
            ));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
