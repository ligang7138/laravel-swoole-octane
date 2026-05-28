<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 供应商折扣记录模型
 * 对应表: discount_log
 */
class DiscountLog extends Model
{
    use SoftDeletes;

    protected $table = 'discount_log';

    protected $fillable = [
        'supplier_id',
        'school_id',
        'discount',
        'old_discount',
        'operator_id',
        'remark',
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'old_discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联供应商
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * 关联学校
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School\School::class, 'school_id');
    }
}