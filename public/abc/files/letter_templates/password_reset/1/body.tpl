<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Восстановление пароля</title>
</head>
<body style="margin:0;padding:0;background:#F6F7FB;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F6F7FB;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;">
          <tr>
            <td align="left" style="padding:10px 6px 16px 6px;">
              <img src="{{logo_url}}" width="180" alt="Совушкина школа" style="display:block;border:0;outline:none;height:auto;max-width:180px;">
            </td>
          </tr>
          <tr>
            <td style="background:#FFFFFF;border-radius:16px;padding:22px 22px 18px 22px;border:1px solid #E7EAF0;">
              <div style="font-family:Arial,Helvetica,sans-serif;color:#1F2937;">
                <div style="font-size:18px;font-weight:700;line-height:1.3;margin:0 0 8px 0;">
                  Восстановление пароля
                </div>
                <div style="font-size:14px;line-height:1.6;margin:0 0 14px 0;color:#374151;">
                  Здравствуйте{{#if user_name}}, {{user_name}}{{/if}}!<br>
                  Вы запросили восстановление пароля на сайте <b>Совушкина школа</b>. Перейдите по ссылке ниже, чтобы задать новый пароль. Старый пароль вводить не нужно.
                </div>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:14px 0 14px 0;">
                  <tr>
                    <td bgcolor="#2F6FE4" style="border-radius:12px;">
                      <a href="{{reset_url}}" target="_blank"
                        style="display:inline-block;padding:12px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;
                        font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:12px;">
                        Задать новый пароль
                      </a>
                    </td>
                  </tr>
                </table>
                <div style="font-size:12px;line-height:1.6;margin:14px 0 0 0;color:#6B7280;">
                  Ссылка действительна {{expire_minutes}} минут. Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:14px 6px 0 6px;">
              <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#6B7280;">© {{year}} «Совушкина школа»</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
