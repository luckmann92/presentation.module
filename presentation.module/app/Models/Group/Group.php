<?php
namespace PresentModule\App\Models\Group;

use Laravel\Illuminate\App\Models\B24\Group\GroupMember;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use PresentModule\App\Models\Task\NotificationSetting;
use PresentModule\App\Models\Task\Task;

class Group extends \Laravel\Illuminate\App\Models\B24\Group\Group
{
	public function userFields(): HasOne
	{
		return $this->hasOne(GroupUserFields::class, 'VALUE_ID', 'ID');
	}

	public function userFieldsWithObject(): HasOne
	{
		return $this->hasOne(GroupUserFields::class, 'VALUE_ID', 'ID')->with('object');
	}

	public function userFieldsWithPassportType(): HasOne
	{
		return $this->hasOne(GroupUserFields::class, 'VALUE_ID', 'ID')->with('passportType');
	}

	public function taskStages(): HasMany
	{
		return $this->hasMany(TaskStage::class, 'ENTITY_ID', 'ID')
			->where('ENTITY_TYPE', '=', 'G')->orderBy('SORT');
	}

	public function notifySettings(): HasMany
	{
		return $this->hasMany(NotificationSetting::class, 'UF_GROUP_ID', 'ID');
	}

	public function tasks(): HasMany
	{
		return $this->hasMany(Task::class, 'GROUP_ID', 'ID');
	}

	public function members(): HasMany
	{
		return $this->hasMany(GroupMember::class, 'GROUP_ID', 'ID');
	}
}
