<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Group\GroupService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

/**
 * 分组管理控制器
 */
class GroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * 分组列表
     */
    public function index(Request $request)
    {
        $params = $request->only(['name', 'status', 'page', 'page_size']);

        $result = $this->groupService->getList($params);

        return ResponseHelper::success($result);
    }

    /**
     * 创建分组
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'pid' => 'nullable|integer|min:0',
            'code' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:0,1',
        ]);

        try {
            $group = $this->groupService->create($data);

            return ResponseHelper::success([
                'id' => $group->id,
            ], '创建成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 更新分组
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:50',
            'pid' => 'nullable|integer|min:0',
            'code' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:0,1',
        ]);

        try {
            $group = $this->groupService->update($id, $data);

            return ResponseHelper::success([
                'id' => $group->id,
            ], '更新成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 删除分组
     */
    public function destroy(int $id)
    {
        try {
            $this->groupService->delete($id);

            return ResponseHelper::success([], '删除成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 分组食堂列表
     */
    public function canteens(int $id)
    {
        try {
            $canteens = $this->groupService->getCanteens($id);

            return ResponseHelper::success($canteens);
        } catch (\Exception $e) {
            return ResponseHelper::error(40002, '分组不存在');
        }
    }

    /**
     * 添加食堂到分组
     */
    public function addCanteen(Request $request, int $id)
    {
        $canteenId = $request->input('canteen_id');

        try {
            $this->groupService->addCanteen($id, $canteenId);

            return ResponseHelper::success([], '添加成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 从分组移除食堂
     */
    public function removeCanteen(int $groupId, int $canteenId)
    {
        try {
            $this->groupService->removeCanteen($groupId, $canteenId);

            return ResponseHelper::success([], '移除成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 设置主账号
     */
    public function setAudit(int $groupId, int $canteenId)
    {
        try {
            $this->groupService->setAudit($groupId, $canteenId);

            return ResponseHelper::success([], '设置成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }

    /**
     * 移除主账号
     */
    public function removeAudit(int $groupId, int $canteenId)
    {
        try {
            $this->groupService->removeAudit($groupId, $canteenId);

            return ResponseHelper::success([], '移除成功');
        } catch (\Exception $e) {
            return ResponseHelper::error(40001, $e->getMessage());
        }
    }
}
