# SXW B2B2C 项目操作手册

本文档是 SXW 项目的完整操作手册，涵盖环境搭建、日常开发、调试、数据库管理等全流程操作。

---

## 一、项目概览

### 1.1 技术栈

| 组件 | 技术选型 | 版本 |
|------|---------|------|
| 运行时 | PHP + Swoole 协程 | PHP 8.4 / Swoole 6.2.1 |
| 框架 | Laravel + Octane | Laravel 12.x / Octane 2.17 |
| 主数据库 | MySQL | 8.4 LTS |
| 分析数据库 | PostgreSQL | 17.x |
| 缓存 | Redis | 7.4.x |
| 消息队列 | RocketMQ / Kafka | 5.3.1 / 7.9.0 (Confluent) |
| Web 服务器 | OpenResty (Nginx) | latest-alpine |
| 容器化 | Docker Compose | V2 |

### 1.2 代码分层架构

```
app/
├── Domain/                          # 领域层 — 业务模型与规则
│   ├── Delivery/                    #   配送域
│   │   ├── Events/                  #     领域事件
│   │   ├── Models/                  #     领域模型
│   │   └── Repositories/           #     仓储接口
│   ├── Merchant/                    #   商户域
│   ├── Order/                       #   订单域
│   ├── Payment/                     #   支付域
│   ├── Product/                     #   商品域
│   └── User/                        #   用户域
├── Services/                        # 服务层 — 业务编排与事务
│   ├── Cart/                        #   购物车服务
│   ├── Delivery/                    #   配送服务
│   ├── Merchant/                    #   商户服务
│   ├── Order/                       #   订单服务
│   ├── Payment/                     #   支付服务
│   └── Product/                     #   商品服务
├── Http/Controllers/                # 控制器层 — 入参校验与响应
│   ├── Customer/                    #   C端（消费者）
│   ├── Merchant/                    #   B端（商户）
│   └── Admin/                       #   平台端（管理员）
├── Infrastructure/                  # 基础设施层 — 外部适配
│   ├── Cache/                       #   缓存服务（Redis）
│   ├── Delivery/                    #   配送网关
│   ├── Lock/                        #   分布式锁
│   ├── Messaging/                   #   消息队列（RocketMQ/Kafka）
│   ├── Payment/                     #   支付网关（支付宝/微信）
│   └── Persistence/Repositories/   #   仓储实现
└── Providers/                       # 服务提供者
    └── RepositoryServiceProvider    #   依赖注入绑定
```

### 1.3 请求流转路径

```
HTTP Request
  → Nginx (OpenResty:80)
    → PHP Swoole (Octane:8000)
      → Controller（入参校验、路由分发）
        → Service（业务编排、事务控制）
          → Domain Model（领域规则、状态流转）
          → Repository（数据访问抽象）
            → Eloquent Model → MySQL / PostgreSQL
          → Infrastructure（缓存、消息、支付、配送）
```

---

## 二、环境搭建

### 2.1 前置要求

- Docker Desktop（macOS）已安装并运行
- 项目代码已克隆到本地
- 宿主机无需安装 PHP/Composer

### 2.2 目录结构

```
sxw/
├── docker/
│   ├── swoole/
│   │   ├── Dockerfile              # PHP+Swoole 应用镜像
│   │   └── entrypoint.sh           # 启动脚本（DNS 解析修复）
│   ├── mysql/
│   │   └── my.cnf                  # MySQL 配置
│   ├── nginx/
│   │   ├── nginx.conf              # Nginx 主配置
│   │   └── conf.d/default.conf     # 站点配置
│   └── rocketmq/
│       └── broker.conf             # RocketMQ Broker 配置
├── docker-compose.yml              # 容器编排
├── .env                            # 环境变量
├── app/                            # 应用代码
├── routes/                         # 路由定义
├── config/                         # 配置文件
├── database/migrations/            # 数据库迁移
└── docs/                           # 项目文档
```

### 2.3 首次搭建步骤

```bash
# 1. 进入项目目录
cd /path/to/sxw

# 2. 构建应用镜像
docker compose build sxw-app

# 3. 启动基础服务（MySQL、PostgreSQL、Redis）
docker compose up -d sxw-mysql sxw-pgsql sxw-redis

# 4. 等待 MySQL 初始化完成（首次启动约 15-30 秒）
sleep 20

# 5. 启动应用和 Nginx
docker compose up -d sxw-app sxw-nginx

# 6. 运行数据库迁移
docker exec sxw-app php artisan migrate

# 7. 验证服务
curl http://localhost/api/health
# 期望输出：{"status":"ok","time":"2026-xx-xx xx:xx:xx"}
```

### 2.4 启动全部服务（含消息队列）

```bash
docker compose up -d
```

这将启动所有 11 个容器：

| 容器名 | 服务 | 端口 |
|--------|------|------|
| sxw-app | PHP Swoole + Octane | 8000 |
| sxw-nginx | OpenResty | 80 |
| sxw-mysql | MySQL 8.4 | 3306 |
| sxw-pgsql | PostgreSQL 17 | 5432 |
| sxw-redis | Redis 7.4 | 6379 |
| sxw-rocketmq-namesrv | RocketMQ NameServer | 9876 |
| sxw-rocketmq-broker | RocketMQ Broker | 10911 |
| sxw-rocketmq-dashboard | RocketMQ 控制台 | 8180 |
| sxw-zookeeper | Zookeeper | 2181 |
| sxw-kafka | Kafka | 9092 |
| sxw-kafka-ui | Kafka 控制台 | 8280 |

---

## 三、日常开发操作

### 3.1 服务管理

```bash
# 启动所有服务
docker compose up -d

# 启动指定服务
docker compose up -d sxw-app sxw-nginx sxw-mysql sxw-redis

# 停止所有服务
docker compose down

# 停止但保留数据卷
docker compose stop

# 重启单个服务
docker compose restart sxw-app

# 查看服务状态
docker compose ps

# 查看服务日志
docker compose logs sxw-app          # 应用日志
docker compose logs sxw-mysql        # MySQL 日志
docker compose logs -f sxw-app       # 实时跟踪日志
```

### 3.2 在容器内执行命令

所有 PHP/Composer/Artisan 命令均在容器内执行：

```bash
# 执行 Artisan 命令
docker exec sxw-app php artisan <command>

# 执行 Composer 命令
docker exec sxw-app composer <command>

# 进入容器交互式 Shell
docker exec -it sxw-app bash

# 在容器内运行 PHP 脚本
docker exec sxw-app php -r "echo 'hello';"
```

### 3.3 代码修改与热更新

项目通过 Docker Volume 挂载（`./:/var/www/html:rw`），宿主机修改代码后容器内实时生效。

由于使用 Octane（常驻内存），部分修改需要重启 Worker：

```bash
# 方式一：重启应用容器
docker compose restart sxw-app

# 方式二：在容器内重启 Octane
docker exec sxw-app php artisan octane:reload
```

**需要注意的修改类型**：

| 修改类型 | 是否需要重启 |
|---------|------------|
| Controller / Service / Repository 代码 | 否（Octane 自动检测） |
| 配置文件（config/） | 是 |
| 路由文件（routes/） | 是 |
| .env 文件 | 是 |
| Provider 注册 | 是 |
| 新增 Composer 依赖 | 是（需重新安装） |

### 3.4 安装 Composer 依赖

```bash
# 安装新依赖
docker exec sxw-app composer require <package>

# 安装开发依赖
docker exec sxw-app composer require <package> --dev

# 更新所有依赖
docker exec sxw-app composer update

# 重新安装（从 lock 文件）
docker exec sxw-app composer install
```

---

## 四、数据库操作

### 4.1 数据库连接信息

| 数据库 | 主机 | 端口 | 库名 | 用户名 | 密码 |
|--------|------|------|------|--------|------|
| MySQL | localhost | 3306 | sxw | sxw | sxw123 |
| PostgreSQL | localhost | 5432 | sxw_analytics | sxw | sxw123 |

> 容器内连接使用主机名 `sxw-mysql` / `sxw-pgsql`，宿主机连接使用 `localhost`。

### 4.2 迁移操作

```bash
# 运行所有未执行的迁移
docker exec sxw-app php artisan migrate

# 回滚上一次迁移
docker exec sxw-app php artisan migrate:rollback

# 回滚指定步数
docker exec sxw-app php artisan migrate:rollback --step=3

# 重新运行所有迁移（先回滚再执行）
docker exec sxw-app php artisan migrate:fresh

# 查看迁移状态
docker exec sxw-app php artisan migrate:status
```

### 4.3 创建新迁移文件

```bash
# 创建迁移
docker exec sxw-app php artisan make:migration create_xxx_table

# 指定表名
docker exec sxw-app php artisan make:migration add_yyy_to_orders_table --table=orders
```

### 4.4 数据库表结构

当前业务表（B2B2C）：

| 表名 | 说明 |
|------|------|
| merchants | 商户 |
| shops | 店铺 |
| categories | 商品分类 |
| products | 商品 |
| skus | SKU |
| customers | 客户 |
| addresses | 收货地址 |
| orders | 订单 |
| order_items | 订单明细 |
| payments | 支付记录 |
| deliveries | 配送记录 |

### 4.5 直接连接数据库

```bash
# MySQL
docker exec -it sxw-mysql mysql -usxw -psxw123 sxw

# MySQL（root）
docker exec -it sxw-mysql mysql -uroot -proot123 sxw

# PostgreSQL
docker exec -it sxw-pgsql psql -Usxw -d sxw_analytics
```

---

## 五、缓存操作

### 5.1 Redis 连接

```bash
# 通过容器连接
docker exec -it sxw-redis redis-cli -a sxw123

# 常用 Redis 命令
127.0.0.1:6379> KEYS *           # 查看所有键
127.0.0.1:6379> GET key_name     # 获取值
127.0.0.1:6379> FLUSHDB          # 清空当前数据库
127.0.0.1:6379> INFO             # 查看服务器信息
```

### 5.2 Laravel 缓存操作

```bash
# 清除所有缓存
docker exec sxw-app php artisan cache:clear

# 清除配置缓存
docker exec sxw-app php artisan config:clear

# 清除路由缓存
docker exec sxw-app php artisan route:clear

# 重建配置缓存
docker exec sxw-app php artisan config:cache
```

---

## 六、消息队列操作

### 6.1 RocketMQ

```bash
# RocketMQ Dashboard
# 浏览器访问：http://localhost:8180

# 在容器内发送测试消息
docker exec sxw-rocketmq-broker sh mqadmin sendMsg -t TestTopic -p "hello" -n sxw-rocketmq-namesrv:9876
```

### 6.2 Kafka

```bash
# Kafka UI
# 浏览器访问：http://localhost:8280

# 创建 Topic
docker exec sxw-kafka kafka-topics --create --topic test-topic --bootstrap-server localhost:9092 --partitions 3 --replication-factor 1

# 查看所有 Topic
docker exec sxw-kafka kafka-topics --list --bootstrap-server localhost:9092

# 发送消息
docker exec -it sxw-kafka kafka-console-producer --topic test-topic --bootstrap-server localhost:9092

# 消费消息
docker exec -it sxw-kafka kafka-console-consumer --topic test-topic --from-beginning --bootstrap-server localhost:9092
```

---

## 七、API 路由

### 7.1 公共接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/health | 健康检查 |

### 7.2 C端 — 消费者路由（/api/customer/*）

需 `auth:sanctum` 中间件认证。

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/customer/orders | 订单列表 |
| POST | /api/customer/orders | 创建订单 |
| GET | /api/customer/orders/{id} | 订单详情 |
| POST | /api/customer/orders/{id}/cancel | 取消订单 |
| POST | /api/customer/payments | 发起支付 |
| POST | /api/customer/payments/callback/{channel} | 支付回调（免认证） |
| GET | /api/customer/products/{id} | 商品详情 |
| GET | /api/customer/shops/{shopId}/products | 店铺商品列表 |
| GET | /api/customer/cart | 购物车列表 |
| POST | /api/customer/cart/add | 添加到购物车 |
| PUT | /api/customer/cart/update | 更新购物车 |
| DELETE | /api/customer/cart/remove | 移除购物车项 |
| DELETE | /api/customer/cart/clear | 清空购物车 |

### 7.3 B端 — 商户路由（/api/merchant/*）

需 `auth:sanctum` 中间件认证。

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/merchant/orders | 商户订单列表 |
| GET | /api/merchant/orders/{id} | 商户订单详情 |
| GET | /api/merchant/products | 商品列表 |
| POST | /api/merchant/products | 创建商品 |
| GET | /api/merchant/products/{id} | 商品详情 |
| PUT | /api/merchant/products/{id} | 更新商品 |
| PUT | /api/merchant/products/{id}/status | 更新商品状态 |

### 7.4 平台端 — 管理路由（/api/admin/*）

需 `auth:sanctum` 中间件认证。

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/admin/merchants | 商户列表 |
| POST | /api/admin/merchants | 创建商户 |
| GET | /api/admin/merchants/{id} | 商户详情 |
| POST | /api/admin/merchants/{id}/activate | 激活商户 |
| POST | /api/admin/merchants/{id}/suspend | 停用商户 |
| GET | /api/admin/orders/{id} | 订单详情 |

---

## 八、调试与排查

### 8.1 查看日志

```bash
# Laravel 应用日志
docker exec sxw-app cat storage/logs/laravel.log | tail -50

# Swoole 运行日志
docker exec sxw-app cat storage/logs/swoole_http.log | tail -50

# Nginx 访问日志
docker exec sxw-nginx cat /usr/local/openresty/nginx/logs/access.log | tail -50

# Nginx 错误日志
docker exec sxw-nginx cat /usr/local/openresty/nginx/logs/error.log | tail -50

# MySQL 慢查询日志
docker exec sxw-mysql cat /var/log/mysql/slow.log | tail -50
```

### 8.2 Tinker 交互式调试

```bash
docker exec -it sxw-app php artisan tinker
```

示例：

```php
# 查询商户
>>> App\Domain\Merchant\Models\Merchant::all();

# 创建商户
>>> App\Domain\Merchant\Models\Merchant::create(['name' => '测试商户', 'contact_name' => '张三', 'contact_phone' => '13800138000']);

# 查询订单
>>> App\Domain\Order\Models\Order::with('items')->first();
```

### 8.3 常见问题排查

**问题：容器内无法连接数据库**

```bash
# 1. 检查数据库容器是否运行
docker ps --filter name=sxw-mysql

# 2. 检查 DNS 解析
docker exec sxw-app php -r "echo gethostbyname('sxw-mysql');"

# 3. 检查 /etc/hosts
docker exec sxw-app cat /etc/hosts

# 4. 如果 DNS 解析失败，重启容器
docker compose down && docker compose up -d
```

**问题：Octane Worker 异常**

```bash
# 查看应用日志
docker exec sxw-app tail -100 storage/logs/laravel.log

# 重启 Octane
docker compose restart sxw-app
```

**问题：代码修改未生效**

```bash
# 清除所有缓存
docker exec sxw-app php artisan optimize:clear

# 重启 Octane Worker
docker exec sxw-app php artisan octane:reload
```

---

## 九、重建与清理

### 9.1 重建应用镜像

修改 Dockerfile 后需要重新构建：

```bash
docker compose build sxw-app
docker compose up -d sxw-app
```

### 9.2 清理并重建所有数据

```bash
# 停止所有容器
docker compose down

# 删除所有数据卷（会清除所有数据库数据！）
docker volume rm sxw_sxw-mysql-data sxw_sxw-pgsql-data sxw_sxw-redis-data sxw_sxw-zookeeper-data sxw_sxw-kafka-data

# 重新启动
docker compose up -d

# 运行迁移
docker exec sxw-app php artisan migrate
```

### 9.3 仅清理 MySQL 数据卷

```bash
docker compose down
docker volume rm sxw_sxw-mysql-data
docker compose up -d sxw-mysql sxw-pgsql sxw-redis
sleep 20
docker compose up -d sxw-app sxw-nginx
docker exec sxw-app php artisan migrate
```

---

## 十、管理控制台访问

| 服务 | URL | 说明 |
|------|-----|------|
| API 健康检查 | http://localhost/api/health | 应用状态检查 |
| RocketMQ Dashboard | http://localhost:8180 | 消息队列管理 |
| Kafka UI | http://localhost:8280 | Kafka 管理界面 |

---

## 十一、配置文件说明

| 文件 | 说明 |
|------|------|
| `.env` | 环境变量（数据库、缓存、队列连接信息） |
| `config/database.php` | 数据库连接配置（MySQL + PostgreSQL） |
| `config/octane.php` | Octane 运行配置 |
| `config/mq.php` | 消息队列配置（RocketMQ/Kafka） |
| `config/payment.php` | 支付渠道配置 |
| `docker/mysql/my.cnf` | MySQL 自定义配置 |
| `docker/nginx/conf.d/default.conf` | Nginx 站点配置 |
| `docker/rocketmq/broker.conf` | RocketMQ Broker 配置 |

---

## 十二、扩展开发指南

### 12.1 新增业务模块

以新增「优惠券」模块为例：

```
1. 创建领域模型
   app/Domain/Coupon/Models/Coupon.php
   app/Domain/Coupon/Repositories/CouponRepositoryInterface.php
   app/Domain/Coupon/Events/CouponUsed.php

2. 创建仓储实现
   app/Infrastructure/Persistence/Repositories/CouponRepository.php

3. 创建服务
   app/Services/Coupon/CouponService.php

4. 创建控制器
   app/Http/Controllers/Customer/CouponController.php

5. 注册依赖注入
   在 RepositoryServiceProvider 中绑定接口与实现

6. 创建迁移
   docker exec sxw-app php artisan make:migration create_coupons_table

7. 定义路由
   在 routes/customer.php 中添加路由
```

### 12.2 新增支付渠道

1. 实现 `App\Infrastructure\Payment\Contracts\PaymentGatewayInterface` 接口
2. 在 `config/payment.php` 中注册新渠道
3. 在 `PaymentService` 中添加渠道分发逻辑

### 12.3 新增消息消费者

1. 实现 `App\Infrastructure\Messaging\Contracts\MessageConsumerInterface` 接口
2. 在 `config/mq.php` 中配置 Topic 和 Consumer Group
3. 注册为 Artisan Command 或 Supervisor 进程
