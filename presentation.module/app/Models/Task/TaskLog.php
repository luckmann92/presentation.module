<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\B24\Task\Task as TaskBase;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use Laravel\Illuminate\App\Models\B24\Task\TaskUserData;
use Laravel\Illuminate\App\Models\Bitrix\UserField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PresentModule\App\Models\Group\ForumMessage;
use PresentModule\App\Models\Group\ForumTopic;
use PresentModule\App\Models\Reference\ApprovalMes;
use PresentModule\App\Models\Reference\ApprovalMgen;
use PresentModule\App\Models\Reference\ApprovalRmr;
use PresentModule\App\Models\Reference\LandingTp;
use PresentModule\App\Models\Reference\LinkingNetworks;
use PresentModule\App\Models\Reference\RsoRequestMethod;
use PresentModule\App\Models\Reference\TuType;
use PresentModule\App\Models\Task\File\FileVersion;

class TaskLog extends TaskBase
{
    protected $table = 'b_tasks_log';
    protected $primaryKey = 'ID';
}
