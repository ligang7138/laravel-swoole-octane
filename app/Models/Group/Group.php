<?php

namespace App\Models\Group;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 分组模型
 * 对应表: `group`
 */
class Group extends Model
{
    use SoftDeletes;

    protected $table = 'group';

    protected $fillable = [
        'name',
        'pid',
        'code',
        'status',
        'add_user',
        'add_time',
    ];

    protected $casts = [
        'pid' => 'integer',
        'status' => 'integer',
        'add_user' => 'integer',
        'add_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 分组状态常量
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        $statusMap = [
            self::STATUS_INACTIVE => '禁用',
            self::STATUS_ACTIVE => '启用',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 父级分组
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'pid');
    }

    /**
     * 子分组
     */
    public function children()
    {
        return $this->hasMany(self::class, 'pid');
    }

    /**
     * 分组下的食堂
     */
    public function canteens()
    {
        return $this->hasMany(\App\Models\School\Canteen::class, 'group_id');
    }

    /**
     * 创建人
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin\Admin::class, 'add_user');
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
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
     * 父级筛选
     */
    public function scopeByParent($query, $pid)
    {
        if ($pid !== null && $pid !== '') {
            $query->where('pid', $pid);
        }
        return $query;
    }

    /**
     * 获取所有子分组ID（包含自身）
     */
    public function getAllChildrenIds(): array
    {
        $ids = [$this->id];
        $children = $this->children;

        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }

        return $ids;
    }

    /**
     * 是否有子分组
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * 是否有关联食堂
     */
    public function hasCanteens(): bool
    {
        return $this->canteens()->count() > 0;
    }
}
