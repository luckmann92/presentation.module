<?php

namespace PresentModule\App\Http\Controllers;

use Laravel\Illuminate\App\Http\Controllers\ApiController;
use Laravel\Illuminate\App\Models\B24\HLBlockEntity;
use Laravel\Illuminate\App\Models\B24\HLBlockEntityLang;
use Laravel\Illuminate\App\Models\B24\UserFieldLang;
use Laravel\Illuminate\App\Models\B24\UserField;
use Illuminate\Database\Capsule\Manager as Capsule;
use PresentModule\App\Services\ParseService;

class HlBlockController extends ApiController
{
	public function list()
	{
		$hlblocks = HLBlockEntity::all();
		foreach ($hlblocks as $hlblock) {
			$lang = HLBlockEntityLang::where('LID', '=', 'ru')
				->where('ID', '=', $hlblock->ID)
				->first();

			$hlblock->lang = $lang;
		}

		return $hlblocks;
	}

	public function get($id)
	{
		return HLBlockEntity::find($id);
	}

	public function fields($id)
	{
		return UserField::with('lang')->where('ENTITY_ID', 'HLBLOCK_'.$id)->get();
	}

	public function field($id)
	{
		return UserField::find($id);
	}

	public function elements($id)
	{
		$hlblock = HLBlockEntity::find($id);

		return Capsule::connection()->table($hlblock->TABLE_NAME)->get();
	}
}
