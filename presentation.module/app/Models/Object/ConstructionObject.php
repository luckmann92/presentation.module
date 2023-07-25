<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Object;

use Laravel\Illuminate\App\Models\Base;
use Laravel\Illuminate\Casts\Serialize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PresentModule\App\Models\Address\Country;
use PresentModule\App\Models\Address\District;
use PresentModule\App\Models\Contract\Contract;
use PresentModule\App\Models\Employee;

class ConstructionObject extends Base
{
    protected $table = 'tdb_objects';
	public $timestamps = false;
	protected $primaryKey = 'ID';
	protected $fillable = ['UF_UID', 'UF_EXT_UID', 'UF_ADDRESS'];

	public const RELATION_ALIASES = [
		'contract'              => 'UF_CONTRACT',
		'stage'             => 'UF_STAGE',
		'country'             => 'UF_COUNTRY',
		'district'             => 'UF_DISTRICT',
	];

	protected $casts = [
		'UF_PHOTO' => Serialize::class,
	];

	public function contract(): BelongsTo
	{
		return $this->belongsTo(Contract::class, 'UF_CONTRACT', 'ID');
	}

	public function stage(): HasOne
	{
		return $this->hasOne(Stage::class, 'ID', 'UF_STAGE');
	}

	public function country(): HasOne
	{
		return $this->hasOne(Country::class, 'ID', 'UF_COUNTRY');
	}

	public function district(): HasOne
	{
		return $this->hasOne(District::class, 'ID', 'UF_DISTRICT');
	}

	public function scopeRelationships($query): Builder
	{
		return $query->with(
			'contract',
			'stage',
			'country',
			'district',
			'responsible',
		);
	}
}
