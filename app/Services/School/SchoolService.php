<?php

namespace App\Services\School;

use App\Models\School\School;
use App\Models\School\Canteen;

/**
 * 学校服务层
 */
class SchoolService
{
    /**
     * 获取学校列表
     */
    public function getList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $query = School::search($params['keyword'] ?? null)
            ->byStatus($params['status'] ?? null);

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
                    'school_name' => $item->school_name,
                    'school_code' => $item->school_code,
                    'contact_name' => $item->contact_name,
                    'contact_phone' => $item->contact_phone,
                    'address' => $item->address,
                    'status' => $item->status,
                    'status_text' => $item->getStatusText(),
                    'canteen_count' => $item->canteens()->count(),
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 创建学校
     */
    public function create(array $data): School
    {
        if (!isset($data['status'])) {
            $data['status'] = School::STATUS_ACTIVE;
        }

        return School::create($data);
    }

    /**
     * 更新学校
     */
    public function update(int $id, array $data): School
    {
        $school = School::findOrFail($id);
        $school->update($data);

        return $school->fresh();
    }

    /**
     * 删除学校
     */
    public function delete(int $id): bool
    {
        $school = School::findOrFail($id);

        if ($school->canteens()->count() > 0) {
            throw new \Exception('该学校下存在食堂，无法删除');
        }

        return $school->delete();
    }

    /**
     * 获取学校详情
     */
    public function getDetail(int $id): array
    {
        $school = School::findOrFail($id);

        return [
            'id' => $school->id,
            'school_name' => $school->school_name,
            'school_code' => $school->school_code,
            'contact_name' => $school->contact_name,
            'contact_phone' => $school->contact_phone,
            'address' => $school->address,
            'status' => $school->status,
            'status_text' => $school->getStatusText(),
            'credit_code' => $school->credit_code,
            'bank_name' => $school->bank_name,
            'bank_account' => $school->bank_account,
            'remark' => $school->remark,
            'is_active' => $school->is_active,
            'created_at' => $school->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取所有启用的学校
     */
    public function getActiveSchools(): array
    {
        $schools = School::where('status', School::STATUS_ACTIVE)
            ->where('is_active', true)
            ->orderBy('school_name')
            ->get();

        return $schools->map(function ($item) {
            return [
                'id' => $item->id,
                'school_name' => $item->school_name,
            ];
        })->toArray();
    }
}