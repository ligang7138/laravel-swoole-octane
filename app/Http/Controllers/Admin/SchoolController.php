<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\School\SchoolService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

/**
 * 学校管理控制器
 */
class SchoolController extends Controller
{
    protected SchoolService $schoolService;

    public function __construct(SchoolService $schoolService)
    {
        $this->schoolService = $schoolService;
    }

    /**
     * 学校列表
     */
    public function index(Request $request)
    {
        $params = $request->only([
            'keyword',
            'status',
            'page',
            'page_size',
            'sort_field',
            'sort_order',
        ]);

        $result = $this->schoolService->getList($params);

        return ResponseHelper::success($result);
    }

    /**
     * 创建学校
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'nullable|string|max:50',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'credit_code' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $school = $this->schoolService->create($data);

            return ResponseHelper::success([
                'id' => $school->id,
            ], '学校创建成功');
        } catch (\Exception $e) {
            return ResponseHelper::error('学校创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 学校详情
     */
    public function show(int $id)
    {
        try {
            $detail = $this->schoolService->getDetail($id);

            return ResponseHelper::success($detail);
        } catch (\Exception $e) {
            return ResponseHelper::error('学校不存在');
        }
    }

    /**
     * 更新学校
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'school_name' => 'sometimes|required|string|max:255',
            'school_code' => 'nullable|string|max:50',
            'contact_name' => 'sometimes|required|string|max:100',
            'contact_phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'credit_code' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $school = $this->schoolService->update($id, $data);

            return ResponseHelper::success([
                'id' => $school->id,
            ], '学校更新成功');
        } catch (\Exception $e) {
            return ResponseHelper::error('学校更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除学校
     */
    public function destroy(int $id)
    {
        try {
            $this->schoolService->delete($id);

            return ResponseHelper::success([], '学校删除成功');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * 获取所有启用的学校
     */
    public function getActiveSchools()
    {
        $schools = $this->schoolService->getActiveSchools();

        return ResponseHelper::success($schools);
    }
}