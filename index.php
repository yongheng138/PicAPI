<?php
// =================================================================================
// 配置部分 - 错误处理和日志设置
// =================================================================================

/**
 * 设置错误显示和日志记录
 * display_errors = 0: 不在页面显示错误（生产环境安全设置）
 * log_errors = 1: 将错误记录到日志文件
 */
ini_set('display_errors', 0);
ini_set('log_errors', 0);

/**
 * 记录API访问日志，便于调试和监控
 * $_SERVER['REQUEST_URI'] 包含完整的请求路径
 */
error_log('随机图片API访问：' . $_SERVER['REQUEST_URI']);

// =================================================================================
// 配置部分 - 图片资源设置
// =================================================================================

/**
 * 设置图片数量变量
 * 这些数字应该与实际图片文件数量匹配
 * 例如：如果有127张横图，则horizontalImageCount = 127
 */
$horizontalImageCount = 175; // 横图数量，根据实际情况修改
$verticalImageCount = 445;   // 竖图数量，根据实际情况修改

// =================================================================================
// 路由部分 - 处理URL路径
// =================================================================================

/**
 * 获取请求路径的两种方式：
 * 1. 通过GET参数path获取（用于调试或特定路由）
 * 2. 通过解析REQUEST_URI获取（标准的URL路径）
 */
$path = isset($_GET['path']) ? $_GET['path'] : '';

/**
 * 如果没有通过GET参数指定path，则从URL路径中解析
 * trim()去除首尾斜杠，parse_url()提取路径部分
 */
if (empty($path)) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

/**
 * 记录处理的路径，便于调试
 */
error_log("处理路径: " . $path);

// =================================================================================
// 路由分发 - 根据路径执行不同操作
// =================================================================================

/**
 * 使用switch语句根据路径分发请求
 * case 'h': 处理横图请求
 * case 'v': 处理竖图请求  
 * case 'a': 自动检测设备类型并返回相应图片
 * default:  显示API说明页面
 */
switch ($path) {
    case 'h':
        // 调用函数返回随机横图
        serveRandomImage('h', $horizontalImageCount);
        break;
        
    case 'v':
        // 调用函数返回随机竖图
        serveRandomImage('v', $verticalImageCount);
        break;
        
    case 'a':
        // 自动检测设备类型并返回相应图片
        serveAutoDetectImage();
        break;
        
    default:
        // 显示API使用说明页面
        showApiDocumentation();
        break;
}

// =================================================================================
// 功能函数 - 检测设备类型
// =================================================================================

/**
 * 检测访问设备类型
 * 
 * @return string 'mobile' 表示移动设备, 'desktop' 表示桌面设备
 */
function detectDeviceType() {
    // 获取用户代理字符串
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    /**
     * 移动设备的用户代理标识符
     * 包括常见的移动设备关键词
     */
    $mobileKeywords = [
        'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry',
        'Windows Phone', 'Opera Mini', 'IEMobile', 'Mobile Safari'
    ];
    
    // 检查用户代理中是否包含移动设备关键词
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            error_log("检测到移动设备访问: " . $keyword);
            return 'mobile';
        }
    }
    
    /**
     * 如果没有找到移动设备标识，则认为是桌面设备
     * 这是简化处理，实际场景可能需要更复杂的检测逻辑
     */
    error_log("检测到桌面设备访问");
    return 'desktop';
}

// =================================================================================
// 功能函数 - 自动检测设备并返回图片
// =================================================================================

/**
 * 自动检测设备类型并返回相应类型的随机图片
 * 移动端返回竖图，桌面端返回横图
 */
function serveAutoDetectImage() {
    // 检测设备类型
    $deviceType = detectDeviceType();
    
    // 根据设备类型决定返回哪种图片
    if ($deviceType === 'mobile') {
        // 移动设备返回竖图
        error_log("为移动设备提供竖图");
        serveRandomImage('v', $GLOBALS['verticalImageCount']);
    } else {
        // 桌面设备返回横图
        error_log("为桌面设备提供横图");
        serveRandomImage('h', $GLOBALS['horizontalImageCount']);
    }
}

// =================================================================================
// 功能函数 - 提供随机图片
// =================================================================================

/**
 * 提供随机图片的核心函数
 * 
 * @param string $type 图片类型 ('h'表示横图, 'v'表示竖图)
 * @param int $count 该类型图片的总数
 * @return void 直接输出图片或错误信息
 */
function serveRandomImage($type, $count) {
    // 检查是否有可用图片
    if ($count <= 0) {
        // 设置404状态码
        header("HTTP/1.0 404 Not Found");
        echo "没有可用的{$type}图片";
        return;
    }
    
    // 生成1到$count之间的随机数
    $randomNum = mt_rand(1, $count);
    
    // 构建图片文件路径
    // __DIR__ 获取当前脚本所在目录
    // 例如：/var/www/html/pics/h/45.webp
    $imagePath = __DIR__ . "/pics/{$type}/{$randomNum}.webp";
    
    // 记录尝试提供的图片路径，便于调试
    error_log("尝试提供图片: {$imagePath}");
    
    // 检查图片文件是否存在
    if (file_exists($imagePath)) {
        error_log("图片存在，正在提供...");
        
        // 设置响应头
        header('Content-Type: image/webp'); // 指定内容类型为WebP图片
        header('Cache-Control: no-cache, no-store, must-revalidate'); // 禁用缓存
        header('Pragma: no-cache'); // 兼同HTTP/1.0的缓存禁用
        header('Expires: 0'); // 设置过期时间为过去
        
        // 读取并输出图片文件内容
        readfile($imagePath);
    } else {
        // 图片文件不存在时的错误处理
        error_log("图片不存在: {$imagePath}");
        header("HTTP/1.0 404 Not Found");
        echo "图片未找到: {$type}/{$randomNum}.webp";
    }
}

// =================================================================================
// 功能函数 - 显示API文档页面
// =================================================================================

/**
 * 显示API使用说明页面
 * 返回一个美观的HTML页面，介绍API的使用方法
 */
function showApiDocumentation() {
    // 设置内容类型为HTML，指定UTF-8编码
    header('Content-Type: text/html; charset=utf-8');
    
    // 输出HTML页面
    echo '
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>随机图片API</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: url("http://picapi.yonghengqwe.top/a") center/cover no-repeat;
            margin: 0;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: rgba(255, 192, 203, 0.5);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(255, 105, 180, 0.2);
            text-align: center;
            max-width: 600px;
            margin: 10px;
            position: relative;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2.5em;
            background: linear-gradient(45deg, #ff69b4, #ffb6c1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .usage-title {
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .endpoint {
            display: inline-block;
            background: #39C5BB;
            padding: 8px 15px;
            border-radius: 5px;
            font-family: "Courier New", monospace;
            margin: 5px;
            transition: all 0.3s ease;
        }
        .endpoint:hover {
            background: #39C5BB;
            color: white;
            transform: translateY(-2px);
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            background: linear-gradient(135deg, #39c5bc48, #39C5BB);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 10px;
            flex: 1;
            min-width: 120px;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        .footer {
            color: #39c5bc8f;
            font-size: 0.9em;
            margin-top: 30px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h1 {
                font-size: 2em;
            }
            .stats {
                flex-direction: column;
            }
            .logo {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="/logo.png" alt="Logo" class="logo-img" onerror="this.parentElement.innerHTML=\'<div style=&quot;width:100%;height:100%;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:30px;&quot;>🖼️</div>\'">
        </div>
        
        <div class="usage">
            <div class="usage-title">🚀 使用方法</div>
            <div>
                <div class="endpoint">/h</div> - 获取随机横版图片
            </div>
            <div>
                <div class="endpoint">/v</div> - 获取随机竖版图片
            </div>
            <div>
                <div class="endpoint">/a</div> - 自动检测设备类型（移动端返回竖图，桌面端返回横图）
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-item">
                <span class="stat-number">' . $GLOBALS['horizontalImageCount'] . '</span>
                <span class="stat-label">横版图片</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $GLOBALS['verticalImageCount'] . '</span>
                <span class="stat-label">竖版图片</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . ($GLOBALS['horizontalImageCount'] + $GLOBALS['verticalImageCount']) . '</span>
                <span class="stat-label">总计图片</span>
            </div>
        </div>
        
        <div class="footer">
            💡 提示：所有图片均为WebP格式，加载更快更清晰
        </div>
    </div>
</body>
</html>';
}
?>