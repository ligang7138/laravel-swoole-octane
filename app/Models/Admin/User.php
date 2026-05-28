<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * 管理员用户模型
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'password',
        'salt',
        'name',
        'email',
        'mobile',
        'avatar',
        'department_id',
        'is_super',
        'status',
        'last_login_time',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'salt',
        'remember_token',
    ];

    protected $casts = [
        'is_super' => 'boolean',
        'status' => 'integer',
        'last_login_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * JWT 标识符
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * JWT 自定义声明
     */
    public function getJWTCustomClaims()
    {
        return [
            'guard' => 'admin',
        ];
    }

    /**
     * 所属部门
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * 用户岗位（多岗位）
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'admin_user_posts', 'user_id', 'post_id');
    }

    /**
     * 是否为超级管理员
     */
    public function isSuper(): bool
    {
        return (bool) $this->is_super;
    }

    /**
     * 是否启用
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * 获取用户所有权限
     */
    public function getPermissions(): array
    {
        if ($this->is_super) {
            return ['*'];
        }

        $permissions = [];

        foreach ($this->posts as $post) {
            if ($post->status !== 1) {
                continue;
            }

            $postPermissions = is_string($post->privilege)
                ? json_decode($post->privilege, true)
                : $post->privilege;

            if (is_array($postPermissions)) {
                $permissions = array_merge($permissions, $postPermissions);
            }
        }

        return array_unique($permissions);
    }

    /**
     * 权限访问器 - 供中间件使用
     */
    public function getPermissionsAttribute(): array
    {
        return $this->getPermissions();
    }

    /**
     * 检查是否拥有指定权限
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();

        if (in_array('*', $permissions)) {
            return true;
        }

        if (in_array($permission, $permissions)) {
            return true;
        }

        // 通配符匹配
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }
}
