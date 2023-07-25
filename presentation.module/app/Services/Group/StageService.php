<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Services\Group;

use Laravel\Illuminate\App\Models\B24\Task\TaskUserData;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Services\Service;
use Illuminate\Http\Request;
use PresentModule\App\Helpers\HlBlock\HlBlockHelper;
use PresentModule\App\Models\Group\Stage;
use PresentModule\App\Models\Task\ProjectSectionSettings;
use PresentModule\App\Models\Task\ProjectTaskFieldRelation;
use PresentModule\App\Models\Task\TaskFieldSectionRelation;
use PresentModule\App\Models\Task\TaskUserFields;

class StageService extends Service
{
    public function index($id)
    {
        return Stage::where('ENTITY_TYPE', 'G')
            ->where('ENTITY_ID', $id)
            ->orderBy('SORT')
            ->get();
    }
}
