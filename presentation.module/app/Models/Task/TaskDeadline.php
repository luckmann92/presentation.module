<?php
namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\Base;

class TaskDeadline extends Base
{
    protected $table = 'tdb_task_deadlines';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_STAGE', 'UF_DAYS'];
}
