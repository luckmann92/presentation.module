<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\Base;

class TaskMappingField extends Base
{
    protected $table = 'tdb_task_mapping_field';
    public $timestamps = false;
    protected $primaryKey = 'ID';

}
