# DropHTML.de - Landing Page Architectural Plan

## 1. Vision & Core Objectives
The goal is to replace the current landing page with a stunning, modern, and highly interactive SaaS landing page. It must immediately communicate the value proposition ("Ship HTML in seconds") through visual storytelling and interactive "fake UI" components that perfectly mimic the actual application.

**Key Aesthetic Principles:**
*   **Dark Mode First:** Deep backgrounds (e.g., `zinc-950`) with subtle, glowing accents (glassmorphism, blurred radial gradients) to feel premium and developer-focused.
*   **Motion & Fluidity:** Elements should enter the viewport gracefully, backgrounds should have subtle ambient movement, and interactions must provide immediate, satisfying feedback.
*   **1:1 UI Fidelity:** The interactive examples must use the exact same Flux UI components and styling as the real dashboard to build trust.

## 2. Tech Stack & Tooling
*   **Framework:** Laravel Blade Components (`resources/views/partials/landing/`).
*   **Styling:** Tailwind CSS (v4) leveraging custom utility classes for gradients, drop-shadows, and glassmorphism.
*   **Interactivity & State:** **Alpine.js**. Crucial for the mock UI logic without the overhead of Livewire for static landing page elements.
*   **Animations:** 
    *   Alpine's `x-intersect` plugin for scroll-triggered reveal animations.
    *   Alpine's `x-transition` for micro-interactions (modals, dropdowns).
    *   Native CSS keyframes for continuous ambient motion (floating orbs, background shifts).

## 3. Directory & Component Structure
We will strictly organize the landing page into isolated, reusable Blade partials:

```text
resources/views/
├── welcome.blade.php                      # The main entry point, composing the partials
└── partials/
    └── landing/
        ├── layout.blade.php               # Master layout (Header, Footer, Meta tags)
        ├── navigation.blade.php           # Sticky, glassmorphic navbar
        ├── hero.blade.php                 # Highly animated top section
        ├── interactive-demo.blade.php     # The "Fake UI" section
        ├── bento-grid.blade.php           # Feature highlights using an asymmetrical grid
        ├── animated-terminal.blade.php    # Code snippet/CLI demonstration
        ├── pricing.blade.php              # Tiered pricing with animated toggles
        ├── testimonials.blade.php         # Scrolling marquee of reviews
        └── footer.blade.php               # Comprehensive footer
```

## 4. The "Fake UI" Interactive Examples
This is the core differentiator. We will build a functional mock dashboard directly on the landing page using Alpine.js to handle the state.

### A. The Instant Deploy Simulation
*   **Visual:** A mocked version of the `site-editor` and upload zone.
*   **Interaction:** The user clicks a "Simulate Deployment" button or drops a mock file into a designated zone.
*   **Animation Sequence (Powered by Alpine):**
    1. State changes to `uploading: true`. Progress bar animates from 0 to 100% over 1.5 seconds.
    2. State changes to `building: true`. A mock terminal window shows rapid, fake build logs.
    3. State changes to `success`. A toast notification pops up, and an `<iframe>` (or styled `<div>`) instantly renders the "uploaded" HTML content.
    4. A fake URL (e.g., `https://drophtml.de/s/demo-site`) appears with a "Copy" button.

### B. The Live Code Editor
*   **Visual:** A split pane. Left side: Code editor. Right side: Live preview.
*   **Interaction:** The user types HTML/CSS into a `<textarea>` styled like a code editor.
*   **Logic:** Alpine.js uses `x-model` to bind the textarea value. A computed property or a watcher instantly injects this value into the right-side preview pane using `x-html`.

### C. The API/CLI Terminal
*   **Visual:** A sleek MacOS-style terminal window.
*   **Interaction:** A typewriter effect automatically types out API commands (e.g., `curl -X POST...`).
*   **Logic:** Once the command is "typed", it shows a mock JSON response highlighting the comprehensive REST API features (Sites, Domains, Deployments).

## 5. Animation Strategy

*   **Ambient Backgrounds:** Use absolute positioned `div`s with `bg-gradient-radial`, `blur-3xl`, and `opacity-30`. Apply a slow, infinite CSS animation to translate and scale them, creating a breathing effect.
*   **Scroll Reveals:** Wrap sections in Alpine intersect directives:
    ```html
    <div x-data="{ shown: false }" x-intersect.once="shown = true">
        <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-1000 ease-out">
            <!-- Content -->
        </div>
    </div>
    ```
*   **Bento Box Hover States:** Cards in the `bento-grid` should have a slight upward transform and increased shadow/border opacity on hover to invite interaction.
*   **Typing Effects:** Use Alpine.js to create a typewriter effect for the main H1 tag in the hero section to emphasize speed and coding.

## 6. Implementation Phases

**Phase 1: Foundation & Layout**
*   Create the file structure.
*   Build the `landing/layout.blade.php` and `landing/navigation.blade.php`.
*   Ensure dark mode is locked in or explicitly styled for the landing page.

**Phase 2: The Hero & Ambient Aesthetics**
*   Implement `landing/hero.blade.php` with the typography, primary CTAs, and the animated, glowing background effects.

**Phase 3: Interactive Demos (The Hard Part)**
*   Build `landing/interactive-demo.blade.php`.
*   Implement the complex Alpine.js state machines required for the mock deployment sequence and the live code editor. Ensure UI components perfectly match the real Flux components.

**Phase 4: Supporting Sections**
*   Implement `landing/bento-grid.blade.php` for features (Custom Domains, Password Protection, API).
*   Implement `landing/animated-terminal.blade.php`.
*   Add Pricing and Testimonials.

**Phase 5: Polish & Polish**
*   Apply `x-intersect` to all sections for smooth scroll-in effects.
*   Test responsive design thoroughly (mobile interactive demos need careful layout).
*   Optimize performance (ensure large blurs don't cause scrolling lag).
