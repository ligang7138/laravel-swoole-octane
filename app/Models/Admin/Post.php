<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 岗位模型
 */
class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'name',
        'code',
        'department_id',
        'remark',
        'privilege',
        'status',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 所属部门
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 岗位下的用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'admin_user_posts', 'post_id', 'user_id');
    }

    /**
     * 获取权限列表
     */
    public function getPrivilegeListAttribute(): array
    {
        if (empty($this->privilege)) {
            return [];
        }

        return is_string($this->privilege)
            ? json_decode($this->privilege, true)
            : $this->privilege;
    }

    /**
     * 设置权限列表
     */
    public function setPrivilegeAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['privilege'] = json_encode($value);
        } else {
            $this->attributes['privilege'] = $value;
        }
    }

    /**
     * 是否启用
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
