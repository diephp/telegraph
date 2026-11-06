# Security Policy

This policy applies to `diephp/telegraph`, an independent fork of `defstudio/telegraph`.

Only the latest stable 2.x release is supported. Upstream 1.x tags are inherited
history and are not maintained releases of this fork.

Report vulnerabilities privately using GitHub's **Report a vulnerability** option
in this repository's Security tab, if enabled. Otherwise contact the repository
owner privately through their GitHub profile. Do not post bot tokens, webhook
secrets, customer data or working exploit payloads in public issues.

Deploy using a patched, supported PHP build. As of 2026-11-06, PHP's October
security fixes require at least 8.3.36, 8.4.27 or 8.5.12 on their respective
branches, or a distribution build with equivalent backported fixes.

Configure `TELEGRAPH_WEBHOOK_SECRET`, register the webhook with that same secret,
and explicitly list remotely callable handler methods in `$allowedActions`.
Callback parameters are untrusted: authorize the requesting user/chat against
the restaurant, order or other application resource inside every sensitive action.

Keep custom API base URLs/proxies trusted. Treat `getApiUrl()`, `getUrl()` and
`toArray()['url']` as sensitive; use `getSafeApiUrl()` for logging. Disable webhook
payload debugging in production because payloads can contain personal data.
