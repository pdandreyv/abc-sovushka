<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Совушкина школа — Привязка VK ID</title>
  <link rel="stylesheet" href="{{ asset_versioned('css/auth.css') }}">
  <style>
    html, body { height: 100%; }
    body {
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f6f7fb;
    }
    .vkid-card {
      text-align: center;
      max-width: 460px;
      width: 100%;
      border: 1px solid #e6e6e6;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      border-radius: 16px;
      background: #fff;
      padding: 24px;
    }
  </style>
</head>
<body>
  <div class="vkid-card">
    <h2>Привязка VK ID</h2>
    <p>Нажмите кнопку ниже и подтвердите вход в VK ID.</p>

    <div
      id="vkid-link-widget"
      style="margin: 16px 0;"
      data-app-id="{{ (int) $vkidAppId }}"
      data-redirect-url="{{ $vkidRedirectUrl }}"
      data-callback-url="{{ route('social.vkid.callback') }}"
      data-csrf="{{ csrf_token() }}"
    ></div>
    <div id="vkid-link-error" class="alert alert-danger" style="display: none; margin-top: 12px;"></div>

    <a href="{{ route('profile.show') }}" class="link" style="margin-top: 16px; display: inline-block;">Вернуться в профиль</a>
  </div>

  <script nonce="csp_nonce" src="https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js"></script>
  <script nonce="csp_nonce" type="text/javascript">
    if ('VKIDSDK' in window) {
      const VKID = window.VKIDSDK;

      const widgetEl = document.getElementById('vkid-link-widget');
      const appId = Number(widgetEl.dataset.appId || 0);
      const redirectUrl = widgetEl.dataset.redirectUrl || '';
      const callbackUrl = widgetEl.dataset.callbackUrl || '';
      const csrfToken = widgetEl.dataset.csrf || '';

      VKID.Config.init({
        app: appId,
        redirectUrl: redirectUrl,
        responseMode: VKID.ConfigResponseMode.Callback,
        source: VKID.ConfigSource.LOWCODE,
        scope: '',
      });

      const oAuth = new VKID.OAuthList();
      const errorContainer = document.getElementById('vkid-link-error');

      oAuth.render({
        container: document.getElementById('vkid-link-widget'),
        oauthList: ['vkid', 'mail_ru', 'ok_ru'],
      })
      .on(VKID.WidgetEvents.ERROR, vkidOnError)
      .on(VKID.OAuthListInternalEvents.LOGIN_SUCCESS, function (payload) {
        const code = payload.code;
        const deviceId = payload.device_id;
        const provider = payload.provider || payload.auth_provider || payload.service || payload.source || null;

        VKID.Auth.exchangeCode(code, deviceId)
          .then(function(data) {
            return finishLinking(data.access_token, provider);
          })
          .catch(vkidOnError);
      });

      function finishLinking(accessToken, provider) {
        return fetch(callbackUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            access_token: accessToken,
            provider: provider,
          }),
        })
        .then(function(response) {
          return response.json().then(function(data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function(result) {
          if (result.ok && result.data.redirect) {
            window.location.href = result.data.redirect;
            return;
          }
          throw new Error(result.data.message || 'Ошибка привязки VK ID.');
        })
        .catch(vkidOnError);
      }

      function vkidOnError(error) {
        if (errorContainer) {
          errorContainer.textContent = 'Ошибка привязки VK/OK. Попробуйте еще раз.';
          errorContainer.style.display = 'block';
        }
        if (window.console && console.error) {
          console.error(error);
        }
      }
    }
  </script>
</body>
</html>
