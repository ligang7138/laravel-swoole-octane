<?php

namespace App\Services\Group;

use App\Models\Group\Group;
use App\Models\School\Canteen;
use Illuminate\Support\Facades\DB;

/**
 * 分组管理服务层
 */
class GroupService
{
    /**
     * 分组列表
     */
    public function getList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $query = Group::with(['parent'])
            ->when($params['name'] ?? null, function ($q, $name) {
                $q->where('name', 'like', "%{$name}%");
            })
            ->when($params['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            });

        $query->orderBy('id', 'desc');

        $total = $query->count();
        $list = $query->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'list' => $list->map(function ($item) {
                $canteenCount = Canteen::where('group_id', $item->id)->count();
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'pid' => $item->pid,
                    'parent_name' => $item->parent?->name,
                    'code' => $item->code,
                    'status' => $item->status,
                    'canteen_count' => $canteenCount,
                    'add_user' => $item->add_user,
                    'add_time' => $item->add_time ? date('Y-m-d H:i:s', $item->add_time) : null,
                    'update_user' => $item->update_user,
                    'update_time' => $item->update_time ? date('Y-m-d H:i:s', $item->update_time) : null,
                ];
            }),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 创建分组
     */
    public function create(array $data): Group
    {
        return Group::create([
            'name' => $data['name'],
            'pid' => $data['pid'] ?? 0,
            'code' => $data['code'] ?? '',
            'status' => $data['status'] ?? 1,
            'add_user' => auth()->user()->name ?? '',
            'add_time' => time(),
        ]);
    }

    /**
     * 更新分组
     */
    public function update(int $id, array $data): Group
    {
        $group = Group::findOrFail($id);

        if (isset($data['pid']) && $data['pid'] == $id) {
            throw new \Exception('父分组不能是自己');
        }

        $data['update_user'] = auth()->user()->name ?? '';
        $data['update_time'] = time();

        $group->update($data);
        return $group;
    }

    /**
     * 删除分组
     */
    public function delete(int $id): bool
    {
        $group = Group::findOrFail($id);

        // 检查是否有子分组
        $childCount = Group::where('pid', $id)->count();
        if ($childCount > 0) {
            throw new \Exception('该分组有子分组，无法删除');
        }

        // 检查是否有食堂关联
        $canteenCount = Canteen::where('group_id', $id)->count();
        if ($canteenCount > 0) {
            throw new \Exception('该分组有食堂关联，无法删除');
        }

        return $group->delete();
    }

    /**
     * 获取分组食堂列表
     */
    public function getCanteens(int $id): array
    {
        $canteens = Canteen::with(['school'])
            ->where('group_id', $id)
            ->get();

        return $canteens->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'school_id' => $item->school_id,
                'school_name' => $item->school?->school_name,
                'canteen_type' => $item->canteen_type,
                'canteen_type_text' => $item->canteen_type == 1 ? '教师食堂' : '学生食堂',
                'linkman' => $item->linkman,
                'mobile' => $item->mobile,
                'is_audit' => $item->is_audit,
                'is_audit_text' => $item->is_audit == 1 ? '主账号' : '普通账号',
            ];
        })->toArray();
    }

    /**
     * 添加食堂到分组
     */
    public function addCanteen(int $groupId, int $canteenId): void
    {
        $canteen = Canteen::findOrFail($canteenId);

        if ($canteen->group_id > 0 && $canteen->group_id != $groupId) {
            throw new \Exception('该食堂已属于其他分组');
        }

        $canteen->group_id = $groupId;
        $canteen->is_audit = 0;
        $canteen->save();
    }

    /**
     * 从分组移除食堂
     */
    public function removeCanteen(int $groupId, int $canteenId): void
    {
        $canteen = Canteen::where('id', $canteenId)
            ->where('group_id', $groupId)
            ->firstOrFail();

        $canteen->group_id = 0;
        $canteen->is_audit = 0;
        $canteen->save();
    }

    /**
     * 设置主账号
     */
    public function setAudit(int $groupId, int $canteenId): void
    {
        DB::transaction(function () use ($groupId, $canteenId) {
            // 先清除分组内所有主账号
            Canteen::where('group_id', $groupId)
                ->update(['is_audit' => 0]);

            // 设置指定食堂为主账号
            Canteen::where('id', $canteenId)
                ->where('group_id', $groupId)
                ->update(['is_audit' => 1]);
        });
    }

    /**
     * 移除主账号
     */
    public function removeAudit(int $groupId, int $canteenId): void
    {
        Canteen::where('id', $canteenId)
            ->where('group_id', $groupId)
            ->update(['is_audit' => 0]);
    }
}