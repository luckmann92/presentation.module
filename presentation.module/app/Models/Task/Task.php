<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\B24\HLBlockEntity;
use Laravel\Illuminate\App\Models\B24\Task\Task as TaskBase;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use Laravel\Illuminate\App\Models\B24\Task\TaskUserData;
use Laravel\Illuminate\App\Models\Bitrix\UserField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PresentModule\App\Models\Group\ForumMessage;
use PresentModule\App\Models\Group\ForumTopic;
use PresentModule\App\Models\Reference\ApprovalMes;
use PresentModule\App\Models\Reference\ApprovalMgen;
use PresentModule\App\Models\Reference\ApprovalRmr;
use PresentModule\App\Models\Reference\LandingTp;
use PresentModule\App\Models\Reference\LinkingNetworks;
use PresentModule\App\Models\Reference\RsoRequestMethod;
use PresentModule\App\Models\Reference\TuType;
use PresentModule\App\Models\Task\File\FileVersion;

class Task extends TaskBase
{
    protected $table = 'b_tasks';
    protected $primaryKey = 'ID';

    public function userFields()
    {
        return UserField::where('ENTITY_ID', 'TASKS_TASK');
    }

    public function groupUserFields()
    {
        return self::userFields();
    }

	public function userFieldsData(): HasOne
	{
		return $this->hasOne(TaskUserData::class, 'VALUE_ID', 'ID')->with(['object']);
	}

	public function stage(): HasOne
	{
		return $this->hasOne(TaskStage::class, 'ID', 'STAGE_ID');
	}

    /**
     * Способ отправки заявки в РСО
     * @return HasOne
     */
    public function rsoRequestMethod(): HasOne
    {
        return $this->hasOne(RsoRequestMethod::class, 'ID', 'UF_RSO_REQUEST_METHOD');
    }

    /**
     * Вид ТУ
     * @return HasOne
     */
    public function tuType(): HasOne
    {
        return $this->hasOne(TuType::class, 'ID', 'UF_TU_TYPE');
    }

    /**
     * Согласование МЭС
     * @return HasOne
     */
    public function approvalMes(): HasOne
    {
        return $this->hasOne(ApprovalMes::class, 'ID', 'UF_APPROVAL_MES');
    }

    /**
     * Согласование МГЭН
     * @return HasOne
     */
    public function approvalMgen(): HasOne
    {
        return $this->hasOne(ApprovalMgen::class, 'ID', 'UF_APPROVAL_MGEN');
    }

    /**
     * Согласование РМР
     * @return HasOne
     */
    public function approvalRmr(): HasOne
    {
        return $this->hasOne(ApprovalRmr::class, 'ID', 'UF_APPROVAL_RMR');
    }

    /**
     * Увязка сетей
     * @return HasOne
     */
    public function linkingNetworks(): HasOne
    {
        return $this->hasOne(LinkingNetworks::class, 'ID', 'UF_LINKING_NETWORKS');
    }

    /**
     * Посадка ТП
     * @return HasOne
     */
    public function landingTp(): HasOne
    {
        return $this->hasOne(LandingTp::class, 'ID', 'UF_LANDING_TP');
    }

    public function scopeHlElements($query): Builder
    {
        $userFields = \Laravel\Illuminate\App\Models\B24\UserField::where('ENTITY_ID', 'TASKS_TASK')
            ->whereIn('USER_TYPE_ID',  ['hlblock', 'hlblock_detail'])
            ->get();

            $query->with(['userFieldsData' => function ($query) use ($userFields) {
            $select = ['*'];

            foreach ($userFields as $userField) {
                if ($userField->FIELD_NAME == 'UF_SECTION_RD' || $userField->FIELD_NAME == 'UF_ADDRESS_BY_DOCS') {
                    continue;
                }

                $hlBlock = HLBlockEntity::find($userField->SETTINGS['HLBLOCK_ID']);

                $select[] = $hlBlock->TABLE_NAME.'.ID as '.$userField->FIELD_NAME.'-ID';
                $hlUserFields = UserField::where('ENTITY_ID', 'HLBLOCK_'.$userField->SETTINGS['HLBLOCK_ID'])->get();
                foreach ($hlUserFields as $hlUserField) {
                    $select[] = $hlBlock->TABLE_NAME.'.'.$hlUserField->FIELD_NAME.' as '.$userField->FIELD_NAME.'-'.$hlUserField->FIELD_NAME;
                }

                $query->leftjoin($hlBlock->TABLE_NAME, 'b_uts_tasks_task.'.$userField->FIELD_NAME, '=', $hlBlock->TABLE_NAME.'.ID');
            }

            $query->select($select);
        }]);

        return $query;
    }

	public function scopeTaskUserDataSelectTo($query): Builder
	{
		return $query->where('ENTITY_ID', 'TASKS_TASK')->with('section');
	}

	public function versions():HasMany
	{
		return $this->hasMany(FileVersion::class, 'TASK_ID', 'ID');
	}

	public function logs():HasMany
	{
		return $this->hasMany(TaskLog::class, 'TASK_ID', 'ID');
	}
}
