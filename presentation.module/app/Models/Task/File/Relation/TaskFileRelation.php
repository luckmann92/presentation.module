<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task\File\Relation;

use Laravel\Illuminate\App\Models\Base;

class TaskFileRelation extends Base
{
    protected $table = 'rn_task_file_doc_relation';

    public $timestamps = false;
    protected $primaryKey = 'ID';
    protected $fillable = [
        'UF_SECTION',
        'UF_CONTRACT',
        'UF_INVOICE',
        'UF_PAYMENT_ORDER',
        'UF_ACT',
        'UF_SF',
        'UF_TASK_ID',
        'UF_CREATED_BY',
        'UF_CREATED_DATE',
        'UF_CHANGED_BY',
        'UF_CHANGED_DATE',
        'UF_CHILD_TASK_ID'
    ];

}
