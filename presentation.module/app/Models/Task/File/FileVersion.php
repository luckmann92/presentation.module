<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task\File;

use Laravel\Illuminate\App\Models\Base;

class FileVersion extends Base
{
    protected $table = 'rn_task_file_version';

    protected $primaryKey = 'ID';
}
