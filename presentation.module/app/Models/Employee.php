<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models;

use Laravel\Illuminate\App\Models\Base;

class Employee extends Base
{
    protected $table = 'tdb_employees';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_NAME'];
}
