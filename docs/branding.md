# drophtml.de identity

The **drop bracket** combines a detached square (the file) with an open bracket
that resolves into a downward point (the drop). The lowercase wordmark uses
Instrument Sans Semibold with tighter spacing and a quieter `.de` suffix.

## Assets

All assets live in `public/brand/`. Open `index.html` for the brand sheet.

- `logo-{light,dark,mono,white}.svg`: horizontal icon + wordmark.
- `wordmark-{light,dark,mono,white}.svg`: wordmark alone.
- `icon-{light,dark,mono,white}.svg`: standalone symbol with transparent background.
- `app-icon.svg`, `app-icon-512.png`: white symbol on an indigo rounded tile.
- `brand-kit.zip`: downloadable kit including this guide and the font license.

`light` is intended for light backgrounds; `dark` for dark backgrounds.
`mono` uses solid ink; `white` uses solid white. SVG lettering is outlined, so
there is no font dependency. Keep the SVG viewBox and aspect ratio intact.
The favicon, multi-resolution ICO, and 180px Apple touch icon use the same tile.

## Color

| Token | Hex | Use |
| --- | --- | --- |
| Drop indigo | `#4F46E5` | Primary symbol and app tile |
| Ink | `#171923` | Wordmark on light backgrounds |
| Periwinkle | `#A5B4FC` | Symbol on dark backgrounds |
| Soft white | `#F5F5FA` | Wordmark on dark backgrounds |
| Mist | `#F4F3FF` | Optional presentation surface |
| Slate | `#687084` | `.de` on light backgrounds |
| Pale slate | `#A3A9BD` | `.de` on dark backgrounds |

## Usage

Allow at least one file-square width of clear space around the mark (12 units
on its 64-unit grid). Use the tile for favicons at 16px and above; use the bare
symbol at 24px and above. Use the horizontal lockup at a minimum width of 160px.
Do not stretch, rotate, add gradients, or change the relative icon/type spacing.
The proposed supporting line is: **Drop a site. Make it live.**

In Blade, use `<x-brand-lockup />` inside an accessible link. Use
`<x-brand-lockup inverse />` on a permanently dark surface. Standalone geometry
is available through `<x-app-logo-icon />` and `<x-app-wordmark />`; both inherit
`currentColor` and are decorative, so label their containing link or control.

Instrument Sans is Copyright 2022 The Instrument Sans Project Authors,
licensed under the SIL Open Font License 1.1. See `FONT-LICENSE.txt` in the kit.
The original font source is https://github.com/Instrument/instrument-sans.
