<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Exchange\External;

use Laravel\Illuminate\App\Repositories\EloquentRepository;
use Laravel\Illuminate\App\Services\Service;
use PresentModule\App\Models\Group\Group;

class GroupIndexService extends Service
{
    public function index($request)
    {
        $repository = (new EloquentRepository())->setModel(new Group());
        $request['filter']['VISIBLE'] = 'N';
        return $repository->index($request, false, true);
    }
}
