<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Controllers\Task;

use Bitrix\Calendar\Sync\Exceptions\ApiException;
use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Laravel\Illuminate\App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Services\Task\FieldSectionService;

class FieldSectionController extends ApiController
{
    protected FieldSection $fieldSections;
    protected EloquentRepository $repository;

    public function __construct(FieldSection $fieldSections, EloquentRepository $repository)
    {
        $this->fieldSections = $fieldSections;
        $this->repository = $repository;

        $this->repository->setModel($fieldSections);
    }

    public function index(Request $request)
    {
        try {
            return self::response(app()->make(FieldSectionService::class)->index($request->get('groupId'), false, true));
        } catch (ApiException $e) {
            return self::unsuccessfulResponse(['error' => $e->getMessage()]);
        }
    }
}
