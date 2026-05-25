#!/bin/sh
# 解决 Alpine musl DNS 兼容问题
# 通过重试解析 Docker 服务主机名，追加到 /etc/hosts

add_host() {
    local hostname=$1
    local max_retries=${2:-3}
    local retry=0
    local ip=""

    while [ $retry -lt $max_retries ]; do
        # 优先用 PHP 解析
        ip=$(php -r "echo gethostbyname('$hostname');" 2>/dev/null)
        if [ -n "$ip" ] && [ "$ip" != "$hostname" ]; then
            echo "$ip $hostname" >> /etc/hosts
            echo "Resolved $hostname -> $ip"
            return 0
        fi

        # 备用：ping 解析
        ip=$(ping -c1 -W2 "$hostname" 2>/dev/null | head -1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -n "$ip" ]; then
            echo "$ip $hostname" >> /etc/hosts
            echo "Resolved $hostname -> $ip (via ping, retry $retry)"
            return 0
        fi

        retry=$((retry + 1))
        if [ $retry -lt $max_retries ]; then
            echo "Waiting for $hostname... (retry $retry/$max_retries)"
            sleep 2
        fi
    done

    echo "Warning: Could not resolve $hostname after $max_retries retries"
}

# 解析核心服务（必须成功）
add_host sxw-mysql 5
add_host sxw-pgsql 3
add_host sxw-redis 3

# 解析消息队列服务（允许失败，可能未启动）
add_host sxw-rocketmq-namesrv 3
add_host sxw-rocketmq-broker 3
add_host sxw-kafka 3

# 启动 Octane
exec php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
