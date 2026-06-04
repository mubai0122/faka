<?php
/**
 * 轻量级简约H5发卡网
 * 
 * @author 沐白
 * @link https://github.com/mubai0122/faka
 * @license MIT
 */

session_start();

if (file_exists(__DIR__ . '/install.lock')) {
    header('Location: index.php');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int)$_POST['step'];
    
    if ($step === 1) {
        $_SESSION['install'] = [
            'db_host' => trim($_POST['db_host'] ?? 'localhost'),
            'db_port' => trim($_POST['db_port'] ?? '3306'),
            'db_user' => trim($_POST['db_user'] ?? ''),
            'db_pass' => trim($_POST['db_pass'] ?? ''),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'install_mode' => $_POST['install_mode'] ?? 'new',
        ];
        header('Location: install.php?step=2');
        exit;
        
    } elseif ($step === 2) {
        $_SESSION['install']['admin_user'] = trim($_POST['admin_user'] ?? '');
        $_SESSION['install']['admin_pass'] = trim($_POST['admin_pass'] ?? '');
        $_SESSION['install']['admin_email'] = trim($_POST['admin_email'] ?? '');
        header('Location: install.php?step=3');
        exit;
        
    } elseif ($step === 3) {
        $_SESSION['install']['alipay_pid'] = trim($_POST['alipay_pid'] ?? '');
        $_SESSION['install']['alipay_key'] = trim($_POST['alipay_key'] ?? '');
        $_SESSION['install']['alipay_gateway'] = trim($_POST['alipay_gateway'] ?? 'https://example.com');
        $_SESSION['install']['alipay_status'] = isset($_POST['alipay_status']) ? 1 : 0;
        $_SESSION['install']['wxpay_pid'] = trim($_POST['wxpay_pid'] ?? '');
        $_SESSION['install']['wxpay_key'] = trim($_POST['wxpay_key'] ?? '');
        $_SESSION['install']['wxpay_gateway'] = trim($_POST['wxpay_gateway'] ?? 'https://example.com');
        $_SESSION['install']['wxpay_status'] = isset($_POST['wxpay_status']) ? 1 : 0;
        header('Location: install.php?step=4');
        exit;
        
    } elseif ($step === 4) {
        $data = $_SESSION['install'];
        
        if (empty($data['db_user']) || empty($data['db_name']) || empty($data['admin_user']) || empty($data['admin_pass']) || empty($data['admin_email'])) {
            $error = '请填写所有必填项';
        } elseif (strlen($data['admin_pass']) < 6) {
            $error = '管理员密码至少需要6位';
        } elseif (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $error = '邮箱格式不正确';
        } else {
            try {
                $pdo = new PDO("mysql:host={$data['db_host']};port={$data['db_port']}", $data['db_user'], $data['db_pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$data['db_name']}`");
                
                $sqlFile = __DIR__ . '/install.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception('install.sql 文件不存在');
                }
                
                $sql = file_get_contents($sqlFile);
                if (substr($sql, 0, 3) == "\xEF\xBB\xBF") {
                    $sql = substr($sql, 3);
                }
                
                if ($data['install_mode'] === 'override') {
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        if (strpos($table, 'faka_') === 0) {
                            $pdo->exec("DROP TABLE IF EXISTS `$table`");
                        }
                    }
                }
                
                $pdo->exec($sql);
                
                $alipay_config = json_encode(['pid' => $data['alipay_pid'], 'key' => $data['alipay_key'], 'gateway' => $data['alipay_gateway']]);
                $pdo->prepare("UPDATE faka_payment SET config = ?, status = ? WHERE type = 'alipay'")->execute([$alipay_config, $data['alipay_status']]);
                
                $wxpay_config = json_encode(['pid' => $data['wxpay_pid'], 'key' => $data['wxpay_key'], 'gateway' => $data['wxpay_gateway']]);
                $pdo->prepare("UPDATE faka_payment SET config = ?, status = ? WHERE type = 'wxpay'")->execute([$wxpay_config, $data['wxpay_status']]);
                
                $hashedPass = password_hash($data['admin_pass'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM faka_admin WHERE username = ?");
                $stmt->execute([$data['admin_user']]);
                
                if ($stmt->fetchColumn()) {
                    $pdo->prepare("UPDATE faka_admin SET password = ?, email = ? WHERE username = ?")->execute([$hashedPass, $data['admin_email'], $data['admin_user']]);
                } else {
                    $pdo->prepare("INSERT INTO faka_admin (username, password, email, role, create_time) VALUES (?, ?, ?, 'admin', NOW())")->execute([$data['admin_user'], $hashedPass, $data['admin_email']]);
                }
                
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $scriptDir . '/';
                
                $config = "<?php\n\n";
                $config .= "define('DB_HOST', '{$data['db_host']}');\n";
                $config .= "define('DB_PORT', '{$data['db_port']}');\n";
                $config .= "define('DB_USER', '{$data['db_user']}');\n";
                $config .= "define('DB_PASS', '{$data['db_pass']}');\n";
                $config .= "define('DB_NAME', '{$data['db_name']}');\n\n";
                $config .= "define('SITE_URL', '{$siteUrl}');\n";
                $config .= "define('SITE_NAME', '个人发卡网');\n\n";
                $config .= "date_default_timezone_set('Asia/Shanghai');\n\n";
                $config .= "error_reporting(0);\n";
                $config .= "ini_set('display_errors', 0);\n\n";
                $config .= "if (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n";
                
                file_put_contents(__DIR__ . '/config.php', $config);
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $uploadDir = __DIR__ . '/logs';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                file_put_contents(__DIR__ . '/install.lock', date('Y-m-d H:i:s'));
                
                session_destroy();
                $success = '安装成功！';
                echo '<meta http-equiv="refresh" content="2;url=index.php">';
                
            } catch (PDOException $e) {
                $error = '数据库错误: ' . $e->getMessage();
            } catch (Exception $e) {
                $error = '安装失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发卡网安装程序</title>
    <link rel="stylesheet" href="/assets/css/install.css">
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="install-container">
        <div class="header">
            <div class="header-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M4 4h16v16H4z"/><path d="M9 9h6"/><path d="M9 13h6"/><path d="M9 17h4"/>
                </svg>
            </div>
            <h1>发卡网安装程序</h1>
            <p>简单三步，快速搭建您的自动发卡平台</p>
        </div>

        <div class="steps">
            <div class="step-item <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step == 1 ? 'active' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">数据库配置</div>
            </div>
            <div class="step-item <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step == 2 ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">管理员账户</div>
            </div>
            <div class="step-item <?php echo $step >= 4 ? 'completed' : ''; ?> <?php echo $step == 3 ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">支付配置</div>
            </div>
            <div class="step-item <?php echo $step == 4 ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-label">开始安装</div>
            </div>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <?php if ($step == 1): ?>
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    <h3>数据库配置</h3>
                    <div class="row-2">
                        <div class="form-group">
                            <label>数据库地址 <span class="required">*</span></label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>端口 <span class="required">*</span></label>
                            <input type="text" name="db_port" value="3306" required>
                        </div>
                    </div>
                    <div class="row-2">
                        <div class="form-group">
                            <label>数据库用户名 <span class="required">*</span></label>
                            <input type="text" name="db_user" required>
                        </div>
                        <div class="form-group">
                            <label>数据库密码</label>
                            <input type="password" name="db_pass">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>数据库名称 <span class="required">*</span></label>
                        <input type="text" name="db_name" required>
                    </div>
                    <div class="form-group">
                        <label>安装模式</label>
                        <div class="radio-group">
                            <label class="radio-option"><input type="radio" name="install_mode" value="new" checked> 全新安装</label>
                            <label class="radio-option"><input type="radio" name="install_mode" value="skip"> 跳过已存在表</label>
                            <label class="radio-option"><input type="radio" name="install_mode" value="override"> 覆盖已有表</label>
                        </div>
                    </div>
                    <button type="submit" class="btn">下一步</button>
                </form>
                
                <?php elseif ($step == 2): ?>
                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    <h3>管理员账户</h3>
                    <div class="form-group">
                        <label>管理员用户名 <span class="required">*</span></label>
                        <input type="text" name="admin_user" required>
                    </div>
                    <div class="form-group">
                        <label>登录密码 <span class="required">*</span></label>
                        <input type="password" name="admin_pass" placeholder="至少6位" required>
                    </div>
                    <div class="form-group">
                        <label>管理员邮箱 <span class="required">*</span></label>
                        <input type="email" name="admin_email" required>
                    </div>
                    <button type="submit" class="btn">下一步</button>
                </form>
                
                <?php elseif ($step == 3): ?>
                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    <h3>支付配置</h3>
                    <p class="form-hint">填写易支付平台信息，可留空后续在后台修改</p>
                    <div class="payment-section">
                        <div class="payment-title">支付宝支付</div>
                        <div class="form-group"><label>商户ID</label><input type="text" name="alipay_pid"></div>
                        <div class="form-group"><label>商户密钥</label><input type="text" name="alipay_key"></div>
                        <div class="form-group"><label>支付网关</label><input type="text" name="alipay_gateway" value="https://example.com"></div>
                        <div class="checkbox-group"><input type="checkbox" name="alipay_status" value="1" checked> 启用支付宝支付</div>
                    </div>
                    <div class="payment-section">
                        <div class="payment-title">微信支付</div>
                        <div class="form-group"><label>商户ID</label><input type="text" name="wxpay_pid"></div>
                        <div class="form-group"><label>商户密钥</label><input type="text" name="wxpay_key"></div>
                        <div class="form-group"><label>支付网关</label><input type="text" name="wxpay_gateway" value="https://example.com"></div>
                        <div class="checkbox-group"><input type="checkbox" name="wxpay_status" value="1" checked> 启用微信支付</div>
                    </div>
                    <button type="submit" class="btn">下一步</button>
                </form>
                
                <?php elseif ($step == 4): ?>
                <form method="POST" onsubmit="return confirm('确认开始安装吗喵？')">
                    <input type="hidden" name="step" value="4">
                    <div class="info-box">
                        <h4>安装信息确认</h4>
                        <ul>
                            <li>数据库：<?php echo htmlspecialchars($_SESSION['install']['db_name'] ?? ''); ?></li>
                            <li>管理员：<?php echo htmlspecialchars($_SESSION['install']['admin_user'] ?? ''); ?></li>
                            <li>邮箱：<?php echo htmlspecialchars($_SESSION['install']['admin_email'] ?? ''); ?></li>
                            <li>安装模式：<?php 
                                $mode = $_SESSION['install']['install_mode'] ?? 'new';
                                echo $mode === 'new' ? '全新安装' : ($mode === 'skip' ? '跳过已存在表' : '覆盖已有表');
                            ?></li>
                        </ul>
                        <?php if (($_SESSION['install']['install_mode'] ?? 'new') === 'override'): ?>
                        <div class="warning-box">警告：覆盖模式将删除所有已有数据，请确认已备份！</div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn">立即安装</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>发卡网安装程序</p>
        </div>
    </div>
</body>
</html>
