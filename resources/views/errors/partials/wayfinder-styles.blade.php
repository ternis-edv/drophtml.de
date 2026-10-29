.wayfinder{position:relative;isolation:isolate;aspect-ratio:1.04;background:var(--panel);border:1px solid var(--line);border-radius:22px;overflow:hidden}
.map-grid{position:absolute;inset:0;background-image:linear-gradient(var(--grid) 1px,transparent 1px),linear-gradient(90deg,var(--grid) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(145deg,#000,transparent 85%)}
.map-coordinate{position:absolute;top:25px;left:25px;font:9px ui-monospace,monospace;letter-spacing:.12em;color:var(--muted)}
.map-number{position:absolute;inset:44px 0 auto;text-align:center;font-size:clamp(120px,16vw,216px);font-weight:750;letter-spacing:-.08em;line-height:1;color:var(--number)}
.map-route{position:absolute;inset:0;width:100%;height:100%;stroke-linecap:round;stroke-linejoin:round}
.route-shadow{stroke:var(--panel);stroke-width:15}
.route-line{stroke:var(--route);stroke-width:2;stroke-dasharray:5 7}
.map-pin-halo{fill:var(--accent);fill-opacity:.12}
.map-pin{fill:var(--accent);stroke:var(--panel);stroke-width:3}
.destination{fill:var(--tag);stroke:var(--line)}
.destination-check{stroke:var(--accent);stroke-width:2}
.map-tag{position:absolute;left:35%;top:62%;padding:14px 16px;border:1px solid var(--line);border-radius:10px;background:var(--tag);font-size:11px;font-weight:600;box-shadow:0 8px 24px #25324808;max-width:60%}
.map-tag>span{display:block;font-size:9px;font-weight:400;color:var(--muted);margin-top:5px;line-height:1.5}
.map-caption{position:absolute;bottom:23px;left:25px;right:25px;display:flex;justify-content:space-between;font:8px ui-monospace,monospace;letter-spacing:.1em;color:var(--muted)}
/* Four seamless cycles give the route a gentle drift, then let the page rest. */
.route-line{animation:error-route-drift 1.2s linear 4}
@keyframes error-route-drift{from{stroke-dashoffset:0}to{stroke-dashoffset:-12}}
@media(prefers-reduced-motion:reduce){.route-line{animation:none}}
