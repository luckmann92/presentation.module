<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Contract;

use Laravel\Illuminate\App\Models\Base;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PresentModule\App\Models\Object\ConstructionObject;

class Contract extends Base
{
    protected $table = 'tdb_contracts';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_UID'];

	public function objects(): HasMany
	{
		return $this->hasMany(ConstructionObject::class, 'UF_CONTRACT', 'ID');
	}
}
