<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\B24\UserField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TaskUserFields extends UserField
{
    public function section(): HasOneThrough
    {
        return $this->hasOneThrough(
			FieldSection::class,
			TaskFieldSectionRelation::class,
            'UF_FIELD_ID',
            'ID',
            'ID',
            'UF_SECTION_ID'
        );
    }

	public function settings(): BelongsTo
	{
		return $this->belongsTo(
			FieldSettings::class,
			'ID',
			'UF_FIELD_ID',
		);
	}

    public function scopeTaskUserFields($query, $columns = []): Builder
    {
    	if ($columns) {
			return $query->where('ENTITY_ID', 'TASKS_TASK')->with('section')->whereIn('FIELD_NAME', $columns);
		} else {
			return $query->where('ENTITY_ID', 'TASKS_TASK')->with('section');
		}
    }
}
