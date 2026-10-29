# AI help

Run `php artisan migrate`, set `XAI_API_KEY`, verify `XAI_MODEL` is available to your xAI account, then set `AI_ASSISTANT_ENABLED=true` and rebuild the config cache. The dashboard sidebar links to AI Help. No other provider or fallback is enabled.

Hard server limits: 1 request per UTC calendar minute, 10 per user per UTC day, 100 app-wide per UTC day; quota is reserved transactionally in SQL before calling xAI, and failures consume quota. Input is capped at 2,000 characters, model output at 700 tokens per step, model steps at 2, tool attempts at 3, with a 20-second provider timeout. These are request/token bounds, not a guaranteed dollar spend cap. Set a provider billing cap too. No client-selected model, conversation history, attachments, web search or execution tools.

Tools list up to 20 owned sites, inspect selected safe fields, and rename a site's display name. Ownership-scoped queries and policies run inside every tool invocation. Rename additionally requires the user's per-request checkbox. No publishing, deleting, content editing, account/security changes or arbitrary mass assignment.

Authenticated endpoint attempts (including disabled, validation and quota failures), tool arguments/outcomes and successful responses/token usage are recorded in `assistant_audits`. Payloads are encrypted with APP_KEY. Audit failures prevent the next provider/tool action. Started records can indicate process interruption. Credentials and exception bodies are never logged. Request text is limited to 2,000 characters in the audit. Authentication, CSRF and verification rejections occur before the assistant endpoint and remain the responsibility of HTTP/access logs.

Restrict database access and preserve APP_KEY for audit access. No audit deletion or retention job is installed: define your retention/privacy policy before enabling. Old budget rows may be removed after their UTC day has ended. Messages and requested safe site metadata go to xAI; audit text may contain sensitive user input. No live provider request is needed for the feature tests.
