<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Group;

use Laravel\Illuminate\App\Models\Base;

class TaskStage extends Base
{
    protected $table = 'tdb_group_task_stage';
	public $timestamps = false;
	protected $primaryKey = 'ID';
}
