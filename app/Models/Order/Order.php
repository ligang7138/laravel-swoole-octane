<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 订单模型
 * 对应表: orders
 */
class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected static function newFactory()
    {
        return \Database\Factories\OrderFactory::new();
    }

    protected $fillable = [
        'order_sn',
        'canteen_id',
        'supp_id',
        'school_id',
        'status',
        'order_type',
        'replenish_type_id',
        'audit_status',
        'audit_time',
        'audit_user_type',
        'send_date',
        'total_price',
        'send_price',
        'receive_price',
        'back_price',
        'settle_price',
        'is_send_late',
        'inspection_report',
        'remark',
        'add_time',
        'update_time',
    ];

    protected $casts = [
        'canteen_id' => 'integer',
        'supp_id' => 'integer',
        'school_id' => 'integer',
        'status' => 'integer',
        'order_type' => 'integer',
        'replenish_type_id' => 'integer',
        'audit_status' => 'integer',
        'audit_time' => 'integer',
        'audit_user_type' => 'integer',
        'send_date' => 'date',
        'total_price' => 'decimal:2',
        'send_price' => 'decimal:2',
        'receive_price' => 'decimal:2',
        'back_price' => 'decimal:2',
        'settle_price' => 'decimal:2',
        'is_send_late' => 'integer',
        'add_time' => 'integer',
        'update_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 订单状态常量
     */
    const STATUS_DRAFT = 0;       // 草稿
    const STATUS_PENDING = 1;     // 待审核
    const STATUS_APPROVED = 2;    // 已审核
    const STATUS_SHIPPED = 3;     // 已发货
    const STATUS_RECEIVED = 4;    // 已收货
    const STATUS_CANCELLED = 5;   // 已取消

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        $statusMap = [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已审核',
            self::STATUS_SHIPPED => '已发货',
            self::STATUS_RECEIVED => '已收货',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    /**
     * 订单商品
     */
    public function goods()
    {
        return $this->hasMany(OrderGoods::class, 'order_id');
    }

    /**
     * 所属学校
     */
    public function school()
    {
        return $this->belongsTo(\App\Models\School\School::class, 'school_id');
    }

    /**
     * 所属食堂
     */
    public function canteen()
    {
        return $this->belongsTo(\App\Models\School\Canteen::class, 'canteen_id');
    }

    /**
     * 供应商
     */
    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier\Supplier::class, 'supp_id');
    }

    /**
     * 搜索作用域
     */
    public function scopeSearch($query, $keyword)
    {
        if ($keyword) {
            $query->where('order_sn', 'like', "%{$keyword}%");
        }
        return $query;
    }

    /**
     * 供应商筛选
     */
    public function scopeBySupplier($query, $supplierId)
    {
        if ($supplierId) {
            $query->where('supp_id', $supplierId);
        }
        return $query;
    }

    /**
     * 日期范围筛选
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->where('send_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('send_date', '<=', $endDate);
        }
        return $query;
    }
}