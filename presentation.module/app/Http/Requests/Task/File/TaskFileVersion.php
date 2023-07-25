<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Http\Requests\Task\File;

use Laravel\Illuminate\App\Http\Requests\ApiFormRequest;

class TaskFileVersion extends ApiFormRequest
{
    public static function init(array $request, array $rules = [], $isGraphQL = false)
    {
        $rules = [
            'TASK_ID' => [
                'required',
                'exists:b_tasks,ID'
            ],
            'SECTION_ID' => [
                'required',
                'exists:tdb_task_field_section,ID'
            ],
            /*'USER_ID' => [
                'required',
                'exists:b_user,ID'
            ],*/
        ];

        return parent::init($request, $rules);
    }
}
