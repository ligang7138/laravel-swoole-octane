<?php

namespace App\Models\Supplier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 供应商模型
 * 对应表: supplier
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier';

    protected static function newFactory()
    {
        return \Database\Factories\SupplierFactory::new();
    }

    protected $fillable = [
        'code',
        'name',
        'company',
        'address',
        'cate_type',
        'cate_ids',
        'license_logo',
        'credit_code',
        'permit_logo',
        'permit_code',
        'linkman',
        'mobile',
        'emergency_linkman',
        'emergency_mobile',
        'sso_user_id',
        'score',
        'status',
        'add_time',
        'update_time',
    ];

    protected $casts = [
        'cate_type' => 'integer',
        'score' => 'decimal:2',
        'status' => 'integer',
        'sso_user_id' => 'integer',
        'add_time' => 'integer',
        'update_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 供应商状态常量
     */
    const STATUS_PENDING = 0;   // 待审核
    const STATUS_APPROVED = 1;  // 已通过
    const STATUS_REJECTED = 2;  // 已拒绝

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('linkman', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
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