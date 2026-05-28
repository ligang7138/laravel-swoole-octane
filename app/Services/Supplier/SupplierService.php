<?php

namespace App\Services\Supplier;

use App\Models\Supplier\Supplier;
use App\Models\Supplier\DiscountLog;
use Illuminate\Support\Facades\DB;

/**
 * 供应商服务层
 */
class SupplierService
{
    /**
     * 获取供应商列表
     */
    public function getList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        $query = Supplier::search($params['keyword'] ?? null)
            ->byStatus($params['status'] ?? null);

        // 排序
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
                    'supplier_name' => $item->supplier_name,
                    'contact_name' => $item->contact_name,
                    'contact_phone' => $item->contact_phone,
                    'contact_address' => $item->contact_address,
                    'status' => $item->status,
                    'status_text' => $item->getStatusText(),
                    'discount' => $item->discount,
                    'goods_count' => $item->goods()->count(),
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 创建供应商
     */
    public function create(array $data): Supplier
    {
        // 默认状态为待审核
        if (!isset($data['status'])) {
            $data['status'] = Supplier::STATUS_PENDING;
        }

        // 默认折扣为100（无折扣）
        if (!isset($data['discount'])) {
            $data['discount'] = 100.00;
        }

        return Supplier::create($data);
    }

    /**
     * 更新供应商
     */
    public function update(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);

        return $supplier->fresh();
    }

    /**
     * 删除供应商
     */
    public function delete(int $id): bool
    {
        $supplier = Supplier::findOrFail($id);

        // 检查是否有关联商品
        if ($supplier->goods()->count() > 0) {
            throw new \Exception('该供应商下存在商品，无法删除');
        }

        return $supplier->delete();
    }

    /**
     * 更改供应商状态
     */
    public function changeStatus(int $id, int $status): Supplier
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->status = $status;
        $supplier->save();

        return $supplier;
    }

    /**
     * 获取供应商详情
     */
    public function getDetail(int $id): array
    {
        $supplier = Supplier::findOrFail($id);

        return [
            'id' => $supplier->id,
            'supplier_name' => $supplier->supplier_name,
            'contact_name' => $supplier->contact_name,
            'contact_phone' => $supplier->contact_phone,
            'contact_address' => $supplier->contact_address,
            'license_no' => $supplier->license_no,
            'license_image' => $supplier->license_image,
            'bank_name' => $supplier->bank_name,
            'bank_account' => $supplier->bank_account,
            'bank_holder' => $supplier->bank_holder,
            'status' => $supplier->status,
            'status_text' => $supplier->getStatusText(),
            'discount' => $supplier->discount,
            'remark' => $supplier->remark,
            'is_active' => $supplier->is_active,
            'created_at' => $supplier->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $supplier->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取所有启用的供应商
     */
    public function getActiveSuppliers(): array
    {
        $suppliers = Supplier::where('status', Supplier::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('supplier_name')
            ->get();

        return $suppliers->map(function ($item) {
            return [
                'id' => $item->id,
                'supplier_name' => $item->supplier_name,
                'contact_name' => $item->contact_name,
                'contact_phone' => $item->contact_phone,
            ];
        })->toArray();
    }

    /**
     * 更新学校折扣
     */
    public function updateDiscount(int $supplierId, int $schoolId, float $discount, int $operatorId, string $remark = ''): bool
    {
        $supplier = Supplier::findOrFail($supplierId);

        $oldDiscount = $supplier->discount;

        DB::beginTransaction();
        try {
            // 更新供应商折扣
            $supplier->discount = $discount;
            $supplier->save();

            // 记录折扣变更日志
            DiscountLog::create([
                'supplier_id' => $supplierId,
                'school_id' => $schoolId,
                'discount' => $discount,
                'old_discount' => $oldDiscount,
                'operator_id' => $operatorId,
                'remark' => $remark,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取折扣变更记录
     */
    public function getDiscountLogs(int $supplierId): array
    {
        $logs = DiscountLog::with('school')
            ->where('supplier_id', $supplierId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $logs->map(function ($item) {
            return [
                'id' => $item->id,
                'school_name' => $item->school?->school_name,
                'discount' => $item->discount,
                'old_discount' => $item->old_discount,
                'remark' => $item->remark,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }
}