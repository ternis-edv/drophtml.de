# Dashboard interactions and profile pictures

## Context menus

Right-click a site card, site table row, media asset or Code Studio file row for its quick actions. These menus reuse the existing server-side actions and confirmations. The normal action buttons and overflow menus remain available.

- Shift-right-click keeps the browser menu.
- Text inputs and editable content keep their native context menu.
- Focus an item and press Shift+F10 or the Context Menu key to open its menu.
- Arrow keys, Home and End move through actions. Escape closes and restores focus; Tab closes and continues normal navigation.
- Menus stay inside the viewport and close on outside clicks, scrolling, resizing and dashboard navigation.

The reusable Alpine provider is `dashboardContextMenu`, with `x-dashboard.context-menu` rendering the menu in a teleport to the document body. Items use native links/buttons with `role="menuitem"`. Styles are scoped to the dashboard stylesheet. New actions must keep the same ownership checks and destructive-action confirmations as their existing UI.

## Avatars

`x-user-avatar` displays the signed-in user's provider photo in the account menu and profile settings. Initials remain underneath and are used when an image cannot load. Image requests use `no-referrer`. No remote image is fetched by the application server.

GitHub OAuth refreshes `avatar_url` and `avatar_provider` after sign-in. Existing GitHub accounts also get a photo immediately from their numeric GitHub ID, without signing in again. Provider photo fields are server-managed rather than mass assignable through profile requests. Only HTTPS image URLs are accepted; GitHub imports are restricted to GitHub avatar hosts.

The provider-neutral `setProviderAvatar($provider, $url)` interface already accepts `ternis` for a later TernisAuth integration. That integration should pass the verified provider's picture claim through this interface; TernisAuth picture import is not enabled yet.

## AI Help schema readiness

Run `php artisan migrate` when deploying. The existing assistant migration creates both `assistant_budgets` and `assistant_audits`. If either is missing, the help page renders an unavailable state and requests return 503 before any model invocation. Budgets are never bypassed, and application requests do not create tables automatically.
