<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Object;

use Laravel\Illuminate\App\Models\Base;

class Stage extends Base
{
    protected $table = 'tdb_object_stages';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_NAME'];
}
