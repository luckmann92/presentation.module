<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Address;

use Laravel\Illuminate\App\Models\Base;

class Country extends Base
{
    protected $table = 'tdb_object_countries';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_NAME'];
}
