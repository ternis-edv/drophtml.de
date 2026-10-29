:root{color-scheme:light dark;--bg:#f7f8fb;--panel:#eeedff;--text:#253248;--muted:#617089;--line:#e0e5ee;--accent:#4f46e5;--button:#fff;--grid:#dddff0;--number:#dcddf6;--tag:#fff;--route:#818cf8}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
:is(a,button):focus-visible{outline:3px solid var(--accent);outline-offset:5px;border-radius:8px}
.site-error-shell{width:min(1200px,100%);min-height:100dvh;margin:auto;display:flex;flex-direction:column;padding:0 48px}
header,footer{display:flex;align-items:center;justify-content:space-between;gap:24px}
header{min-height:108px;border-bottom:1px solid var(--line)}
.site-identity{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:650;min-width:0}
.site-identity>span:last-child{overflow-wrap:anywhere}
.site-mark{display:grid;place-items:center;flex-shrink:0;width:32px;height:32px;border:1px solid var(--line);border-radius:10px;font-size:20px}
.header-note{color:var(--muted);font-size:12px;white-space:nowrap}
main{flex:1;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:76px;padding:72px 0}
.error-content{min-width:0}
.eyebrow{display:flex;align-items:center;gap:9px;font:10px ui-monospace,monospace;letter-spacing:.1em;color:var(--accent);margin:0 0 26px}
.eyebrow>span{width:6px;height:6px;border-radius:50%;background:var(--accent)}
h1{font-size:clamp(40px,4.4vw,62px);font-weight:550;line-height:1.06;letter-spacing:-.055em;margin:0 0 24px;overflow-wrap:anywhere}
.description{max-width:390px;font-size:14px;line-height:1.85;color:var(--muted);margin:0}
.requested-path{border-left:2px solid var(--line);padding:2px 0 2px 14px;margin:26px 0 30px}
.requested-path>span{display:block;color:var(--muted);font-size:10px;margin-bottom:6px}
.requested-path code{display:block;font:12px/1.7 ui-monospace,monospace;overflow-wrap:anywhere;max-height:100px;overflow:auto}
.primary{display:inline-flex;justify-content:space-between;align-items:center;gap:36px;background:var(--accent);color:var(--button);border:1px solid transparent;border-radius:9px;padding:15px 18px;font-size:13px;font-weight:600;min-height:48px}
.primary:hover{filter:brightness(1.13)}
.primary>span{font-size:19px;line-height:1}
.action-note{font-size:11px;color:var(--muted);line-height:1.6;margin:13px 0 0;overflow-wrap:anywhere}
@include('errors.partials.wayfinder-styles')
.help-note{display:flex;align-items:flex-start;gap:12px;max-width:560px;padding-bottom:32px;color:var(--muted)}
.help-icon{font-size:22px;line-height:1.3}
.help-note p{font-size:11px;line-height:1.8;margin:0}
.help-note strong{color:var(--text);font-weight:600}
footer{border-top:1px solid var(--line);padding:24px 0;font-size:10px;color:var(--muted)}
footer>span{overflow-wrap:anywhere}
footer>a{flex-shrink:0;padding:8px 0}
footer strong{color:var(--text);font-weight:650}
:root[data-theme="dark"]{color-scheme:dark;--bg:#151c28;--panel:#19212f;--text:#d6deeb;--muted:#92a3bc;--line:#2a3445;--accent:#a5b4fc;--button:#19223e;--grid:#293249;--number:#293451;--tag:#202a3c;--route:#818cf8}
:root[data-theme="light"]{color-scheme:light}
@media(prefers-color-scheme:dark){:root:not([data-theme]){--bg:#151c28;--panel:#19212f;--text:#d6deeb;--muted:#92a3bc;--line:#2a3445;--accent:#a5b4fc;--button:#19223e;--grid:#293249;--number:#293451;--tag:#202a3c;--route:#818cf8}
}
@media(max-width:900px){.site-error-shell{padding:0 30px}
main{gap:32px;padding:56px 0}
.map-tag{left:28%;max-width:68%;padding:12px}
.header-note{font-size:11px}
}
@media(max-width:640px){.site-error-shell{padding:0 24px}
header{min-height:84px}
.header-note{display:none}
main{grid-template-columns:1fr;gap:36px;padding:42px 0 32px}
h1{font-size:46px}
.eyebrow{margin-bottom:20px}
.description{max-width:100%}
.wayfinder{aspect-ratio:1.5;max-height:280px}
.map-number{font-size:160px;inset:24px 0 auto}
.map-coordinate{top:18px;left:20px}
.map-caption{bottom:16px;left:20px;right:20px}
.map-tag{top:60%;left:37%;padding:10px 12px}
.map-route{inset:-25% 0;width:100%;height:150%}
.help-note{padding-bottom:26px}
footer{align-items:flex-start;gap:16px;flex-wrap:wrap;padding:20px 0}
.primary{width:100%}
.action-note{text-align:center}
}
@media(forced-colors:active){.primary,.wayfinder,.map-tag{border:1px solid CanvasText}
.map-number{color:GrayText}
.route-line{stroke:CanvasText}
}
.error-icon{width:20px;height:20px;flex-shrink:0;vertical-align:middle}
.header-actions{display:flex;align-items:center;gap:20px;flex-shrink:0}
.theme-toggle{display:grid;place-items:center;width:44px;height:44px;padding:0;border:1px solid var(--line);border-radius:10px;background:transparent;color:var(--text);cursor:pointer}
.theme-toggle[hidden]{display:none}
.theme-toggle:hover{background:var(--panel)}
.theme-sun{display:none}
[data-theme="dark"] .theme-sun{display:block}
[data-theme="dark"] .theme-moon{display:none}
.map-caption .error-icon{width:13px;height:13px}
footer .error-icon{width:13px;height:13px;margin-left:4px}
.help-icon .error-icon{width:22px;height:22px}
