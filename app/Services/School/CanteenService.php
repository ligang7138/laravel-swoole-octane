<?php

namespace App\Services\School;

use App\Models\School\Canteen;

/**
 * 食堂服务层
 */
class CanteenService
{
    /**
     * 获取食堂列表
     */
    public function getList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $query = Canteen::with('school')
            ->search($params['keyword'] ?? null)
            ->bySchool($params['school_id'] ?? null);

        $sortField = $params['sort_field'] ?? 'id';
        $sortOrder = $params['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $list = $query->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return [
            'list' => $list->map(function ($item) {
                return [
                    'id' => $item->id,
                    'school_id' => $item->school_id,
                    'school_name' => $item->school?->school_name,
                    'canteen_name' => $item->canteen_name,
                    'contact_name' => $item->contact_name,
                    'contact_phone' => $item->contact_phone,
                    'address' => $item->address,
                    'status' => $item->status,
                    'status_text' => $item->getStatusText(),
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 创建食堂
     */
    public function create(array $data): Canteen
    {
        if (!isset($data['status'])) {
            $data['status'] = Canteen::STATUS_ACTIVE;
        }

        return Canteen::create($data);
    }

    /**
     * 更新食堂
     */
    public function update(int $id, array $data): Canteen
    {
        $canteen = Canteen::findOrFail($id);
        $canteen->update($data);

        return $canteen->fresh();
    }

    /**
     * 删除食堂
     */
    public function delete(int $id): bool
    {
        $canteen = Canteen::findOrFail($id);
        return $canteen->delete();
    }

    /**
     * 获取食堂详情
     */
    public function getDetail(int $id): array
    {
        $canteen = Canteen::with('school')->findOrFail($id);

        return [
            'id' => $canteen->id,
            'school_id' => $canteen->school_id,
            'school_name' => $canteen->school?->school_name,
            'canteen_name' => $canteen->canteen_name,
            'contact_name' => $canteen->contact_name,
            'contact_phone' => $canteen->contact_phone,
            'address' => $canteen->address,
            'status' => $canteen->status,
            'status_text' => $canteen->getStatusText(),
            'remark' => $canteen->remark,
            'is_active' => $canteen->is_active,
            'created_at' => $canteen->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取学校下的食堂
     */
    public function getBySchool(int $schoolId): array
    {
        $canteens = Canteen::where('school_id', $schoolId)
            ->where('status', Canteen::STATUS_ACTIVE)
            ->orderBy('canteen_name')
            ->get();

        return $canteens->map(function ($item) {
            return [
                'id' => $item->id,
                'canteen_name' => $item->canteen_name,
            ];
        })->toArray();
    }
}