# Mailbox (IMAP) + Google OAuth — план и порядок действий

Админка Orchid: **Mailbox** → список писем, просмотр, compose/reply, **Settings**.

## Правила синка (жёстко)

- Только **чтение** с сервера: `BODY.PEEK` / `leaveUnread` — **не** помечать прочитанным.
- **Не** удалять и **не** перемещать письма на почтовом сервере.
- Импорт **с вчерашнего дня** (`SINCE` + проверка даты).
- Subject содержит `Deluxewindows` (настраивается).
- From пока только `notify.deluxewindows.com` (настраивается в Settings → From filter).
- Крон: `php artisan mailbox:sync` каждые 10 минут (если Enable sync включён).

## Авторизация: Google OAuth (без 2-Step / App Password)

В Settings: **Client ID / Client Secret** → **Connect with Google** → токены хранятся зашифрованно.  
IMAP/SMTP идут через **XOAUTH2**. App Password — только запасной вариант.

Redirect URI (смотреть в админке, поле *Authorized redirect URI*):

- прод: `https://<домен>/admin/mailbox/google/callback`
- локально: `http://127.0.0.1:8000/admin/mailbox/google/callback`

### Порядок действий в Google Cloud

1. Открыть [Google Cloud Console](https://console.cloud.google.com/) → создать или выбрать проект.
2. **APIs & Services → OAuth consent screen**
   - User type: External (или Internal для Workspace).
   - Указать имя приложения и support email.
   - Scopes: `https://mail.google.com/` (+ email / profile / openid).
   - Пока статус **Testing** — добавить свой Google-аккаунт в **Test users**.
3. **Credentials → Create credentials → OAuth client ID**
   - Application type: **Web application**.
   - Authorized redirect URIs: вставить URI из админки (см. выше).
4. Скопировать **Client ID** и **Client Secret**.
5. В админке: **Mailbox → Settings**
   - Вставить Client ID / Client Secret → **Save**.
   - **Connect with Google** → войти в нужный ящик → Allow.
   - Включить **Enable sync** → **Test connection** → **Sync now**.

### Если refresh token не пришёл

В Google Account → Security → Third-party access отозвать доступ приложению, затем снова **Connect with Google** (consent с `prompt=consent` уже включён в коде).

## Ключевые файлы

- Сервисы: `app/Services/Mailbox/`
- Экраны: `app/Orchid/Screens/Mailbox/`
- OAuth controller: `app/Http/Controllers/Admin/GoogleMailboxOAuthController.php`
- Роуты: `routes/platform.php` (`platform.mailbox.*`, `platform.mailbox.google.*`)
- Команда: `php artisan mailbox:sync`
- Schedule: `bootstrap/app.php`

## Дальше (по мере надобности)

- Расширять From filter / несколько ящиков.
- Verification OAuth-приложения в Google (если выйти из Testing на production).
