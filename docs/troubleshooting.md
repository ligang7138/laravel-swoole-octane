# SXW 项目框架搭建 — 问题与解决方案

本文档记录了 B2B2C 项目框架搭建过程中遇到的所有问题及其解决方案，供团队参考。

---

## 一、环境与依赖问题

### 1.1 宿主机无 Composer，无法创建 Laravel 项目

**现象**：在宿主机执行 `composer create-project laravel/laravel . "12.*" --prefer-dist`，提示 `command not found: composer`。

**原因**：宿主机未安装 PHP 和 Composer，项目要求所有开发调试在容器内完成。

**解决方案**：通过 Docker 官方 Composer 镜像在容器中创建项目：

```bash
docker run --rm -v "$(pwd)":/app composer:latest create-project laravel/laravel /app/tmp-laravel "12.*" --prefer-dist
```

创建完成后将 `tmp-laravel/` 目录内容移动到项目根目录。

---

### 1.2 Dockerfile 中 pecl install redis 安装失败

**现象**：`pecl install redis` 提示 `No releases available for package "pecl.php.net/redis"`。

**原因**：未指定 Redis 扩展版本，PECL 通道索引未更新或版本解析失败。

**解决方案**：

1. 先更新 PECL 通道：`pecl channel-update pecl.php.net`
2. 指定版本安装：`pecl install redis`（当前最新稳定版自动选择）

如果网络不稳定，可改用 GitHub 源码编译：

```dockerfile
RUN git clone --branch 6.2.0 --depth 1 https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis && phpize && ./configure && make -j$(nproc) && make install \
    && docker-php-ext-enable redis && rm -rf /tmp/phpredis
```

---

### 1.3 Dockerfile COPY Composer 镜像失败

**现象**：`COPY --from=composer:latest /usr/bin/composer /usr/bin/composer` 拉取镜像失败，提示 `Service Unavailable`。

**原因**：Docker 镜像源不稳定，无法拉取 `composer:latest` 多阶段构建镜像。

**解决方案**：改用 curl 直接下载 Composer 安装脚本：

```dockerfile
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
```

---

### 1.4 Docker 镜像源无法拉取 php:8.4-cli-alpine

**现象**：`docker build` 时提示 `failed to do request: Head "https://registry.docker-cn.com/v2/library/php/manifests/8.4-cli-alpine": Service Unavailable`。

**原因**：Docker 镜像源（registry.docker-cn.com）不可用。

**解决方案**：

1. 手动拉取镜像：`docker pull php:8.4-cli-alpine`
2. 修改 Docker Desktop 配置，使用可用的镜像加速源（如 `https://docker.m.daocloud.io`）

---

### 1.5 PHP 扩展安装提示 Cannot find autoconf

**现象**：构建过程中 `pecl install` 报错 `Cannot find autoconf`。

**原因**：Dockerfile 中过早执行 `apk del $PHPIZE_DEPS` 清理了构建依赖，导致后续 pecl 安装缺少 autoconf。

**解决方案**：调整 Dockerfile 指令顺序，将所有 PHP 扩展安装完毕后再清理构建依赖：

```dockerfile
# 先安装所有扩展（含 pecl 扩展）
RUN docker-php-ext-install ...
RUN pecl install redis && ...
RUN pecl install swoole && ...

# 最后再清理
RUN apk del $PHPIZE_DEPS
```

---

## 二、数据库问题

### 2.1 MySQL 8.4 不支持 default-authentication-plugin 参数

**现象**：MySQL 容器不断重启，日志报错 `unknown variable 'default-authentication-plugin=caching_sha2_password'`。

**原因**：MySQL 8.4 已移除 `default-authentication-plugin` 参数，该参数在 8.0 中已标记为废弃，8.4 正式删除。

**解决方案**：

1. **my.cnf 修改**：将 `default-authentication-plugin=caching_sha2_password` 替换为 `authentication_policy=caching_sha2_password`
2. **docker-compose.yml 修改**：移除 command 中的 `--default-authentication-plugin=caching_sha2_password`

```ini
# my.cnf（MySQL 8.4+）
[mysqld]
authentication_policy=caching_sha2_password
```

```yaml
# docker-compose.yml
command: >
  --character-set-server=utf8mb4
  --collation-server=utf8mb4_unicode_ci
  --innodb-buffer-pool-size=512M
```

1. **数据卷清理**：如果 MySQL 之前初始化失败导致数据卷损坏，需要删除旧卷重建：

```bash
docker compose down
docker volume rm sxw_sxw-mysql-data
docker compose up -d sxw-mysql
```

---

### 2.2 MySQL 数据卷损坏导致容器循环重启

**现象**：MySQL 容器启动后立即退出并重启，日志提示 `Table 'mysql.component' doesn't exist`、`Could not open the mysql.plugin table`。

**原因**：之前使用了不兼容的配置参数导致 MySQL 初始化失败，数据卷中存在不完整的数据文件。

**解决方案**：删除数据卷后重新创建：

```bash
docker compose down
docker volume rm sxw_sxw-mysql-data
docker compose up -d sxw-mysql
```

---

## 三、网络与 DNS 问题

### 3.1 Alpine 容器内 PHP 无法解析 Docker 服务主机名

**现象**：容器内 `ping sxw-mysql` 可以解析，但 `php -r "echo gethostbyname('sxw-mysql');"` 返回原始主机名，无法解析为 IP。Laravel 应用连接数据库报 `php_network_getaddresses: getaddrinfo for sxw-mysql failed: Name does not resolve`。

**原因**：Alpine Linux 使用 musl libc，其 `getaddrinfo` 实现与 Docker 内置 DNS（127.0.0.11）存在兼容性问题。musl 的 DNS 解析器不读取 `/etc/nsswitch.conf`，且 `gethostbyname` 不走 Docker DNS。

**尝试过的方案**：

| 方案 | 结果 | 原因 |
|------|------|------|
| 写入 `/etc/nsswitch.conf` | 失败 | musl 不读取 nsswitch.conf |
| 安装 `musl-utils` | 失败 | 不影响 PHP 的 DNS 解析行为 |
| 切换到 Debian 基础镜像 | 失败 | Docker Hub 镜像源无法拉取 `php:8.4-cli-bookworm` |
| `sed -i` 修改 `/etc/hosts` | 失败 | Docker 挂载的 `/etc/hosts` 无法被 sed 原地修改（Resource busy） |
| `echo >> /etc/hosts` 追加 | 成功 | 可以追加但不能原地修改 |

**最终解决方案**：通过 entrypoint 启动脚本，在容器启动时用 `ping` 解析 Docker 服务主机名（ping 使用系统 DNS 解析器，可以正确解析 Docker DNS），然后将 IP 写入 `/etc/hosts`：

```bash
#!/bin/sh
add_host() {
    local hostname=$1
    local ip=$(php -r "echo gethostbyname('$hostname');" 2>/dev/null)
    if [ -n "$ip" ] && [ "$ip" != "$hostname" ]; then
        echo "$ip $hostname" >> /etc/hosts
    else
        local ip2=$(ping -c1 -W2 "$hostname" 2>/dev/null | head -1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -n "$ip2" ]; then
            echo "$ip2 $hostname" >> /etc/hosts
        fi
    fi
}

add_host sxw-mysql
add_host sxw-pgsql
add_host sxw-redis
add_host sxw-rocketmq-namesrv
add_host sxw-rocketmq-broker
add_host sxw-kafka

exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

Dockerfile 中使用：

```dockerfile
COPY docker/swoole/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
CMD ["/usr/local/bin/entrypoint.sh"]
```

---

### 3.2 Docker Compose down 后 DNS 解析失败

**现象**：`docker exec sxw-app php artisan migrate` 报 DNS 解析失败，但 `docker compose down && docker compose up -d` 后正常。

**原因**：Docker 网络重建后，容器内的 DNS 缓存可能过期。`docker compose restart` 不会重建网络，而 `down + up` 会。

**解决方案**：遇到 DNS 问题时，优先使用 `docker compose down && docker compose up -d` 重建整个环境。

---

## 四、构建与部署问题

### 4.1 Dockerfile COPY 上下文路径问题

**现象**：`COPY entrypoint.sh /usr/local/bin/entrypoint.sh` 提示 `"/entrypoint.sh": not found`。

**原因**：docker-compose.yml 中 `context: .` 指向项目根目录，而 Dockerfile 在 `docker/swoole/` 子目录中。COPY 的源路径是相对于 build context（项目根目录）的，不是相对于 Dockerfile 所在目录。

**解决方案**：COPY 路径使用相对于项目根目录的路径：

```dockerfile
# 错误
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# 正确
COPY docker/swoole/entrypoint.sh /usr/local/bin/entrypoint.sh
```

---

### 4.2 docker-compose.yml version 属性已废弃

**现象**：`docker compose up` 时警告 `the attribute 'version' is obsolete, it will be ignored`。

**原因**：Docker Compose V2 不再需要 `version` 字段，该字段已被标记为废弃。

**解决方案**：从 docker-compose.yml 中移除 `version: '3.8'` 行即可消除警告（不影响功能）。

---

## 五、问题排查方法论

### 5.1 容器启动失败排查流程

```
1. docker logs <container>           # 查看容器日志
2. docker ps -a                       # 查看容器状态（是否在重启）
3. docker exec <container> <command>  # 进入容器执行诊断命令
4. docker inspect <container>         # 查看容器详细配置
5. docker network inspect <network>   # 检查网络连通性
```

### 5.2 DNS 问题排查流程

```
1. docker exec <app> ping <hostname>           # 系统级 DNS 解析
2. docker exec <app> php -r "echo gethostbyname('<hostname>');"  # PHP 级 DNS 解析
3. docker exec <app> cat /etc/hosts            # 检查 hosts 文件
4. docker exec <app> cat /etc/resolv.conf      # 检查 DNS 配置
5. docker network inspect <network>            # 检查容器是否在同一网络
```

### 5.3 MySQL 启动失败排查流程

```
1. docker logs <mysql-container> 2>&1 | tail -30  # 查看最新日志
2. 搜索关键字：ERROR、Aborting、unknown variable
3. 检查 my.cnf 配置是否与 MySQL 版本兼容
4. 必要时清除数据卷重建：docker volume rm <volume>
```

---

## 六、Swoole 协程与 Octane 兼容性问题

### 6.1 Swoole 协程在 Octane 中导致 408 超时（核心问题）

**现象**：在 Controller 中使用 `go()` + `Channel`、`Coroutine::create` + `WaitGroup`、`go()` + `System::sleep()` + `Atomic` 轮询等方式实现并发处理，接口均返回 HTTP 408 超时，请求无响应。

**原因**：Laravel Octane 默认将 Swoole 的 `enable_coroutine` 设置为 `false`（见 `vendor/laravel/octane/src/Commands/StartSwooleCommand.php` 第 126 行）。当 `enable_coroutine` 为 `false` 时，Swoole Worker 进程中**不会自动创建协程上下文**，所有协程相关操作（`go()`、`Coroutine::create`、`Channel`、`WaitGroup`、`System::sleep()` 等）都无法正常工作，导致请求阻塞直至超时。

**排查过程**：

| 尝试方案 | 结果 | 原因 |
|------|------|------|
| `go()` + `Channel` 收集结果 | 408 超时 | `enable_coroutine => false`，协程无法创建 |
| `Coroutine::create` + `WaitGroup` | 408 超时 | 同上 |
| `go()` + `Atomic` 轮询 | 408 超时 | 同上 |
| `Octane::concurrently()` | 无响应 | 走 `SwooleTaskDispatcher`（taskWaitMulti），Task Worker 进程中 `enable_coroutine` 也为 `false` |
| `Http::pool()` | 可用 | 不依赖协程，使用 Laravel HTTP 客户端并发 |

**解决方案**：在 `config/octane.php` 中添加 Swoole 配置覆盖，启用协程：

```php
// config/octane.php
'swoole' => [
    'options' => [
        'enable_coroutine' => true,
    ],
],
```

原理：Octane 启动 Swoole Server 时，会将 `config('octane.swoole.options')` 与默认配置合并（见 `vendor/laravel/octane/bin/createSwooleServer.php`），覆盖 `enable_coroutine => false` 的默认值。

**启用后验证**：

```bash
# 重启容器使配置生效
docker restart sxw-app

# 测试 Worker Pool 接口
curl http://localhost:8000/customer/orders/worker-pool
# 返回：{"message":"Worker Pool 演示完成","workers":3,"tasks":10,"completed":10,"time":"0.501s",...}
```

**注意事项**：

- 启用 `enable_coroutine` 后，需注意协程间的变量隔离问题，避免协程间共享可变状态
- Octane 常驻内存模式下，协程内修改的全局/静态变量会影响后续请求，需在请求结束时清理
- `enable_coroutine => true` 会影响 Worker 进程的行为，确保代码中不存在阻塞操作（如 `sleep()`、`file_get_contents()`），应使用对应的协程版本（`System::sleep()`、协程 HTTP 客户端等）

---

### 6.2 Swoole `defer` 函数在 Octane 中导致接口卡死

**现象**：使用 Swoole 的 `defer()` 函数在请求结束时重置静态变量 `self::$count = 0`，接口卡死无响应。

**原因**：`defer()` 注册的回调在当前协程结束时执行。在 Octane 环境下，请求处理协程的生命周期由 Octane 管理，`defer()` 的执行时机可能与 Octane 的请求生命周期冲突，导致协程无法正常退出。

**解决方案**：使用 Laravel 的 `app()->terminating()` 方法替代 `defer()`，在应用终止时执行清理操作：

```php
// 错误：defer 在 Octane 中会导致卡死
defer(function () {
    self::$count = 0;
});

// 正确：使用 Laravel 的 terminating 钩子
app()->terminating(function () {
    self::$count = 0;
});
```

---

### 6.3 路由冲突导致 TypeError

**现象**：访问 `GET /customer/orders/worker-pool` 时报错：

```
TypeError: App\Http\Controllers\Customer\OrderController::show(): 
Argument #1 ($id) must be of type int, string given
```

**原因**：路由注册顺序问题。`Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show'])` 会注册 `orders/{id}` 路由，而 `orders/worker-pool` 被 `orders/{id}` 先匹配到，`worker-pool` 字符串被当作 `$id` 参数传入 `show(int $id)` 方法，导致类型错误。

**解决方案**：将自定义路由放在 `apiResource` 之前注册，确保优先匹配：

```php
// 正确顺序：自定义路由在前
Route::get('orders/worker-pool', [OrderController::class, 'workerPoolDemo']);
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);

// 错误顺序：apiResource 在前，orders/worker-pool 会被 orders/{id} 拦截
Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
Route::get('orders/worker-pool', [OrderController::class, 'workerPoolDemo']);
```

---

### 6.4 Nginx 反向代理超时导致 408

**现象**：Swoole 协程请求外部资源耗时较长时，Nginx 返回 HTTP 408 超时。

**原因**：Nginx 默认的代理超时时间较短，未配置足够的超时时间。

**解决方案**：在 Nginx 配置中添加代理超时设置：

```nginx
location / {
    proxy_pass http://sxw-app:8000;
    proxy_connect_timeout 30s;
    proxy_send_timeout 30s;
    proxy_read_timeout 30s;
}
```

---

## 七、Octane 协程最佳实践

### 7.1 Octane 环境下的并发方式选择

| 方式 | 适用场景 | Octane 兼容性 | 说明 |
|------|---------|-------------|------|
| `Coroutine::create` + `WaitGroup` | 本地数据并发处理 | ✅ 需启用 `enable_coroutine` | 最灵活，适合处理 MySQL 查询结果等本地数据 |
| `go()` + `Channel` | 协程间通信、Worker Pool | ✅ 需启用 `enable_coroutine` | 适合生产者-消费者模式 |
| `Octane::concurrently()` | 简单并发任务 | ⚠️ 依赖 Task Worker | 通过 `taskWaitMulti` 投递到 Task Worker 进程 |
| `Http::pool()` | 并发 HTTP 请求 | ✅ 无需额外配置 | 仅适用于 HTTP 请求场景 |

### 7.2 协程使用注意事项

1. **必须启用 `enable_coroutine`**：在 `config/octane.php` 中配置 `'swoole' => ['options' => ['enable_coroutine' => true]]`
2. **避免使用 `defer()`**：Octane 环境下使用 `app()->terminating()` 替代
3. **避免阻塞操作**：不要使用 `sleep()`、`file_get_contents()` 等阻塞函数，使用 `System::sleep()`、协程 HTTP 客户端等协程友好版本
4. **注意变量隔离**：协程间不要共享可变状态，使用 `Channel` 或 `Atomic` 进行安全通信
5. **静态变量清理**：Octane 常驻内存模式下，静态变量在请求间不会自动重置，需在 `app()->terminating()` 中手动清理
