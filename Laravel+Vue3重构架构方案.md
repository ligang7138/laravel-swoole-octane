# Laravel + Vue3 前后端分离重构架构方案

> 基于旧系统完整业务白皮书，设计全新前后端分离架构，100%兼容原有业务逻辑

---

## 一、整体架构图（文字版）

```
┌─────────────────────────────────────────────────────────────────────┐
│                         客户端层 (Client)                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │  管理后台SPA  │  │  学校/食堂H5  │  │  第三方API调用 │              │
│  │  Vue3+ElemPlus│  │  Vue3+Vant   │  │  SM3签名调用  │              │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘              │
└─────────┼─────────────────┼─────────────────┼──────────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         网关层 (Gateway)                            │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Nginx 反向代理 + CORS跨域 + 静态资源 + SSL                   │  │
│  └──────────────────────────────────────────────────────────────┘  │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      Laravel 后端 (API Server)                      │
│                                                                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                │
│  │ Admin API   │  │ Home API    │  │ Open API    │                │
│  │ /api/admin/*│  │ /api/home/* │  │ /api/open/* │                │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘                │
│         │                │                │                         │
│  ┌──────▼────────────────▼────────────────▼──────┐                 │
│  │              中间件管道 (Middleware)             │                 │
│  │  CORS → JWT认证 → RBAC权限 → 操作日志 → 限流   │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              控制器层 (Controller)              │                 │
│  │  参数接收 → 调用Service → 返回统一Response      │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              验证器层 (FormRequest)             │                 │
│  │  参数校验 → 规则定义 → 自定义错误消息            │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              服务层 (Service)                   │                 │
│  │  业务逻辑编排 → 调用Logic/Repository → 事务管理  │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              逻辑层 (Logic)                     │                 │
│  │  核心业务规则 → 状态流转 → 级联更新 → 通知       │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              仓库层 (Repository)                │                 │
│  │  数据访问 → 查询构建 → 复杂SQL封装               │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │              模型层 (Model / Eloquent)          │                 │
│  │  ORM映射 → 关联关系 → 访问器/修改器 → 事件      │                 │
│  └──────────────────────┬────────────────────────┘                 │
│                         │                                           │
│  ┌──────────────────────▼────────────────────────┐                 │
│  │           定时任务 (Console/Commands)           │                 │
│  │  账单生成 → 竞价处理 → 报价同步 → 统计汇总      │                 │
│  └───────────────────────────────────────────────┘                 │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
          ┌────────────────────┼────────────────────┐
          ▼                    ▼                    ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│   MySQL 8.0      │ │   Redis 7.x      │ │   OSS/本地存储    │
│   业务数据存储    │ │   缓存/Session   │ │   文件上传存储    │
│   读写分离       │ │   队列/锁        │ │                  │
└──────────────────┘ └──────────────────┘ └──────────────────┘
```

### 请求流转图

```
客户端请求
    │
    ▼
Nginx (路由分发)
    │
    ├── /api/admin/* ──→ Admin路由组 (JWT + RBAC中间件)
    ├── /api/home/*  ──→ Home路由组  (JWT + 食堂认证中间件)
    ├── /api/open/*  ──→ Open路由组  (SM3签名认证中间件)
    │
    ▼
Middleware管道
    │
    ▼
Controller (接收参数)
    │
    ▼
FormRequest (参数校验)
    │
    ▼
Service (业务编排)
    │
    ├──→ Logic (核心规则)
    ├──→ Repository (数据访问)
    ├──→ Model (ORM)
    │
    ▼
统一ApiResponse返回
```

---

## 二、后端目录结构标准

```
jiaowei-daxing-server/                    # Laravel项目根目录
├── app/
│   ├── Console/
│   │   └── Commands/                      # 定时任务命令
│   │       ├── GenerateReceipt.php        # 账单自动生成
│   │       ├── ProcessBiddingExpiry.php   # 竞价到期处理
│   │       ├── SyncJiagewang.php          # 指导价同步
│   │       ├── SyncBill.php               # 账单同步
│   │       ├── DailyReport.php            # 日统计汇总
│   │       └── ProcessDiscount.php        # 报价到期处理
│   │
│   ├── Exceptions/
│   │   ├── Handler.php                    # 全局异常处理器
│   │   └── BusinessException.php          # 业务异常类
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                     # 后台管理控制器
│   │   │   │   ├── BaseController.php
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── GoodsController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── SupplierController.php
│   │   │   │   ├── SchoolController.php
│   │   │   │   ├── SchoolCanteenController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── BackorderController.php
│   │   │   │   ├── BiddingController.php
│   │   │   │   ├── CommentController.php
│   │   │   │   ├── ComplaintController.php
│   │   │   │   ├── EmergencyController.php
│   │   │   │   ├── ApproveController.php
│   │   │   │   ├── JiagewangController.php
│   │   │   │   ├── ReceivableController.php
│   │   │   │   ├── StatController.php
│   │   │   │   ├── DataAnalysisController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── PostController.php
│   │   │   │   ├── DepartmentController.php
│   │   │   │   ├── GroupController.php
│   │   │   │   ├── SchoolDistrictController.php
│   │   │   │   ├── LogController.php
│   │   │   │   └── ApiController.php
│   │   │   │
│   │   │   ├── Home/                      # 学校/食堂前端控制器
│   │   │   │   ├── BaseController.php
│   │   │   │   ├── HomeController.php
│   │   │   │   ├── GoodsController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── CartController.php
│   │   │   │   ├── BackorderController.php
│   │   │   │   ├── BiddingController.php
│   │   │   │   ├── CommentController.php
│   │   │   │   ├── ComplaintController.php
│   │   │   │   ├── EmergencyController.php
│   │   │   │   ├── BillController.php
│   │   │   │   ├── FavoriteController.php
│   │   │   │   ├── SupplierController.php
│   │   │   │   ├── ApproveController.php
│   │   │   │   ├── StatController.php
│   │   │   │   ├── AuthController.php
│   │   │   │   └── SsoController.php
│   │   │   │
│   │   │   └── OpenApi/                   # 第三方开放API控制器
│   │   │       ├── BaseController.php
│   │   │       ├── GoodsController.php
│   │   │       └── OrderController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── JwtAuth.php                # JWT认证中间件
│   │   │   ├── RbacPermission.php         # RBAC权限中间件
│   │   │   ├── Sm3Signature.php           # SM3签名认证中间件
│   │   │   ├── OperationLog.php           # 操作日志中间件
│   │   │   └── CanteenAuth.php            # 食堂身份认证中间件
│   │   │
│   │   ├── Requests/                      # 表单验证器
│   │   │   ├── Admin/
│   │   │   │   ├── GoodsAddRequest.php
│   │   │   │   ├── GoodsEditRequest.php
│   │   │   │   ├── SupplierAddRequest.php
│   │   │   │   ├── SupplierEditRequest.php
│   │   │   │   ├── SchoolAddRequest.php
│   │   │   │   ├── SchoolEditRequest.php
│   │   │   │   ├── SchoolCanteenAddRequest.php
│   │   │   │   ├── SchoolCanteenEditRequest.php
│   │   │   │   ├── OrderFixRequest.php
│   │   │   │   ├── BackorderAuditRequest.php
│   │   │   │   ├── BiddingAuditRequest.php
│   │   │   │   ├── ReceiptAddRequest.php
│   │   │   │   ├── UserAddRequest.php
│   │   │   │   ├── UserEditRequest.php
│   │   │   │   ├── PostAddRequest.php
│   │   │   │   ├── PostEditRequest.php
│   │   │   │   ├── ChangePasswordRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Home/
│   │   │   │   ├── OrderCreateRequest.php
│   │   │   │   ├── BackorderAddRequest.php
│   │   │   │   ├── CommentAddRequest.php
│   │   │   │   ├── ComplaintAddRequest.php
│   │   │   │   ├── EmergencyAddRequest.php
│   │   │   │   ├── BiddingApplyRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   └── OpenApi/
│   │   │       └── Sm3SignedRequest.php
│   │   │
│   │   ├── Resources/                     # API资源转换器
│   │   │   ├── Admin/
│   │   │   │   ├── GoodsResource.php
│   │   │   │   ├── GoodsCollection.php
│   │   │   │   ├── SupplierResource.php
│   │   │   │   ├── SchoolResource.php
│   │   │   │   ├── SchoolCanteenResource.php
│   │   │   │   ├── OrderResource.php
│   │   │   │   ├── OrderCollection.php
│   │   │   │   ├── BackorderResource.php
│   │   │   │   ├── BiddingResource.php
│   │   │   │   ├── CommentResource.php
│   │   │   │   ├── ComplaintResource.php
│   │   │   │   ├── EmergencyResource.php
│   │   │   │   ├── ReceiptResource.php
│   │   │   │   ├── AccountResource.php
│   │   │   │   ├── UserResource.php
│   │   │   │   └── MenuResource.php
│   │   │   └── Home/
│   │   │       ├── GoodsListResource.php
│   │   │       ├── GoodsDetailResource.php
│   │   │       ├── OrderListResource.php
│   │   │       ├── OrderDetailResource.php
│   │   │       ├── CartResource.php
│   │   │       └── BillResource.php
│   │   │
│   │   └── Kernel.php
│   │
│   ├── Models/                            # Eloquent模型
│   │   ├── User.php
│   │   ├── SsoUser.php
│   │   ├── Supplier.php
│   │   ├── School.php
│   │   ├── SchoolCanteen.php
│   │   ├── SchoolUser.php
│   │   ├── SchoolDistrict.php
│   │   ├── Category.php
│   │   ├── Goods.php
│   │   ├── GoodsUnit.php
│   │   ├── GoodsFinance.php
│   │   ├── GoodsJiagewang.php
│   │   ├── GoodsJiagewangLog.php
│   │   ├── Discount.php
│   │   ├── DiscountCategory.php
│   │   ├── DiscountAttachment.php
│   │   ├── DiscountLog.php
│   │   ├── Cart.php
│   │   ├── Order.php
│   │   ├── OrdersGoods.php
│   │   ├── OrdersGoodsFixLog.php
│   │   ├── Delivery.php
│   │   ├── DeliveryGoods.php
│   │   ├── Backorder.php
│   │   ├── BackorderType.php
│   │   ├── BiddingHistory.php
│   │   ├── BiddingLog.php
│   │   ├── Comment.php
│   │   ├── Complaint.php
│   │   ├── ComplaintType.php
│   │   ├── Emergency.php
│   │   ├── EmergencyType.php
│   │   ├── ReplenishType.php
│   │   ├── Favorite.php
│   │   ├── ReceivableAccount.php
│   │   ├── ReceivableReceipt.php
│   │   ├── ReceivableInvoice.php
│   │   ├── SupplierApprove.php
│   │   ├── SystemMenu.php
│   │   ├── SystemLog.php
│   │   ├── SystemSms.php
│   │   ├── Department.php
│   │   ├── Post.php
│   │   ├── Group.php
│   │   ├── ApiAuth.php
│   │   ├── ApiUrl.php
│   │   ├── ApiRequest.php
│   │   ├── Message.php
│   │   ├── UserMessage.php
│   │   ├── OrdersRecord.php
│   │   ├── OrdersRecordSchool.php
│   │   ├── CategoryRecord.php
│   │   └── CategoryRecordSchool.php
│   │
│   ├── Services/                          # 服务层（业务编排）
│   │   ├── Admin/
│   │   │   ├── GoodsService.php
│   │   │   ├── CategoryService.php
│   │   │   ├── SupplierService.php
│   │   │   ├── SchoolService.php
│   │   │   ├── SchoolCanteenService.php
│   │   │   ├── OrderService.php
│   │   │   ├── BackorderService.php
│   │   │   ├── BiddingService.php
│   │   │   ├── CommentService.php
│   │   │   ├── ComplaintService.php
│   │   │   ├── EmergencyService.php
│   │   │   ├── ApproveService.php
│   │   │   ├── JiagewangService.php
│   │   │   ├── ReceivableService.php
│   │   │   ├── StatService.php
│   │   │   ├── DataAnalysisService.php
│   │   │   ├── UserService.php
│   │   │   ├── PostService.php
│   │   │   ├── DepartmentService.php
│   │   │   ├── GroupService.php
│   │   │   └── AuthService.php
│   │   ├── Home/
│   │   │   ├── GoodsService.php
│   │   │   ├── OrderService.php
│   │   │   ├── CartService.php
│   │   │   ├── BackorderService.php
│   │   │   ├── BiddingService.php
│   │   │   ├── CommentService.php
│   │   │   ├── ComplaintService.php
│   │   │   ├── EmergencyService.php
│   │   │   ├── BillService.php
│   │   │   ├── FavoriteService.php
│   │   │   ├── SupplierService.php
│   │   │   ├── ApproveService.php
│   │   │   ├── StatService.php
│   │   │   └── AuthService.php
│   │   └── OpenApi/
│   │       ├── GoodsService.php
│   │       └── OrderService.php
│   │
│   ├── Logic/                             # 逻辑层（核心业务规则）
│   │   ├── OrderLogic.php                 # 订单状态流转/级联更新
│   │   ├── ReceiptLogic.php               # 对账单检测报告状态级联
│   │   ├── PriceLogic.php                 # 价格计算（指导价/报价/限高价）
│   │   ├── BiddingLogic.php               # 竞价/合作状态流转
│   │   ├── BackorderLogic.php             # 退货状态流转/账单级联
│   │   ├── AccountPeriodLogic.php         # 账期计算
│   │   └── InspectionReportLogic.php      # 检测报告状态级联
│   │
│   ├── Repositories/                      # 仓库层（数据访问）
│   │   ├── BaseRepository.php
│   │   ├── GoodsRepository.php
│   │   ├── OrderRepository.php
│   │   ├── SupplierRepository.php
│   │   ├── SchoolCanteenRepository.php
│   │   ├── BackorderRepository.php
│   │   ├── BiddingRepository.php
│   │   ├── ReceivableRepository.php
│   │   ├── JiagewangRepository.php
│   │   └── StatRepository.php
│   │
│   ├── Enums/                             # 枚举类（替代旧系统常量/魔法数字）
│   │   ├── OrderStatus.php                # 10取消/20下单/30配货/40发货/50收货
│   │   ├── OrderType.php                  # 1正常/2补单
│   │   ├── AuditStatus.php               # 0待审核/1通过/2拒绝
│   │   ├── BackorderStatus.php            # 1取消/2拒绝/3待审核/4通过
│   │   ├── BackorderType.php              # 1仅退款/2退货退款
│   │   ├── BiddingAuditStatus.php         # 1待审批/2拒绝/3通过
│   │   ├── BiddingType.php                # 1申请合作/2终止合作
│   │   ├── BiddingLogStatus.php           # 0解绑/1合作中
│   │   ├── CommentType.php                # 1好评/2差评
│   │   ├── CanteenType.php                # 1教师食堂/2学生食堂
│   │   ├── GoodsAttr.php                  # 1非标/2标品/3特种
│   │   ├── GoodsLevel.php                 # 1普通/2精品
│   │   ├── GoodsType.php                  # 0通用/1教师专用
│   │   ├── GoodsChannel.php               # 0否/1特渠
│   │   ├── IdentityType.php               # 1管理/2供应商/3学校
│   │   ├── SupplierCateType.php           # 1全品类/2指定品类
│   │   ├── AccountType.php                # 1固定每月每日/2固定天数/3半月结/4十天结
│   │   ├── AccountCreateType.php          # 1按生成时间/2按配送时间
│   │   ├── ReceivableAccountType.php      # 1订单/2退单
│   │   ├── ProcessStatus.php              # 0未处理/1已处理
│   │   ├── ReviewStatus.php               # 0未审阅/1已审阅
│   │   └── ApproveCheckStatus.php         # 0未审批/1通过/2拒绝
│   │
│   ├── Traits/                            # 可复用Trait
│   │   ├── PaginateTrait.php              # 统一分页处理
│   │   ├── ExportTrait.php                # 统一导出处理
│   │   └── UploadTrait.php                # 统一上传处理
│   │
│   └── Helpers/                           # 全局辅助
│       ├── ApiResponse.php                # 统一响应构造器
│       ├── Sm3Helper.php                  # SM3国密签名
│       ├── CnyHelper.php                  # 金额大写转换
│       ├── PriceHelper.php                # 价格计算辅助
│       └── MessageHelper.php              # 站内信发送
│
├── config/
│   ├── auth.php                           # JWT配置
│   ├── database.php                       # 数据库配置(读写分离)
│   ├── jiaowei.php                        # 业务自定义配置
│   └── ...
│
├── database/
│   ├── migrations/                        # 数据库迁移文件
│   │   ├── 0001_create_sso_users_table.php
│   │   ├── 0002_create_users_table.php
│   │   ├── 0003_create_suppliers_table.php
│   │   ├── 0004_create_schools_table.php
│   │   ├── 0005_create_school_canteens_table.php
│   │   ├── 0006_create_categories_table.php
│   │   ├── 0007_create_goods_table.php
│   │   ├── 0008_create_orders_table.php
│   │   ├── ... (全部60+张表)
│   │   └── 9999_add_new_rbac_tables.php  # 新增RBAC表
│   └── seeders/                           # 数据填充
│       ├── AdminMenuSeeder.php            # 菜单权限种子
│       ├── AdminUserSeeder.php            # 管理员种子
│       └── CategorySeeder.php             # 分类种子
│
├── routes/
│   ├── api.php                            # API路由总入口
│   ├── admin.php                          # 后台管理路由
│   ├── home.php                           # 学校/食堂前端路由
│   └── open.php                           # 第三方开放API路由
│
├── storage/
│   └── app/
│       └── public/
│           └── upload/                    # 文件上传目录
│
├── .env                                   # 环境配置
├── composer.json
└── artisan
```

---

## 三、前端目录结构标准

```
jiaowei-daxing-web/                        # Vue3项目根目录
├── public/
│   ├── favicon.ico
│   └── index.html
│
├── src/
│   ├── api/                               # API接口定义
│   │   ├── admin/                         # 后台管理接口
│   │   │   ├── auth.ts                    # 登录/修改密码
│   │   │   ├── goods.ts                   # 商品管理
│   │   │   ├── category.ts                # 分类管理
│   │   │   ├── supplier.ts                # 供应商管理
│   │   │   ├── school.ts                  # 学校管理
│   │   │   ├── schoolCanteen.ts           # 食堂管理
│   │   │   ├── order.ts                   # 订单管理
│   │   │   ├── backorder.ts               # 退货管理
│   │   │   ├── bidding.ts                 # 竞价管理
│   │   │   ├── comment.ts                 # 评价管理
│   │   │   ├── complaint.ts               # 投诉管理
│   │   │   ├── emergency.ts               # 应急管理
│   │   │   ├── approve.ts                 # 审阅管理
│   │   │   ├── jiagewang.ts               # 价格网
│   │   │   ├── receivable.ts              # 应收账单
│   │   │   ├── stat.ts                    # 统计报表
│   │   │   ├── dataAnalysis.ts            # 数据分析
│   │   │   ├── user.ts                    # 用户管理
│   │   │   ├── post.ts                    # 岗位管理
│   │   │   ├── department.ts              # 部门管理
│   │   │   ├── group.ts                   # 分组管理
│   │   │   └── log.ts                     # 日志管理
│   │   └── home/                          # 学校/食堂前端接口
│   │       ├── auth.ts
│   │       ├── goods.ts
│   │       ├── order.ts
│   │       ├── cart.ts
│   │       ├── backorder.ts
│   │       ├── bidding.ts
│   │       ├── comment.ts
│   │       ├── complaint.ts
│   │       ├── emergency.ts
│   │       ├── bill.ts
│   │       ├── favorite.ts
│   │       ├── supplier.ts
│   │       ├── approve.ts
│   │       └── stat.ts
│   │
│   ├── assets/                            # 静态资源
│   │   ├── styles/
│   │   │   ├── variables.scss             # 全局SCSS变量
│   │   │   ├── mixins.scss               # SCSS混入
│   │   │   ├── reset.scss                # 样式重置
│   │   │   └── common.scss               # 公共样式
│   │   ├── images/
│   │   └── fonts/
│   │
│   ├── components/                        # 全局公共组件
│   │   ├── Layout/
│   │   │   ├── AdminLayout.vue            # 后台布局（侧边栏+Tab页）
│   │   │   ├── HomeLayout.vue             # 食堂前端布局
│   │   │   ├── Sidebar.vue                # 侧边栏菜单
│   │   │   ├── TabView.vue                # Tab页签视图
│   │   │   ├── Navbar.vue                 # 顶部导航
│   │   │   └── Breadcrumb.vue             # 面包屑
│   │   ├── Table/
│   │   │   ├── SearchTable.vue            # 搜索+表格+分页 组件
│   │   │   └── TableToolbar.vue           # 表格工具栏
│   │   ├── Form/
│   │   │   ├── FormDialog.vue             # 表单弹窗
│   │   │   ├── ImageUpload.vue            # 图片上传
│   │   │   ├── FileUpload.vue             # 文件上传
│   │   │   └── RichEditor.vue             # 富文本编辑器
│   │   ├── Export/
│   │   │   └── ExportButton.vue           # 导出按钮
│   │   ├── Status/
│   │   │   └── StatusTag.vue              # 状态标签
│   │   └── Permission/
│   │       └── PermissionButton.vue       # 权限按钮
│   │
│   ├── composables/                       # 组合式函数（Hooks）
│   │   ├── useAuth.ts                     # 认证相关
│   │   ├── usePermission.ts               # 权限相关
│   │   ├── useTable.ts                    # 表格通用逻辑
│   │   ├── useForm.ts                     # 表单通用逻辑
│   │   ├── useExport.ts                   # 导出通用逻辑
│   │   └── useDict.ts                     # 字典数据
│   │
│   ├── constants/                         # 常量定义
│   │   ├── orderStatus.ts                 # 订单状态映射
│   │   ├── backorderStatus.ts             # 退货状态映射
│   │   ├── canteenType.ts                 # 食堂类型映射
│   │   ├── goodsAttr.ts                   # 商品属性映射
│   │   └── index.ts                       # 统一导出
│   │
│   ├── directives/                        # 自定义指令
│   │   └── permission.ts                  # v-permission 权限指令
│   │
│   ├── router/                            # 路由
│   │   ├── index.ts                       # 路由入口
│   │   ├── admin.ts                       # 后台管理路由
│   │   ├── home.ts                        # 食堂前端路由
│   │   └── guards.ts                      # 路由守卫
│   │
│   ├── stores/                            # Pinia状态管理
│   │   ├── auth.ts                        # 认证状态
│   │   ├── permission.ts                  # 权限/菜单状态
│   │   ├── app.ts                         # 应用全局状态
│   │   └── dict.ts                        # 字典缓存
│   │
│   ├── types/                             # TypeScript类型定义
│   │   ├── api.d.ts                       # API响应类型
│   │   ├── models.d.ts                    # 数据模型类型
│   │   └── global.d.ts                    # 全局类型
│   │
│   ├── utils/                             # 工具函数
│   │   ├── request.ts                     # Axios封装（拦截器/统一错误处理）
│   │   ├── auth.ts                        # Token管理
│   │   ├── permission.ts                  # 权限判断
│   │   ├── validate.ts                    # 表单校验规则
│   │   ├── format.ts                      # 格式化（日期/金额/状态）
│   │   └── export.ts                      # 导出工具
│   │
│   └── views/                             # 页面视图
│       ├── admin/                         # 后台管理页面
│       │   ├── login/
│       │   │   └── index.vue
│       │   ├── home/
│       │   │   ├── index.vue              # 首页仪表盘
│       │   │   ├── password.vue           # 修改密码
│       │   │   ├── goods-prices.vue       # 价格变动
│       │   │   ├── orders.vue             # 近期订单
│       │   │   ├── supplier.vue           # 供应商信息
│       │   │   └── supply-ranking.vue     # 供货排名
│       │   ├── goods/
│       │   │   ├── index.vue              # 商品列表
│       │   │   ├── add.vue                # 新增商品
│       │   │   ├── edit.vue               # 编辑商品
│       │   │   ├── finance.vue            # 财务信息
│       │   │   ├── history-price.vue      # 历史价格
│       │   │   ├── report.vue             # 检测报告
│       │   │   ├── status-log.vue         # 状态日志
│       │   │   ├── supplier.vue           # 供应商报价
│       │   │   └── unit.vue               # 计量单位
│       │   ├── category/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   └── edit.vue
│       │   ├── supplier/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   ├── stat.vue
│       │   │   ├── bidding.vue
│       │   │   └── api-config.vue
│       │   ├── school/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   └── user.vue
│       │   ├── school-canteen/
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   └── stat.vue
│       │   ├── order/
│       │   │   ├── index.vue
│       │   │   ├── view.vue
│       │   │   ├── edit.vue
│       │   │   └── trace-source.vue
│       │   ├── backorder/
│       │   │   ├── index.vue
│       │   │   ├── view.vue
│       │   │   ├── audit.vue
│       │   │   └── type.vue
│       │   ├── bidding/
│       │   │   ├── index.vue
│       │   │   ├── audit.vue
│       │   │   └── discount.vue
│       │   ├── comment/
│       │   │   └── index.vue
│       │   ├── complaint/
│       │   │   ├── index.vue
│       │   │   └── type.vue
│       │   ├── emergency/
│       │   │   ├── index.vue
│       │   │   └── type.vue
│       │   ├── approve/
│       │   │   ├── comment.vue
│       │   │   ├── complaint.vue
│       │   │   ├── bidding.vue
│       │   │   └── supplier.vue
│       │   ├── jiagewang/
│       │   │   ├── index.vue
│       │   │   ├── import.vue
│       │   │   ├── match.vue
│       │   │   ├── nomatch.vue
│       │   │   ├── history.vue
│       │   │   └── errorlist.vue
│       │   ├── receivable/
│       │   │   ├── account.vue
│       │   │   ├── receipt.vue
│       │   │   └── order.vue
│       │   ├── stat/
│       │   │   ├── order.vue
│       │   │   ├── goods.vue
│       │   │   ├── bidding.vue
│       │   │   ├── complaint.vue
│       │   │   ├── backorder.vue
│       │   │   ├── backorder-rate.vue
│       │   │   ├── ontime-rate.vue
│       │   │   ├── replenish.vue
│       │   │   └── replenish-rate.vue
│       │   ├── data-analysis/
│       │   │   └── goods.vue
│       │   ├── user/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   └── privilege.vue
│       │   ├── post/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   └── privilege.vue
│       │   ├── department/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   └── edit.vue
│       │   ├── group/
│       │   │   ├── index.vue
│       │   │   ├── add.vue
│       │   │   ├── edit.vue
│       │   │   └── school.vue
│       │   └── log/
│       │       ├── index.vue
│       │       └── view.vue
│       │
│       └── home/                          # 学校/食堂前端页面
│           ├── login/
│           │   └── index.vue
│           ├── home/
│           │   └── index.vue
│           ├── goods/
│           │   ├── list.vue
│           │   └── detail.vue
│           ├── order/
│           │   ├── list.vue
│           │   └── detail.vue
│           ├── cart/
│           │   └── index.vue
│           ├── backorder/
│           │   ├── list.vue
│           │   └── add.vue
│           ├── bidding/
│           │   ├── list.vue
│           │   └── apply.vue
│           ├── comment/
│           │   ├── list.vue
│           │   └── add.vue
│           ├── complaint/
│           │   ├── list.vue
│           │   └── add.vue
│           ├── emergency/
│           │   ├── list.vue
│           │   └── add.vue
│           ├── bill/
│           │   ├── list.vue
│           │   └── detail.vue
│           └── supplier/
│               ├── list.vue
│               └── detail.vue
│
├── .env.development                       # 开发环境配置
├── .env.production                        # 生产环境配置
├── vite.config.ts
├── tsconfig.json
├── package.json
└── index.html
```

---

## 四、全局统一规范

### 4.1 统一API响应格式

```php
// 成功响应
{
    "code": 200,
    "msg": "Success",
    "data": { ... }
}

// 分页响应
{
    "code": 200,
    "msg": "Success",
    "data": {
        "list": [ ... ],
        "pagination": {
            "total": 100,
            "page": 1,
            "page_size": 20,
            "page_count": 5
        }
    }
}

// 失败响应
{
    "code": 40001,
    "msg": "参数错误",
    "data": null
}

// 表单校验失败
{
    "code": 422,
    "msg": "验证失败",
    "data": {
        "errors": {
            "goods_name": ["商品名称不能为空"]
        }
    }
}
```

**状态码对照表**（与老系统一一对应）：

| 新code | 旧status | 含义 |
|--------|----------|------|
| 200 | 200 | 成功 |
| 20001 | 20001 | 业务逻辑错误 |
| 40000 | 40000 | API签名错误 |
| 40001 | 40001 | 参数/业务错误 |
| 40002 | 40002 | 数据校验失败 |
| 40003 | 40003 | 登录超时/未认证 |
| 40004 | 40004 | 原密码错误 |
| 40005 | 40005 | 短信网关故障 |
| 40009 | 40009 | 操作失败(异常) |
| 40098 | 40098 | 无权限 |
| 40099 | 40099 | 方法不存在 |
| 422 | - | 表单验证失败(Laravel标准) |
| 401 | - | JWT认证失败 |
| 403 | - | 禁止访问 |

**ApiResponse 辅助类**：

```php
<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success(mixed $data = null, string $msg = 'Success', int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    public static function paginate($list, int $total, int $page, int $pageSize, string $msg = 'Success'): \Illuminate\Http\JsonResponse
    {
        return self::success([
            'list'       => $list,
            'pagination' => [
                'total'      => $total,
                'page'       => $page,
                'page_size'  => $pageSize,
                'page_count' => ceil($total / $pageSize) ?: 1,
            ],
        ], $msg);
    }

    public static function error(string $msg = 'Fail', int $code = 40001, mixed $data = null): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

    public static function noPermission(string $msg = '无权限'): \Illuminate\Http\JsonResponse
    {
        return self::error($msg, 40098);
    }

    public static function unauthenticated(string $msg = '登录超时'): \Illuminate\Http\JsonResponse
    {
        return self::error($msg, 40003);
    }
}
```

### 4.2 统一异常处理

```php
<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // 业务异常
        if ($e instanceof BusinessException) {
            return ApiResponse::error($e->getMessage(), $e->getCode());
        }

        // JWT认证失败
        if ($e instanceof UnauthorizedHttpException) {
            return ApiResponse::unauthenticated('登录超时，请重新登录');
        }

        // 表单验证失败
        if ($e instanceof ValidationException) {
            return response()->json([
                'code' => 422,
                'msg'  => '验证失败',
                'data' => [
                    'errors' => $e->errors(),
                ],
            ]);
        }

        // 其他异常
        return ApiResponse::error(
            config('app.debug') ? $e->getMessage() : '服务器内部错误',
            500
        );
    }
}
```

**BusinessException 业务异常类**：

```php
<?php

namespace App\Exceptions;

class BusinessException extends \RuntimeException
{
    public function __construct(string $message = '操作失败', int $code = 40001)
    {
        parent::__construct($message, $code);
    }
}
```

**使用方式**（与老系统 `exit(json_encode(...))` 一一对应）：

```php
// 老系统写法
exit(json_encode(array("status" => 40001, "data" => "参数错误")));

// 新系统写法
throw new BusinessException('参数错误', 40001);

// 老系统写法
exit(json_encode(array("status" => 200, "data" => "操作成功")));

// 新系统写法
return ApiResponse::success('操作成功');
```

### 4.3 统一参数校验（FormRequest）

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GoodsAddRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'goods_name'    => 'required|string|max:100',
            'cate_id'       => 'required|integer|exists:categories,id',
            'scate_id'      => 'nullable|integer|exists:categories,id',
            'attr'          => 'required|integer|in:1,2,3',
            'level'         => 'required|integer|in:1,2',
            'goods_type'    => 'required|integer|in:0,1',
            'goods_channel' => 'required|integer|in:0,1',
            'discount_rate' => 'required|numeric|min:0',
            'unit'          => 'required|string|max:20',
            'spec'          => 'nullable|string|max:100',
            'logo'          => 'nullable|string',
            'image_list'    => 'required|json',
            'status'        => 'required|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'goods_name.required'    => '商品名称不能为空',
            'cate_id.required'       => '请选择商品分类',
            'cate_id.exists'         => '商品分类不存在',
            'attr.in'                => '商品属性值无效',
            'level.in'               => '商品等级值无效',
            'discount_rate.numeric'  => '折扣率必须为数字',
            'image_list.required'    => '请上传商品图片',
        ];
    }
}
```

### 4.4 统一操作日志

```php
<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\SystemLog;
use Illuminate\Http\Request;

class OperationLog
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 只记录写操作
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $user = $request->user();
            SystemLog::create([
                'module'   => $request->segment(3) ?? '',  // api/admin/{module}
                'method'   => $request->segment(4) ?? '',  // api/admin/{module}/{action}
                'sql'      => '',  // 通过DB::listen获取
                'param'    => json_encode($request->except(['password', 'salt'])),
                'username' => $user?->name ?? '',
                'ip'       => $request->ip(),
                'add_time' => time(),
            ]);
        }

        return $response;
    }
}
```

### 4.5 枚举类规范

```php
<?php

namespace App\Enums;

enum OrderStatus: int
{
    case CANCEL  = 10;  // 已取消
    case ADD     = 20;  // 已下单
    case READY   = 30;  // 已配货
    case SEND    = 40;  // 已发货
    case RECEIVE = 50;  // 已收货

    public function label(): string
    {
        return match($this) {
            self::CANCEL  => '已取消',
            self::ADD     => '已下单',
            self::READY   => '已配货',
            self::SEND    => '已发货',
            self::RECEIVE => '已收货',
        };
    }
}
```

---

## 五、接口命名规范

### 5.1 URL路由规范

```
后端管理：POST /api/admin/{模块}/{动作}
前端接口：POST /api/home/{模块}/{动作}
开放接口：POST /api/open/{模块}/{动作}
```

### 5.2 动作命名规范

| 操作 | 动作名 | 说明 | HTTP方法 |
|------|--------|------|----------|
| 列表 | list | 获取分页列表 | GET/POST |
| 详情 | detail | 获取单条详情 | GET |
| 新增 | add | 新增记录 | POST |
| 编辑 | edit | 编辑记录 | POST |
| 删除 | delete | 删除记录 | POST |
| 状态变更 | setStatus | 启用/停用 | POST |
| 审核 | audit | 审核通过/拒绝 | POST |
| 导出 | export | 导出Excel | POST |
| 导入 | import | 导入Excel | POST |
| 重置密码 | resetPassword | 重置密码 | POST |
| 处理 | process | 业务处理 | POST |
| 审阅 | review | 审阅操作 | POST |

### 5.3 老系统路由映射对照表

| 老系统 | 新系统 |
|--------|--------|
| `POST /admin/rest.php?do=goods.add` | `POST /api/admin/goods/add` |
| `POST /admin/rest.php?do=goods.edit` | `POST /api/admin/goods/edit` |
| `POST /admin/rest.php?do=goods.setStatus` | `POST /api/admin/goods/set-status` |
| `POST /admin/rest.php?do=order.fix_orders_goods` | `POST /api/admin/order/fix-orders-goods` |
| `POST /admin/rest.php?do=backorder.audit` | `POST /api/admin/backorder/audit` |
| `POST /admin/rest.php?do=bidding.audit` | `POST /api/admin/bidding/audit` |
| `POST /admin/rest.php?do=comment.process` | `POST /api/admin/comment/process` |
| `POST /admin/rest.php?do=approve.comment_review` | `POST /api/admin/approve/comment-review` |
| `POST /admin/rest.php?do=jiagewang.import` | `POST /api/admin/jiagewang/import` |
| `POST /admin/rest.php?do=receivable.receiptAdd` | `POST /api/admin/receivable/receipt-add` |
| `POST /home/rest.php?do=order.getList` | `POST /api/home/order/list` |
| `POST /home/rest.php?do=order.confirmReceipt` | `POST /api/home/order/confirm-receipt` |
| `POST /home/rest.php?do=goods.getList` | `POST /api/home/goods/list` |
| `POST /home/rest.php?do=cart.add` | `POST /api/home/cart/add` |
| `GET /api/rest.php?method=goods.getList` | `POST /api/open/goods/list` |

### 5.4 路由文件定义

```php
// routes/admin.php
Route::prefix('admin')->middleware(['jwt.auth', 'rbac', 'operation.log'])->group(function () {

    // 首页
    Route::post('home/index', [HomeController::class, 'index']);
    Route::post('home/editPassword', [HomeController::class, 'editPassword']);

    // 商品管理
    Route::post('goods/list', [GoodsController::class, 'list']);
    Route::post('goods/add', [GoodsController::class, 'add']);
    Route::post('goods/edit', [GoodsController::class, 'edit']);
    Route::post('goods/setStatus', [GoodsController::class, 'setStatus']);
    Route::post('goods/setFinance', [GoodsController::class, 'setFinance']);
    Route::post('goods/uploadReport', [GoodsController::class, 'uploadReport']);
    Route::post('goods/export', [GoodsController::class, 'export']);
    Route::post('goods/import', [GoodsController::class, 'import']);

    // 分类管理
    Route::post('category/list', [CategoryController::class, 'list']);
    Route::post('category/add', [CategoryController::class, 'add']);
    Route::post('category/edit', [CategoryController::class, 'edit']);
    Route::post('category/setStatus', [CategoryController::class, 'setStatus']);

    // 供应商管理
    Route::post('supplier/list', [SupplierController::class, 'list']);
    Route::post('supplier/add', [SupplierController::class, 'add']);
    Route::post('supplier/edit', [SupplierController::class, 'edit']);
    Route::post('supplier/setStatus', [SupplierController::class, 'setStatus']);
    Route::post('supplier/resetPassword', [SupplierController::class, 'resetPassword']);
    Route::post('supplier/export', [SupplierController::class, 'export']);

    // ... 其他模块同上
});

// routes/home.php
Route::prefix('home')->middleware(['jwt.auth', 'canteen.auth'])->group(function () {

    // 商品
    Route::post('goods/list', [GoodsController::class, 'list']);
    Route::post('goods/detail', [GoodsController::class, 'detail']);

    // 订单
    Route::post('order/list', [OrderController::class, 'list']);
    Route::post('order/detail', [OrderController::class, 'detail']);
    Route::post('order/create', [OrderController::class, 'create']);
    Route::post('order/confirmReceipt', [OrderController::class, 'confirmReceipt']);

    // 购物车
    Route::post('cart/list', [CartController::class, 'list']);
    Route::post('cart/add', [CartController::class, 'add']);
    Route::post('cart/update', [CartController::class, 'update']);
    Route::post('cart/delete', [CartController::class, 'delete']);

    // ... 其他模块同上
});

// routes/open.php
Route::prefix('open')->middleware(['sm3.signature'])->group(function () {
    Route::post('goods/list', [GoodsController::class, 'list']);
    Route::post('order/list', [OrderController::class, 'list']);
});
```

---

## 六、数据库迁移优化方案

### 6.1 迁移原则

1. **表结构100%保留**：所有字段名、类型、默认值与老系统一致
2. **新增字段规范**：所有表新增 `created_at`、`updated_at` 标准时间字段
3. **旧时间字段保留**：`add_time`、`update_time` 等旧字段保留不动，不删除
4. **索引优化**：补充老系统缺失的索引
5. **字符集统一**：`utf8mb4` + `utf8mb4_unicode_ci`
6. **RBAC新增表**：新增标准RBAC权限表

### 6.2 新增RBAC权限表

老系统权限基于 `post.privilege`（逗号分隔的menu_id列表），新系统升级为标准RBAC：

```sql
-- 角色表（替代老系统post表）
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '角色标识',
  `display_name` varchar(100) NOT NULL COMMENT '角色名称',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 权限表（替代老系统system_menu表，保留原表数据）
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '权限标识(如goods.index)',
  `display_name` varchar(100) NOT NULL COMMENT '权限名称',
  `module` varchar(50) DEFAULT NULL COMMENT '所属模块',
  `sort` int DEFAULT 0 COMMENT '排序',
  `status` tinyint DEFAULT 1 COMMENT '1启用 0禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';

-- 角色-权限关联表
CREATE TABLE `role_permissions` (
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联';

-- 用户-角色关联表
CREATE TABLE `user_roles` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联';
```

### 6.3 数据迁移兼容方案

```sql
-- 保留旧表 post 和 system_menu 不删除
-- 旧 post 表的 privilege 字段（逗号分隔menu_id）迁移到 role_permissions
-- 旧 system_menu 表的 path 字段迁移到 permissions.name

-- 迁移SQL示例：将旧岗位转为角色
INSERT INTO roles (id, name, display_name, description)
SELECT id, CONCAT('post_', id), name, '' FROM post;

-- 迁移SQL示例：将旧菜单权限转为权限表
INSERT INTO permissions (id, name, display_name, module, sort, status)
SELECT id, path, func, module, sort, status FROM system_menu WHERE level = 2 AND path != '';

-- 迁移SQL示例：岗位-权限关联
INSERT INTO role_permissions (role_id, permission_id)
SELECT p.id, m.id FROM post p
CROSS JOIN system_menu m
WHERE FIND_IN_SET(m.id, p.privilege) AND m.level = 2 AND m.path != '';

-- 迁移SQL示例：用户-角色关联（从user表的post字段）
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, p.id FROM user u
INNER JOIN post p ON FIND_IN_SET(p.id, u.post)
WHERE u.post != '';
```

### 6.4 索引优化

```sql
-- orders 表索引优化
ALTER TABLE `orders` ADD INDEX `idx_canteen_status` (`canteen_id`, `status`);
ALTER TABLE `orders` ADD INDEX `idx_supp_status` (`supp_id`, `status`);
ALTER TABLE `orders` ADD INDEX `idx_send_date` (`send_date`);
ALTER TABLE `orders` ADD INDEX `idx_add_time` (`add_time`);
ALTER TABLE `orders` ADD INDEX `idx_order_sn` (`order_sn`);

-- orders_goods 表索引优化
ALTER TABLE `orders_goods` ADD INDEX `idx_order_id` (`order_id`);
ALTER TABLE `orders_goods` ADD INDEX `idx_goods_id` (`goods_id`);

-- discount_category 表索引优化
ALTER TABLE `discount_category` ADD INDEX `idx_supp_school_cate` (`supp_id`, `school_id`, `category_id`);

-- receivable_account 表索引优化
ALTER TABLE `receivable_account` ADD INDEX `idx_receipt_id` (`receipt_id`);
ALTER TABLE `receivable_account` ADD INDEX `idx_order_id` (`order_id`);

-- goods 表索引优化
ALTER TABLE `goods` ADD INDEX `idx_cate_status` (`cate_id`, `status`);
ALTER TABLE `goods` ADD INDEX `idx_scate_status` (`scate_id`, `status`);

-- bidding_log 表索引优化
ALTER TABLE `bidding_log` ADD INDEX `idx_canteen_supp` (`canteen_id`, `supp_id`);

-- system_log 表索引优化
ALTER TABLE `system_log` ADD INDEX `idx_add_time` (`add_time`);
ALTER TABLE `system_log` ADD INDEX `idx_username` (`username`);
```

### 6.5 字段类型优化

| 旧字段 | 旧类型 | 新类型 | 说明 |
|--------|--------|--------|------|
| 各表 add_time | int(timestamp) | int(timestamp) | **保留不动**，不改为timestamp类型 |
| image_list | text | json | JSON类型，便于MySQL原生JSON查询 |
| detail_image_list | text | json | 同上 |
| inspection_report | text | json | 同上 |
| attachments | text | json | 同上 |
| 新增 created_at | - | timestamp | Laravel标准字段 |
| 新增 updated_at | - | timestamp | Laravel标准字段 |

---

## 七、与老Smarty混编系统的差异改造点说明

### 7.1 架构层面差异

| 维度 | 老系统 | 新系统 | 改造说明 |
|------|--------|--------|----------|
| **渲染方式** | Smarty服务端渲染HTML | Vue3客户端渲染SPA | 前后端完全分离，后端只返回JSON |
| **路由** | URL文件路径映射 | Laravel路由 + Vue Router | 双端各自管理路由 |
| **会话** | MySQL session表 | JWT Token + Redis | 无状态认证，Token存Redis支持主动失效 |
| **权限** | Session存储+PHP检查 | JWT+中间件+前端指令 | 前后端双重权限控制 |
| **表单** | HTML form提交+PHP处理 | Vue表单+FormRequest | 前端校验+后端FormRequest双重校验 |
| **弹窗** | LayUI layer弹窗 | Element Plus Dialog | 组件化弹窗 |
| **表格** | HTML table+PHP循环 | Element Plus Table | 前端分页/搜索/排序 |
| **文件上传** | form multipart | Axios upload | 统一上传组件 |
| **导出** | PHP直接输出文件 | 后端生成+前端下载 | 后端返回文件流 |
| **菜单** | Smarty模板渲染 | Vue动态菜单+路由 | 根据权限动态生成 |

### 7.2 代码层面差异

| 维度 | 老系统 | 新系统 | 改造说明 |
|------|--------|--------|----------|
| **数据访问** | 原生SQL+PDO | Eloquent ORM + Repository | 复杂查询用Repository封装原生SQL |
| **参数获取** | `$_POST['key']` | `$request->input('key')` | 通过Request对象获取 |
| **响应输出** | `exit(json_encode(...))` | `return ApiResponse::success(...)` | 统一响应构造器 |
| **错误处理** | `exit(json_encode(error))` | `throw new BusinessException()` | 异常冒泡到统一Handler |
| **验证** | 手动if判断 | FormRequest自动验证 | 规则集中定义，自动触发 |
| **事务** | `$db->beginTrans()/commit()` | `DB::transaction(function(){})` | 闭包事务，自动回滚 |
| **日志** | `file_put_contents()` | Laravel Log + DB日志 | 多通道日志 |
| **缓存** | Redis直接操作 | Laravel Cache facade | 统一缓存接口 |
| **密码加密** | `md5(md5(pwd).salt)` | **保持不变** | 兼容老数据，新密码同样算法 |
| **时间戳** | `time()` + `date()` | Carbon | 但数据库int字段仍用time()存储 |

### 7.3 关键兼容点（必须100%保持一致）

1. **密码加密算法**：`md5(md5(明文) . salt)` — 新系统必须使用相同算法，否则老用户无法登录
2. **订单编号生成规则**：`date("ymdHis") + 2位随机 + 2位用户ID模100` — 必须完全一致
3. **供应商/学校编码**：`10000 + id` — 编码规则不变
4. **价格计算公式**：
   - `quotation_price = 指导价 × (1 + float_rate)`
   - `limit_price = 报价 × (1 + discount_rate)` — 公式和精度(round 2位)不变
5. **订单状态码**：10/20/30/40/50 — 数值不变
6. **所有业务判断条件**：如学生食堂只能看goods_type=0、截止下单时间14:00等 — 逻辑不变
7. **级联更新逻辑**：订单修复→账单更新→退单删除→对账单重算 — 流程不变
8. **SM3签名算法**：`SM3(timestamp + random + private_key)` — 算法不变
9. **默认重置密码**：`Dxdzcg888` — 不变
10. **数据库时间字段**：`add_time`等int类型字段继续用`time()`存取，不改为timestamp

### 7.4 前端改造对照

| 老系统组件 | 新系统组件 | 说明 |
|------------|------------|------|
| LayUI layer.open() | ElDialog / ElDrawer | 弹窗/抽屉 |
| LayUI form | ElForm + FormRequest | 表单 |
| LayUI table | ElTable + SearchTable组件 | 表格 |
| LayUI laydate | ElDatePicker | 日期选择 |
| LayUI laypage | ElPagination | 分页 |
| LayUI upload | 自定义ImageUpload/FileUpload | 上传 |
| Select2 | ElSelect | 下拉选择 |
| Bootstrap栅格 | ElRow / ElCol | 布局 |
| jQuery AJAX | Axios (request.ts封装) | 请求 |
| ECharts | ECharts (vue-echarts) | 图表 |
| Tab页签(b.tabs.js) | ElTabs / KeepAlive | 多标签页 |
| 侧边栏菜单 | ElMenu + 动态路由 | 菜单 |
| PDF.js | vue-pdf-embed | PDF预览 |
| Viewer.js | ElImageViewer | 图片预览 |
| Lodop | window.print() / print-js | 打印 |

### 7.5 Controller → Service → Logic → Repository 分层示例

以**订单商品修复**为例，展示完整分层：

```php
// Controller层：只负责接收参数和返回响应
class OrderController extends BaseController
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function fixOrdersGoods(OrderFixRequest $request): JsonResponse
    {
        $result = $this->orderService->fixOrdersGoods($request->validated());
        return ApiResponse::success($result);
    }
}

// Service层：业务编排，事务管理
class OrderService
{
    public function __construct(
        private OrderLogic $orderLogic,
        private OrderRepository $orderRepo,
        private BackorderRepository $backorderRepo,
        private ReceivableRepository $receivableRepo,
    ) {}

    public function fixOrdersGoods(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. 更新订单商品数量
            $this->orderRepo->updateOrdersGoods($data['orders_goods_id'], [
                'receiveqty' => $data['receiveqty'],
                'settleqty'  => $data['receiveqty'],
            ]);

            // 2. 记录修复日志
            $this->orderRepo->createFixLog($data);

            // 3. 级联更新（Logic层处理核心规则）
            $this->orderLogic->afterFixOrdersGoods($data['order_id']);

            return ['message' => '修复成功'];
        });
    }
}

// Logic层：核心业务规则，不依赖事务
class OrderLogic
{
    public function __construct(
        private ReceiptLogic $receiptLogic,
        private BackorderRepository $backorderRepo,
        private ReceivableRepository $receivableRepo,
    ) {}

    public function afterFixOrdersGoods(int $orderId): void
    {
        // 1. 更新账单明细
        $this->receivableRepo->updateByOrderId($orderId);

        // 2. 删除该订单的退单
        $this->backorderRepo->deleteByOrderId($orderId);

        // 3. 删除退单的账单明细
        $this->receivableRepo->deleteBackorderByOrderId($orderId);

        // 4. 重算对账单
        $this->receiptLogic->recalculateByOrderId($orderId);

        // 5. 更新检测报告状态
        ReceiptLogic::updateInspectionReportStatusByOrderId($orderId);
    }
}

// Repository层：数据访问
class OrderRepository
{
    public function updateOrdersGoods(int $id, array $data): bool
    {
        return OrdersGoods::where('id', $id)->update($data);
    }

    public function createFixLog(array $data): OrdersGoodsFixLog
    {
        return OrdersGoodsFixLog::create([
            'order_id'       => $data['order_id'],
            'orders_goods_id' => $data['orders_goods_id'],
            'receiveqty'     => $data['receiveqty'],
            'supporting_documents_files' => $data['supporting_documents_files'],
        ]);
    }
}
```

### 7.6 JWT认证兼容方案

老系统使用MySQL session表存储会话，新系统改为JWT：

```
登录流程：
1. 用户提交 username + password
2. 后端验证：md5(md5(password) . salt) 与数据库比对（与老系统一致）
3. 验证通过，生成JWT Token，payload包含：user_id, identity_type
4. Token存入Redis，设置过期时间（与老系统session过期时间一致）
5. 返回Token给前端

请求认证：
1. 前端每次请求Header携带：Authorization: Bearer {token}
2. JwtAuth中间件验证Token有效性
3. 检查Redis中Token是否存在（支持主动失效/踢出）
4. 解析用户信息注入Request

Token刷新：
1. Token过期前自动续期
2. 单点登录：同一用户只保留最新Token

兼容老系统API签名：
1. /api/open/* 路由使用SM3签名认证，不走JWT
2. 签名算法与老系统完全一致
```

### 7.7 RBAC权限兼容方案

```
老系统权限模型：
  user.post → post.privilege(逗号分隔menu_id) → system_menu.path

新系统RBAC模型：
  user → user_roles → roles → role_permissions → permissions

兼容方案：
1. 数据迁移时，将post转为role，privilege转为role_permissions
2. 保留老系统system_menu表，用于前端菜单渲染
3. permissions.name = 老系统system_menu.path（如goods.index）
4. RbacPermission中间件检查逻辑：当前用户角色 → 权限列表 → 是否包含请求的权限标识
5. 前端权限指令 v-permission="['goods.add']" 与老系统权限路径一致
```

---

## 八、开发阶段规划

### Phase 1：基础框架搭建

- Laravel项目初始化 + 数据库迁移
- JWT认证 + RBAC权限中间件
- 统一响应/异常/日志
- Vue3项目初始化 + 布局组件 + 路由守卫

### Phase 2：系统管理模块

- 用户/角色/权限/菜单/部门/岗位
- 登录/修改密码/操作日志

### Phase 3：核心业务模块

- 商品/分类/价格网
- 供应商/学校/食堂
- 订单/退货/竞价

### Phase 4：辅助业务模块

- 评价/投诉/应急/审阅
- 应收账单/对账单
- 统计报表/数据分析

### Phase 5：前端Home端 + API端

- 学校/食堂前端全部页面
- 第三方开放API + SM3签名

### Phase 6：定时任务 + 联调测试

- 全部定时任务迁移
- 全量业务回归测试

---

> 本架构方案严格遵循"只升级架构、技术栈、代码规范，业务逻辑100%与老系统保持一致"的原则，所有业务判断条件、数据结果、用户操作均与老系统一一对应，零丢失、零偏差。
