<?php

return [
    'default' => env('MQ_DEFAULT', 'rocketmq'),

    'rocketmq' => [
        'endpoint' => env('ROCKETMQ_ENDPOINT', 'http://sxw-rocketmq-proxy:8080'),
        'access_key' => env('ROCKETMQ_ACCESS_KEY', ''),
        'secret_key' => env('ROCKETMQ_SECRET_KEY', ''),
        'namespace' => env('ROCKETMQ_NAMESPACE', ''),
        'cluster' => env('ROCKETMQ_CLUSTER', 'DefaultCluster'),
    ],

    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'sxw-kafka:9092'),
        'group_id' => env('KAFKA_GROUP_ID', 'sxw-group'),
        'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),
    ],
];
