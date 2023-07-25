<?php
namespace PresentModule\App\Models\Group;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PresentModule\App\Models\Object\ConstructionObject;

class GroupUserFields extends \Laravel\Illuminate\App\Models\B24\Group\GroupUserFields
{
	public function object(): BelongsTo
	{
		return $this->belongsTo(ConstructionObject::class, 'UF_OBJECT', 'ID')->relationships();
	}

	public function passportType(): BelongsTo
	{
		return $this->belongsTo(PassportType::class, 'UF_PASSPORT_TYPE', 'ID');
	}
}
