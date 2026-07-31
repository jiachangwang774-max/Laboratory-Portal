<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>实验室管理系统 - {{ $title }}验证码</title>
</head>
<body style="margin:0; padding:0; background-color:#eef3f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', 'Helvetica Neue', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 24px 0;">
        <tr>
            <td align="center">
                <!-- 主卡片：自适应宽度，手机端友好 -->
                <table width="100%" cellpadding="0" cellspacing="0" style="width:92%; max-width:460px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 4px 24px rgba(74,144,226,0.10), 0 2px 6px rgba(0,0,0,0.05);">
                    <!-- Header：蓝色横幅 -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4a90e2, #357abd); padding: 22px 24px 20px 24px; position:relative; border-radius:12px 12px 0 0; overflow:hidden;">
                            <!-- 细密白点纹理 -->
                            <div style="position:absolute; inset:0; pointer-events:none;
                                background-image: radial-gradient(circle, rgba(255,255,255,0.25) 0.7px, transparent 0.7px);
                                background-size: 18px 18px;
                                background-position: 0 0;
                                background-repeat: repeat;">
                            </div>
                            <!-- 左上角淡蓝色虚化圆 -->
                            <div style="position:absolute; top:-22px; left:-22px; width:86px; height:86px; border-radius:50%; background:rgba(133,184,235,0.22); pointer-events:none; filter:blur(18px);"></div>
                            <!-- 右下角淡蓝色虚化圆 -->
                            <div style="position:absolute; bottom:-18px; right:-18px; width:76px; height:76px; border-radius:50%; background:rgba(133,184,235,0.18); pointer-events:none; filter:blur(16px);"></div>

                            <!-- 标题 -->
                            <table cellpadding="0" cellspacing="0" style="position:relative; width:100%;">
                                <tr>
                                    <td align="center">
                                        <span style="color:#ffffff; font-size:18px; font-weight:700; letter-spacing:1.5px; font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', 'Helvetica Neue', sans-serif;">实验室管理系统</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:3px;">
                                        <span style="color:rgba(255,255,255,0.55); font-size:11px; font-weight:400; letter-spacing:0.6px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;">Laboratory Management System</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 24px 24px 24px; text-align:center;">
                            <p style="color:#2c3e50; font-size:15px; margin:0 0 8px 0; font-weight:500; text-align:left;">您好，</p>
                            <p style="color:#555; font-size:13px; margin:0 0 24px 0; line-height:1.7;">
                                您正在进行<strong style="color:#4a90e2;">{{ $title }}</strong>操作，请使用以下验证码完成验证：
                            </p>

                            <!-- 验证码框 -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="background: linear-gradient(135deg, #f0f6fd 0%, #e8f1fb 100%); border-radius:10px; padding: 20px 16px; border: 1px solid #d6e6f8;">
                                        <p style="color:#8a9bb5; font-size:10px; margin:0 0 8px 0; letter-spacing:3px; text-transform:uppercase;">Verification Code</p>
                                        <span style="font-size:32px; font-weight:700; color:#4a90e2; letter-spacing:8px; font-family: 'SF Mono', 'Cascadia Code', 'Consolas', 'Courier New', monospace;">{{ $code }}</span>
                                        <div style="width:36px; height:3px; background: linear-gradient(90deg, #4a90e2, #357abd); border-radius:2px; margin:10px auto 0 auto;"></div>
                                    </td>
                                </tr>
                            </table>

                            <!-- 安全提示 -->
                            <p style="color:#999; font-size:12px; margin:18px 0 0 0; line-height:1.6;">
                                验证码 <strong>{{ $validMinutes ?? '5' }} 分钟内</strong>有效，请勿将验证码透露给任何人。
                            </p>

                            <p style="color:#aaa; font-size:11px; margin:18px 0 0 0; line-height:1.6;">
                                如非本人操作，请忽略此邮件，无需进行任何处理。
                            </p>
                        </td>
                    </tr>

                    <!-- 分割线 -->
                    <tr>
                        <td style="padding:0 24px;">
                            <div style="height:1px; background: linear-gradient(90deg, transparent, #e0e8f0, transparent);"></div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 14px 24px 20px 24px; text-align:center;">
                            <p style="color:#b0bcc8; font-size:10px; margin:0 0 4px 0;">此为系统自动发送邮件，请勿回复。</p>
                            <p style="color:#c8d2dc; font-size:9px; margin:0;">&copy; {{ date('Y') }} 实验室管理系统 · Laboratory Portal</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
