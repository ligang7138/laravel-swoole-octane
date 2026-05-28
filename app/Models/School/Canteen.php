<?php

namespace App\Models\School;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 食堂模型
 * 对应表: school_canteen
 */
class Canteen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_canteen';

    protected static function newFactory()
    {
        return \Database\Factories\CanteenFactory::new();
    }

    protected $fillable = [
        'school_id',
        'canteen_name',
        'contact_name',
        'contact_phone',
        'address',
        'status',
        'remark',
        'is_active',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'status' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 食堂状态常量
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

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
     * 所属学校
     */
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where('canteen_name', 'like', "%{$keyword}%");
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
}