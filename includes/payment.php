<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */

class Payment {
    
    private $db;
    private $config = [];
    
    public function __construct($db = null) {
        $this->db = $db;
        $this->loadConfig();
    }
    
    // 从数据库加载支付配置
    private function loadConfig() {
        if (!$this->db) {
            if (file_exists(__DIR__ . '/../config.php')) {
                require_once __DIR__ . '/../config.php';
                require_once __DIR__ . '/database.php';
                $this->db = Database::getInstance();
            }
        }
        
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM faka_payment WHERE status = 1 ORDER BY sort ASC");
                $stmt->execute();
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($payments as $payment) {
                    $config = json_decode($payment['config'], true);
                    $this->config[$payment['type']] = [
                        'name' => $payment['name'],
                        'pid' => $config['pid'] ?? '',
                        'key' => $config['key'] ?? '',
                        'gateway' => $config['gateway'] ?? 'https://pay.abgeymw.cn/submit.php',
                        'status' => $payment['status']
                    ];
                }
            } catch (PDOException $e) {
                $this->loadDefaultConfig();
            }
        } else {
            $this->loadDefaultConfig();
        }
    }

    private function loadDefaultConfig() {
        $this->config = [
            'alipay' => [
                'name' => '支付宝',
                'pid' => '',
                'key' => '',
                'gateway' => '',
                'status' => 0
            ],
            'wxpay' => [
                'name' => '微信支付',
                'pid' => '',
                'key' => '',
                'gateway' => '',
                'status' => 0
            ]
        ];
    }
    
    // 检查
    public function isAvailable($payType) {
        if (!isset($this->config[$payType])) {
            return false;
        }
        return $this->config[$payType]['status'] == 1 && 
               !empty($this->config[$payType]['pid']) && 
               !empty($this->config[$payType]['key']);
    }
    
    // 获取
    public function getAvailablePayments() {
        $available = [];
        foreach ($this->config as $type => $conf) {
            if ($conf['status'] == 1 && !empty($conf['pid']) && !empty($conf['key'])) {
                $available[] = [
                    'type' => $type,
                    'name' => $conf['name']
                ];
            }
        }
        return $available;
    }
    
    // 生成签名
    private function generateSign($params, $key) {
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v != "" && $k != "sign" && $k != "sign_type") {
                $filtered[$k] = $v;
            }
        }
        ksort($filtered);
        $signStr = http_build_query($filtered);
        return md5($signStr . $key);
    }
    
    // 验证
    public function verifySign($params, $payType = null) {
        if (!isset($params['sign'])) {
            return false;
        }
        if ($payType && isset($this->config[$payType])) {
            $key = $this->config[$payType]['key'];
            $calcSign = $this->generateSign($params, $key);
            return $params['sign'] === $calcSign;
        }
        foreach ($this->config as $type => $conf) {
            if ($conf['status'] == 1 && !empty($conf['key'])) {
                $calcSign = $this->generateSign($params, $conf['key']);
                if ($params['sign'] === $calcSign) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    // 创建
    public function createOrder($orderNo, $amount, $payType, $productName = '商品购买') {
        if (!$this->isAvailable($payType)) {
            return false;
        }
        
        $config = $this->config[$payType];
        
        $params = [
            'pid' => $config['pid'],
            'type' => $payType,
            'out_trade_no' => $orderNo,
            'notify_url' => SITE_URL . 'api/notify.php',
            'return_url' => SITE_URL . 'api/return.php',
            'name' => $productName,
            'money' => $amount,
            'clientip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        $params['sign'] = $this->generateSign($params, $config['key']);
        $params['sign_type'] = 'MD5';
        
        return $config['gateway'] . '?' . http_build_query($params);
    }
    
    // 获取C
    public function getPaymentConfig($type = null) {
        if ($type) {
            return $this->config[$type] ?? null;
        }
        return $this->config;
    }
    
    // 更新
    public static function updateConfig($db, $type, $data) {
        $stmt = $db->prepare("SELECT id, config FROM faka_payment WHERE type = ?");
        $stmt->execute([$type]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            $config = json_decode($payment['config'], true);
            $config = array_merge($config, $data);
            $configJson = json_encode($config);
            
            $stmt = $db->prepare("UPDATE faka_payment SET config = ? WHERE type = ?");
            return $stmt->execute([$configJson, $type]);
        } else {
            $configJson = json_encode($data);
            $name = $type == 'alipay' ? '支付宝' : '微信支付';
            $stmt = $db->prepare("INSERT INTO faka_payment (name, type, config, status, sort) VALUES (?, ?, ?, 1, ?)");
            return $stmt->execute([$name, $type, $configJson, $type == 'alipay' ? 1 : 2]);
        }
    }
    
    // 切换
    public static function toggleStatus($db, $type, $status) {
        $stmt = $db->prepare("UPDATE faka_payment SET status = ? WHERE type = ?");
        return $stmt->execute([$status, $type]);
    }
}
