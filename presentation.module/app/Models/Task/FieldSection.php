<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Models\Task;

use Laravel\Illuminate\App\Models\Base;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FieldSection extends Base
{
    protected $table = 'tdb_task_field_section';

    protected $maps = [
        'UF_NAME' => 'name',
    ];

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(
            TaskUserFields::class,
            'rn_task_field_section_relation',
            'UF_SECTION_ID',
            'UF_FIELD_ID',
            'ID',
            'ID'
        )->orderBy('SORT');
    }

    public function invoiceField(): HasOne
    {
        return $this->hasOne(
            TaskUserFields::class,
            'ID', 'UF_INVOICE_FIELD'
        )->select(['ID', 'FIELD_NAME']);
    }

    public function actField(): HasOne
    {
        return $this->hasOne(
            TaskUserFields::class,
            'ID', 'UF_ACT_FIELD'
        )->select(['ID', 'FIELD_NAME']);
    }

    public function sfField(): HasOne
    {
        return $this->hasOne(
            TaskUserFields::class,
            'ID', 'UF_SF_FIELD'
        )->select(['ID', 'FIELD_NAME']);
    }

    public function paymentOrderField(): HasOne
    {
        return $this->hasOne(
            TaskUserFields::class,
            'ID', 'UF_PAYMENT_ORDER_FIELD'
        )->select(['ID', 'FIELD_NAME']);
    }

    public function contractField(): HasOne
    {
        return $this->hasOne(
            TaskUserFields::class,
            'ID', 'UF_CONTRACT_FIELD'
        )->select(['ID', 'FIELD_NAME']);
    }

    public function scopeMappingFields($query): Builder
    {
        return $query->with(['contractField', 'paymentOrderField', 'sfField', 'actField', 'invoiceField']);
    }

    public function scopeGroupFields($query): Builder
    {
        return $query->orderBy('UF_SORT')
            ->whereHas('fields', function ($query) {
                $query
                    ->select(['FIELD_NAME']);
            })->with('fields');
    }
}
