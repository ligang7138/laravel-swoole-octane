<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use App\Models\Admin\User;

/**
 * 认证辅助类
 * 处理密码加密、验证等认证相关逻辑
 */
class AuthHelper
{
    /**
     * 默认密码（与老系统一致）
     */
    public const DEFAULT_PASSWORD = 'Dxdzcg888';

    /**
     * 使用老系统方式加密密码
     * 老系统: md5(md5(password) + salt)
     */
    public static function encryptLegacyPassword(string $password, string $salt): string
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 生成盐值
     */
    public static function generateSalt(): string
    {
        return substr(md5((string) time()), 0, 10);
    }

    /**
     * 验证老系统密码
     */
    public static function verifyLegacyPassword(string $password, string $hash, string $salt): bool
    {
        $legacyHash = self::encryptLegacyPassword($password, $salt);
        return $legacyHash === $hash;
    }

    /**
     * 使用 Laravel 方式加密密码（bcrypt）
     */
    public static function encryptPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * 验证密码（兼容新老两种加密方式）
     */
    public static function verifyPassword(string $password, string $hash, string $salt = ''): bool
    {
        // 先尝试 Laravel 方式验证
        if (Hash::check($password, $hash)) {
            return true;
        }

        // 如果有盐值，尝试老系统方式验证
        if ($salt !== '') {
            return self::verifyLegacyPassword($password, $hash, $salt);
        }

        return false;
    }

    /**
     * 升级密码加密方式（从老系统迁移到新系统）
     */
    public static function upgradePassword(User $user, string $password): void
    {
        $user->password = self::encryptPassword($password);
        $user->salt = ''; // 清空旧的盐值
        $user->save();
    }

    /**
     * 重置密码为默认密码
     */
    public static function resetToDefaultPassword(User $user): void
    {
        $user->password = self::encryptPassword(self::DEFAULT_PASSWORD);
        $user->salt = '';
        $user->save();
    }
}