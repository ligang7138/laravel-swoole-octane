<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\School\CanteenService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;

/**
 * 食堂管理控制器
 */
class CanteenController extends Controller
{
    protected CanteenService $canteenService;

    public function __construct(CanteenService $canteenService)
    {
        $this->canteenService = $canteenService;
    }

    /**
     * 食堂列表
     */
    public function index(Request $request)
    {
        $params = $request->only([
            'keyword',
            'school_id',
            'page',
            'page_size',
            'sort_field',
            'sort_order',
        ]);

        $result = $this->canteenService->getList($params);

        return ResponseHelper::success($result);
    }

    /**
     * 创建食堂
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => 'required|integer|exists:school,id',
            'canteen_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $canteen = $this->canteenService->create($data);

            return ResponseHelper::success([
                'id' => $canteen->id,
            ], '食堂创建成功');
        } catch (\Exception $e) {
            return ResponseHelper::error('食堂创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 食堂详情
     */
    public function show(int $id)
    {
        try {
            $detail = $this->canteenService->getDetail($id);

            return ResponseHelper::success($detail);
        } catch (\Exception $e) {
            return ResponseHelper::error('食堂不存在');
        }
    }

    /**
     * 更新食堂
     */
    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'school_id' => 'sometimes|required|integer|exists:school,id',
            'canteen_name' => 'sometimes|required|string|max:255',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|integer|in:0,1',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $canteen = $this->canteenService->update($id, $data);

            return ResponseHelper::success([
                'id' => $canteen->id,
            ], '食堂更新成功');
        } catch (\Exception $e) {
            return ResponseHelper::error('食堂更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除食堂
     */
    public function destroy(int $id)
    {
        try {
            $this->canteenService->delete($id);

            return ResponseHelper::success([], '食堂删除成功');
        } catch (\Exception $e) {
            return ResponseHelper::error('食堂删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取学校下的食堂
     */
    public function getBySchool(int $schoolId)
    {
        $canteens = $this->canteenService->getBySchool($schoolId);

        return ResponseHelper::success($canteens);
    }
}