<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\Base;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class FieldSettings extends Base
{
    protected $table = 'tdb_field_settings';

	public $timestamps = false;
    protected $primaryKey = 'ID';
    protected $fillable = ['UF_FIELD_ID', 'UF_MANDATORY', 'UF_STAGE_ID'];

    protected $attributes = [
    	'UF_MANDATORY' => 0
	];

	public function field(): BelongsTo
	{
		return $this->belongsTo(
			TaskUserFields::class,
			'UF_FIELD_ID',
			'ID',
		)->with(['settings', 'lang']);
	}
}
