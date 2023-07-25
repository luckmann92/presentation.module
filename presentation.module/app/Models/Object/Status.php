<?php
namespace PresentModule\App\Models\Object;

use Laravel\Illuminate\App\Models\Base;

class Status extends Base
{
    protected $table = 'tdb_object_status';
	public $timestamps = false;
	protected $primaryKey = 'ID';

	protected $fillable = ['UF_NAME'];
}
