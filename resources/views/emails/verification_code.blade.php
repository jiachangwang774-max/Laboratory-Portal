<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>实验室管理系统 - {{ $title }}验证码</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family: Arial, 'Microsoft YaHei', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1677ff; padding: 28px 32px; text-align:center;">
                            <h1 style="color:#ffffff; font-size:20px; margin:0; font-weight:600;">实验室管理系统</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px;">
                            <p style="color:#333; font-size:15px; margin:0 0 12px 0;">您好！</p>
                            <p style="color:#333; font-size:15px; margin:0 0 24px 0;">
                                您正在为 <strong>实验室管理系统</strong> 进行<strong>{{ $title }}</strong>操作，验证码如下：
                            </p>
                            <!-- Code -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="background-color:#f0f5ff; border-radius:6px; padding: 20px 16px;">
                                        <span style="font-size:32px; font-weight:700; color:#1677ff; letter-spacing:8px; font-family: 'Courier New', monospace;">{{ $code }}</span>
                                    </td>
                                </tr>
                            </table>
                            <p style="color:#999; font-size:13px; margin:20px 0 0 0; text-align:center;">
                                验证码 <strong>5 分钟内</strong>有效，请勿转发给他人。
                            </p>
                            <p style="color:#999; font-size:13px; margin:8px 0 0 0; text-align:center;">
                                如非本人操作，请忽略此邮件。
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="border-top:1px solid #eee; padding: 16px 32px; text-align:center;">
                            <p style="color:#bbb; font-size:12px; margin:0;">此为系统自动发送邮件，请勿回复。</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
