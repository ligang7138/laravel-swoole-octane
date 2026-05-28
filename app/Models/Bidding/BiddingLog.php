<?php

namespace App\Models\Bidding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合作关系模型
 * 对应表: bidding_log
 */
class BiddingLog extends Model
{
    use SoftDeletes;

    protected $table = 'bidding_log';

    protected $fillable = [
        'supp_id',
        'school_id',
        'canteen_id',
        'status',
        'effective_status',
        'start_date',
        'end_date',
        'remark',
    ];

    protected $casts = [
        'supp_id' => 'integer',
        'school_id' => 'integer',
        'canteen_id' => 'integer',
        'status' => 'integer',
        'effective_status' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 合作状态常量
     */
    const STATUS_INACTIVE = 0;  // 未生效
    const STATUS_ACTIVE = 1;    // 合作中
    const STATUS_EXPIRED = 2;   // 已过期
    const STATUS_TERMINATED = 3; // 已终止

    /**
     * 生效状态常量
     */
    const EFFECTIVE_PENDING = 0;  // 待生效
    const EFFECTIVE_ACTIVE = 1;   // 已生效
    const EFFECTIVE_EXPIRED = 2;  // 已失效

    /**
     * 获取合作状态文本
     */
    public function getStatusText(): string
    {
        $statusMap = [
            self::STATUS_INACTIVE => '未生效',
            self::STATUS_ACTIVE => '合作中',
            self::STATUS_EXPIRED => '已过期',
            self::STATUS_TERMINATED => '已终止',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 获取生效状态文本
     */
    public function getEffectiveStatusText(): string
    {
        $statusMap = [
            self::EFFECTIVE_PENDING => '待生效',
            self::EFFECTIVE_ACTIVE => '已生效',
            self::EFFECTIVE_EXPIRED => '已失效',
        ];
        return $statusMap[$this->effective_status] ?? '未知';
    }

    /**
     * 所属食堂
     */
    public function canteen()
    {
        return $this->belongsTo(\App\Models\School\Canteen::class, 'canteen_id');
    }

    /**
     * 所属学校
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School\School::class, 'school_id');
    }

    /**
     * 供应商
     */
    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier\Supplier::class, 'supp_id');
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('school', function ($sq) use ($keyword) {
                    $sq->where('school_name', 'like', "%{$keyword}%");
                })->orWhereHas('supplier', function ($sq) use ($keyword) {
                    $sq->where('supplier_name', 'like', "%{$keyword}%");
                });
            });
        }
        return $query;
    }

    /**
     * 学校筛选
     */
    public function scopeBySchool($query, $schoolId)
    {
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        return $query;
    }

    /**
     * 食堂筛选
     */
    public function scopeByCanteen($query, $canteenId)
    {
        if ($canteenId) {
            $query->where('canteen_id', $canteenId);
        }
        return $query;
    }

    /**
     * 供应商筛选
     */
    public function scopeBySupplier($query, $supplierId)
    {
        if ($supplierId) {
            $query->where('supp_id', $supplierId);
        }
        return $query;
    }

    /**
     * 状态筛选
     */
    public function scopeByStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * 生效状态筛选
     */
    public function scopeByEffectiveStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('effective_status', $status);
        }
        return $query;
    }

    /**
     * 合作中
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 已生效
     */
    public function scopeEffective($query)
    {
        return $query->where('effective_status', self::EFFECTIVE_ACTIVE);
    }
}
