<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Group;

use Laravel\Illuminate\App\Models\Base;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Type extends Base
{
    protected $table = 'tdb_group_types';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	public function stages(): HasManyThrough
	{
		return $this->hasManyThrough(
			TaskStage::class,
			TypeTaskStage::class,
			'UF_GROUP_TYPE',
			'ID',
			'ID',
			'UF_TASK_STAGE'
		);
	}
}
