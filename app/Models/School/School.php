<?php

namespace App\Models\School;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 学校模型
 * 对应表: school
 */
class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school';

    protected static function newFactory()
    {
        return \Database\Factories\SchoolFactory::new();
    }

    protected $fillable = [
        'school_sn',
        'school_name',
        'school_district',
        'school_subdistrict',
        'school_period',
        'bank_no',
        'taxpayer_no',
        'invoice_title',
        'account_type',
        'account_period',
        'account_start_date',
        'account_end_date',
        'account_execute_date',
        'status',
        'add_time',
    ];

    protected $casts = [
        'account_type' => 'integer',
        'account_period' => 'integer',
        'account_start_date' => 'date',
        'account_end_date' => 'date',
        'account_execute_date' => 'date',
        'status' => 'integer',
        'add_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 学校状态常量
     */
    const STATUS_INACTIVE = 0;  // 未激活
    const STATUS_ACTIVE = 1;    // 已激活

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        $statusMap = [
            self::STATUS_INACTIVE => '未激活',
            self::STATUS_ACTIVE => '已激活',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 学校的食堂
     */
    public function canteens()
    {
        return $this->hasMany(Canteen::class, 'school_id');
    }

    /**
     * 学校的用户
     */
    public function users()
    {
        return $this->hasMany(SchoolUser::class, 'school_id');
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('school_name', 'like', "%{$keyword}%")
                    ->orWhere('school_sn', 'like', "%{$keyword}%")
                    ->orWhere('school_district', 'like', "%{$keyword}%");
            });
        }
        return $query;
    }

    /**
     * 状态筛选作用域
     */
    public function scopeByStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        return $query;
    }
}