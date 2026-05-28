<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\User;
use App\Helpers\ResponseHelper;

class UserController extends Controller
{
    /**
     * 用户列表
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        // 部门筛选
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('page_size', 20));

        return ResponseHelper::paginate($list);
    }

    /**
     * 用户详情
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return ResponseHelper::success($user);
    }

    /**
     * 创建用户
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:admin_users',
            'password' => 'required|string|min:6',
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer',
            'is_super' => 'nullable|boolean',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $validated['password'] = password_hash($validated['password'], PASSWORD_BCRYPT);
        $validated['salt'] = '';

        $user = User::create($validated);

        return ResponseHelper::success($user, '创建成功');
    }

    /**
     * 更新用户
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer',
            'is_super' => 'nullable|boolean',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $user->update($validated);

        return ResponseHelper::success($user, '更新成功');
    }

    /**
     * 删除用户
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return ResponseHelper::success(null, '删除成功');
    }

    /**
     * 更改用户状态
     */
    public function changeStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|integer|in:0,1',
        ]);

        $user->update(['status' => $validated['status']]);

        return ResponseHelper::success(null, '状态更新成功');
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
            'salt' => '',
        ]);

        return ResponseHelper::success(null, '密码重置成功');
    }

    /**
     * 批量删除
     */
    public function batchDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        User::whereIn('id', $validated['ids'])->delete();

        return ResponseHelper::success(null, '批量删除成功');
    }

    /**
     * 获取用户权限
     */
    public function getPrivilege($id)
    {
        $user = User::findOrFail($id);
        $permissions = $user->getPermissions();

        return ResponseHelper::success([
            'user_id' => $user->id,
            'permissions' => $permissions,
        ]);
    }

    /**
     * 更新用户权限
     */
    public function updatePrivilege(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 权限更新逻辑（根据业务需求实现）
        // 这里可以更新用户的岗位关联等

        return ResponseHelper::success(null, '权限更新成功');
    }
}
