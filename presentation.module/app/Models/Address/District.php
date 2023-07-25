<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Address;

use Laravel\Illuminate\App\Models\Base;

class District extends Base
{
    protected $table = 'tdb_object_districts';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_NAME'];
}
