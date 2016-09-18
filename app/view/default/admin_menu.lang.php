<?php

/**
 * 后台菜单结构
 */
return [
    'index' => [
        ['frame|main' => '首页']
    ],
    'system' => [
        ['setting|ls' => '基础设置'],
        ['admin|ls' => '管理员'],
        ['badword|ls' => '词语过滤'],
        ['log|ls' => '系统日志'],
        ['feed|ls' => '事件列表']
    ],
    'app' => [
        ['app|ls' => '数据备份'],
        ['domain|ls' => '域名解析'],
    ],
    'user' => [
        ['user|ls' => '用户管理'],
        ['setting|register' => '注册设置'],
        ['pm|ls' => '短消息管理'],
        ['credit|ls' => '积分兑换'],
    ],
    'plugin' => [
        ['plugin|filecheck' => '文件校验']
    ],
    'tool' => [
        ['db', 'ls'],
        ['cache', 'update'],
    ],
    'queue' => [
        ['mail|ls' => '邮件队列'],
        ['setting|mail' => '邮件设置'],
        ['note|ls' => '通知列表'],
    ],
];
