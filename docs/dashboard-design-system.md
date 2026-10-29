# Dashboard design system

`resources/css/dashboard.css` is the shared dashboard component layer. It is loaded only by the site/app and editor layouts, under `.dashboard-ui`. Landing pages, authentication and public error pages retain their own stylesheets and templates.

The palette comes from `--dashboard-*` tokens, with light and dark values for backgrounds, panels, insets, borders, primary text, secondary text, accent and status colors. Code Studio keeps its dark editing surface. Media-specific browser geometry remains in `site-workspace.css`; it uses the same shared tokens.

Use these building blocks for dashboard additions:

- `x-dashboard.page-heading` for page titles, descriptions and actions.
- `dashboard-panel` for cards, forms, empty states and grouped content.
- `dashboard-title`, `dashboard-section-title`, `dashboard-lede`, `dashboard-stat-label` and `dashboard-stat-value` for typography.
- `dashboard-button` / `dashboard-button-primary` for native links and buttons. Flux buttons carry `data-dashboard-variant` for their visual role.
- `dashboard-tabs` / `dashboard-tab` for compact filters and segmented navigation.
- `dashboard-text`, `dashboard-muted`, `dashboard-accent` and the success/warning/danger color, tint and border classes for semantic states.
- `x-dashboard.badge` for status and category badges; its colors map to semantic status tones.
- `dashboard-code`, `dashboard-meter`, `dashboard-skeleton` for snippets, usage and loading states.

Flux inputs, selects, tables, menus, dialogs, checkboxes, segmented radios and notifications inherit the same dashboard layer. The account settings layout supplies responsive section navigation. `layouts/app/sidebar` is a compatibility alias for the shared site shell, not a separate design.

Coverage includes overview and its cached preview, uploads, shared/site media, Code Studio, analytics, deployments, domains, redirects, site settings, activity, AI Help, command search, profile, security and 2FA dialogs, storage, API tokens, data export, appearance and account deletion.

The shared file uploader defaults to its original public presentation. Dashboard callers explicitly pass `:dashboard="true"`, which selects the dashboard upload form without changing the landing-page uploader.
