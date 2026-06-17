-- 轻量级简约H5发卡网
-- 
-- @author 沐白
-- @link https://github.com/mubai0122/faka
-- @license MIT
-- 发卡网数据库表结构

-- 管理员表
CREATE TABLE IF NOT EXISTS `faka_admin` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码',
    `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
    `role` VARCHAR(20) DEFAULT 'admin' COMMENT '角色',
    `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
    `create_time` DATETIME NOT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- 商品分类表
CREATE TABLE IF NOT EXISTS `faka_category` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `sort` INT(11) DEFAULT 0 COMMENT '排序',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态 0禁用 1启用',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品分类表';

-- 商品表
CREATE TABLE IF NOT EXISTS `faka_product` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL COMMENT '分类ID',
    `name` VARCHAR(200) NOT NULL COMMENT '商品名称',
    `description` TEXT COMMENT '商品简介',
    `detail` TEXT COMMENT '商品详情',
    `image` VARCHAR(255) DEFAULT NULL COMMENT '商品图片',
    `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
    `stock` INT(11) NOT NULL DEFAULT '0' COMMENT '库存数量',
    `sales` INT(11) NOT NULL DEFAULT '0' COMMENT '销量',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态 0下架 1上架',
    `create_time` DATETIME NOT NULL COMMENT '创建时间',
    `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';

-- 卡密库存表
CREATE TABLE IF NOT EXISTS `faka_stock` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) NOT NULL COMMENT '商品ID',
    `card_info` TEXT NOT NULL COMMENT '卡密内容',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态 0未售 1已售',
    `order_id` INT(11) DEFAULT NULL COMMENT '订单ID',
    `create_time` DATETIME NOT NULL COMMENT '创建时间',
    `sell_time` DATETIME DEFAULT NULL COMMENT '售出时间',
    PRIMARY KEY (`id`),
    KEY `idx_product` (`product_id`),
    KEY `idx_status` (`status`),
    KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卡密库存表';

-- 订单表
CREATE TABLE IF NOT EXISTS `faka_order` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_no` VARCHAR(32) NOT NULL COMMENT '订单号',
    `product_id` INT(11) NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(200) NOT NULL COMMENT '商品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '购买价格',
    `buyer_name` VARCHAR(100) DEFAULT NULL COMMENT '购买者姓名',
    `buyer_email` VARCHAR(100) DEFAULT NULL COMMENT '购买者邮箱',
    `buyer_qq` VARCHAR(20) DEFAULT NULL COMMENT '购买者QQ',
    `card_info` TEXT COMMENT '卡密信息',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态 0待支付 1已支付',
    `pay_type` VARCHAR(20) DEFAULT NULL COMMENT '支付方式',
    `trade_no` VARCHAR(100) DEFAULT NULL COMMENT '支付平台交易号',
    `create_time` DATETIME NOT NULL COMMENT '创建时间',
    `pay_time` DATETIME DEFAULT NULL COMMENT '支付时间',
    `ip_address` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_product` (`product_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表';

-- 支付配置表
CREATE TABLE IF NOT EXISTS `faka_payment` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL COMMENT '支付名称',
    `type` VARCHAR(30) NOT NULL COMMENT '支付类型 alipay/wxpay',
    `config` TEXT COMMENT '支付配置JSON',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态 0禁用 1启用',
    `sort` INT(11) DEFAULT 0 COMMENT '排序',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付配置表';

-- 网站设置表
CREATE TABLE IF NOT EXISTS `faka_setting` (
    `key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `value` TEXT COMMENT '配置值',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '描述',
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站设置表';

-- 插入默认数据
INSERT INTO `faka_setting` (`key`, `value`, `description`) VALUES
('site_name', '个人发卡网', '网站名称'),
('site_keywords', '发卡网,自动发卡', '网站关键词'),
('site_description', '专业的自动发卡平台', '网站描述'),
('kf_wechat', '', '微信'),
('kf_qq', '', 'QQ'),
('kf_workwx', '', '企业微信'),
('kf_dingtalk', '', '钉钉'),
('kf_feishu', '', '飞书'),
('kf_telegram', '', 'Telegram'),
('kf_whatsapp', '', 'WhatsApp'),
('kf_twitter', '', 'Twitter/X'),
('kf_instagram', '', 'Instagram'),
('kf_facebook', '', 'Facebook'),
('kf_phone', '', '电话'),
('kf_email', '', '邮箱'),
('notice', '欢迎光临本站，请放心购买！', '网站公告');

INSERT INTO `faka_payment` (`name`, `type`, `config`, `status`, `sort`) VALUES
('支付宝', 'alipay', '{"pid":"","key":"","gateway":"https:\\/\\/pay.liohg.top\\/submit.php"}', 0, 1),
('微信支付', 'wxpay', '{"pid":"","key":"","gateway":"https:\\/\\/pay.liohg.top\\/submit.php"}', 0, 2);
