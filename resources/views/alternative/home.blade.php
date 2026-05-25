<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deluxe Windows — Window &amp; Door Replacement, Bay Area</title>
<meta name="description" content="Upgrade your Bay Area home with energy-efficient windows. Deluxe Windows offers 30+ years of expert installation of vinyl, aluminum, fiberglass &amp; wood. Free quotes.">
<meta property="og:title" content="Deluxe Windows — Window &amp; Door Replacement, Bay Area">
<meta property="og:description" content="Premium window and door replacement for San Francisco Bay Area homes. 30+ years, 100% employee owned.">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,600;9..144,700;9..144,800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="shortcut icon" href="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/6924517e8392d51f62f03159_favicon-new.png" type="image/x-icon">
<style>
/* ============================================================
   DESIGN TOKENS
============================================================ */
:root {
  --bg:             oklch(95.5% 0.009 82);
  --surface:        oklch(98.5% 0.005 76);
  --dark:           oklch(16% 0.014 50);
  --dark-2:         oklch(22% 0.014 50);
  --accent:         oklch(54% 0.138 46);
  --accent-dk:      oklch(50% 0.138 46);
  --accent-pale:    oklch(88% 0.050 78);
  --text:           oklch(22% 0.014 50);
  --muted:          oklch(50% 0.010 54);
  --border:         oklch(87% 0.010 78);
  --radius:         6px;
  --radius-md:      12px;
  --radius-lg:      20px;
  --shadow-sm:      0 2px 8px oklch(10% 0.01 50 / 0.08);
  --shadow-md:      0 8px 32px oklch(10% 0.01 50 / 0.14);
  --shadow-lg:      0 20px 60px oklch(10% 0.01 50 / 0.22);
  --ease-out:       cubic-bezier(0.22, 1, 0.36, 1);
  --ff-display:     'Fraunces', Georgia, serif;
  --ff-body:        'Inter', system-ui, sans-serif;
  --container:      1240px;
  --gutter:         clamp(20px, 5vw, 80px);
}

/* ============================================================
   RESET + BASE
============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--ff-body);
  font-size: 16px;
  line-height: 1.65;
  color: var(--text);
  background: var(--bg);
  -webkit-font-smoothing: antialiased;
}
img { display: block; max-width: 100%; height: auto; }
a { color: inherit; text-decoration: none; }
ul, ol { list-style: none; }

/* ============================================================
   UTILITIES
============================================================ */
.wrap {
  max-width: var(--container);
  margin-inline: auto;
  padding-inline: var(--gutter);
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 13px 26px;
  border: 1.5px solid transparent;
  border-radius: var(--radius);
  font-family: var(--ff-body);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 160ms var(--ease-out), transform 160ms var(--ease-out), border-color 160ms var(--ease-out), color 160ms var(--ease-out);
  white-space: nowrap;
  letter-spacing: 0.01em;
}
.btn:hover { transform: translateY(-1px); }
.btn-accent { background: var(--accent); color: oklch(98% 0.005 76); }
.btn-accent:hover { background: var(--accent-dk); }
.btn-outline-light {
  background: transparent;
  color: oklch(97% 0.005 76);
  border-color: oklch(100% 0 0 / 0.30);
}
.btn-outline-light:hover {
  background: oklch(100% 0 0 / 0.10);
  border-color: oklch(100% 0 0 / 0.55);
}
.btn-dark { background: var(--dark); color: oklch(97% 0.005 76); }
.btn-dark:hover { background: var(--dark-2); }
.eyebrow {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 14px;
}
.eyebrow-light { color: var(--accent-pale); }
.section-h {
  font-family: var(--ff-display);
  font-size: clamp(30px, 3.8vw, 52px);
  font-weight: 700;
  line-height: 1.10;
  letter-spacing: -0.025em;
  color: var(--text);
}
.fade-in {
  opacity: 0;
  transform: translateY(22px);
  transition: opacity 500ms var(--ease-out), transform 500ms var(--ease-out);
}
.fade-in.visible { opacity: 1; transform: none; }

/* ============================================================
   NAVBAR
============================================================ */
.nav {
  position: fixed;
  inset-block-start: 0;
  inset-inline: 0;
  z-index: 900;
  padding-block: 18px;
  transition: background 300ms var(--ease-out), box-shadow 300ms var(--ease-out), padding 300ms var(--ease-out);
}
.nav.nav-solid {
  background: var(--surface);
  box-shadow: var(--shadow-sm);
  padding-block: 12px;
}
.nav-inner {
  display: flex;
  align-items: center;
  gap: 36px;
}
.nav-logo img {
  height: 38px;
  width: auto;
  transition: filter 300ms var(--ease-out);
}
.nav-solid .nav-logo img { filter: brightness(0) saturate(0); }
.nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
  margin-left: auto;
}
.nav-link {
  font-size: 14px;
  font-weight: 500;
  color: oklch(97% 0.005 76 / 0.85);
  transition: color 160ms;
}
.nav-solid .nav-link { color: var(--muted); }
.nav-link:hover,
.nav-solid .nav-link:hover { color: var(--accent); }
.nav-actions { display: flex; align-items: center; gap: 10px; }
.nav-phone {
  font-size: 14px;
  font-weight: 600;
  color: oklch(97% 0.005 76 / 0.85);
  transition: color 160ms;
}
.nav-solid .nav-phone { color: var(--text); }
.nav-phone:hover { color: var(--accent); }
.nav-burger {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  margin-left: auto;
}
.nav-burger span {
  display: block;
  width: 24px;
  height: 2px;
  background: oklch(97% 0.005 76);
  border-radius: 2px;
  transition: background 300ms;
}
.nav-solid .nav-burger span { background: var(--text); }
.nav-drawer {
  display: none;
  flex-direction: column;
  gap: 2px;
  position: fixed;
  inset-block-start: 62px;
  inset-inline: 0;
  background: var(--surface);
  padding: 16px var(--gutter) 28px;
  box-shadow: var(--shadow-md);
  z-index: 899;
}
.nav-drawer.open { display: flex; }
.drawer-link {
  font-size: 18px;
  font-weight: 500;
  color: var(--text);
  padding-block: 11px;
  border-bottom: 1px solid var(--border);
}
.drawer-link:last-child { border-bottom: none; }

/* ============================================================
   HERO
============================================================ */
.hero {
  position: relative;
  min-height: 100svh;
  display: flex;
  align-items: center;
  overflow: hidden;
}
.hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}
.hero-bg video,
.hero-bg .hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.hero-bg .hero-img { display: none; }
.hero-overlay {
  position: absolute;
  inset: 0;
  background: oklch(10% 0.012 48 / 0.68);
  z-index: 1;
}
.hero-body {
  position: relative;
  z-index: 2;
  width: 100%;
  padding-block: 130px 80px;
}
.hero-grid {
  display: grid;
  grid-template-columns: 1fr 430px;
  gap: 64px;
  align-items: center;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: oklch(100% 0 0 / 0.10);
  border: 1px solid oklch(100% 0 0 / 0.18);
  backdrop-filter: blur(8px);
  border-radius: 100px;
  padding: 5px 16px 5px 10px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: oklch(97% 0.005 76 / 0.85);
  margin-bottom: 24px;
}
.hero-badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
}
.hero-h1 {
  font-family: var(--ff-display);
  font-size: clamp(46px, 5.8vw, 82px);
  font-weight: 700;
  line-height: 1.06;
  color: oklch(98.5% 0.005 76);
  letter-spacing: -0.025em;
  margin-bottom: 22px;
}
.hero-h1 em {
  font-style: italic;
  color: var(--accent-pale);
}
.hero-offer {
  font-size: 17px;
  font-weight: 400;
  color: oklch(95% 0.005 76 / 0.75);
  margin-bottom: 28px;
  line-height: 1.5;
}
.hero-offer strong { color: var(--accent-pale); font-weight: 700; }
.hero-trust {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 20px;
  margin-bottom: 28px;
}
.hero-trust-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
  color: oklch(90% 0.005 76 / 0.75);
}
.hero-trust-check {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: oklch(98% 0.005 76);
  flex-shrink: 0;
  line-height: 1;
}
.hero-social {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: oklch(90% 0.005 76 / 0.65);
}
.hero-stars { color: oklch(82% 0.12 82); font-size: 15px; letter-spacing: -1px; }
.hero-form-card {
  background: var(--surface);
  border-radius: var(--radius-lg);
  padding: 34px 30px 28px;
  box-shadow: var(--shadow-lg);
}
.form-heading {
  font-family: var(--ff-display);
  font-size: 21px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 4px;
}
.form-sub {
  font-size: 12px;
  color: var(--muted);
  margin-bottom: 22px;
  line-height: 1.5;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}
.form-row.solo { grid-template-columns: 1fr; }
.form-field { display: flex; flex-direction: column; gap: 4px; }
.form-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}
.form-control {
  padding: 10px 12px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  font-family: var(--ff-body);
  font-size: 14px;
  color: var(--text);
  background: var(--bg);
  outline: none;
  transition: border-color 160ms, box-shadow 160ms;
}
.form-control:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px oklch(54% 0.138 46 / 0.15);
}
.form-control::placeholder { color: oklch(65% 0.008 60); }
textarea.form-control { resize: vertical; min-height: 76px; }
.form-submit {
  width: 100%;
  justify-content: center;
  margin-top: 6px;
  padding: 14px;
  font-size: 14px;
}
.form-fine { font-size: 11px; color: var(--muted); margin-top: 8px; line-height: 1.4; }

/* ============================================================
   STATS STRIP
============================================================ */
.stats {
  background: var(--dark);
  padding-block: 52px;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
}
.stat { text-align: center; }
.stat-stars { color: oklch(82% 0.12 82); font-size: 16px; display: block; margin-bottom: 4px; }
.stat-num {
  font-family: var(--ff-display);
  font-size: clamp(38px, 4vw, 58px);
  font-weight: 700;
  color: oklch(97% 0.005 76);
  line-height: 1;
  margin-bottom: 8px;
}
.stat-num sup { font-size: 0.5em; vertical-align: super; }
.stat-label {
  font-size: 12px;
  font-weight: 500;
  color: oklch(58% 0.008 55);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ============================================================
   BRANDS
============================================================ */
.brands {
  background: var(--surface);
  padding-block: 30px;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  overflow: hidden;
}
.brands-row {
  display: flex;
  align-items: center;
  gap: 48px;
  flex-wrap: wrap;
  justify-content: center;
}
.brands-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--muted);
  flex-shrink: 0;
}
.brand-img {
  height: 26px;
  width: auto;
  opacity: 0.45;
  filter: grayscale(1);
  transition: opacity 200ms, filter 200ms;
}
.brand-img:hover { opacity: 1; filter: none; }

/* ============================================================
   WINDOWS SECTION
============================================================ */
.windows-section {
  background: var(--bg);
  padding-block: 96px;
}
.section-hdr {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 44px;
  gap: 24px;
  flex-wrap: wrap;
}
/* Asymmetric editorial grid */
.win-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: 300px 300px;
  gap: 14px;
}
/* Row 1: Card 1 wide (2 cols) + Card 2 narrow */
.win-grid .wc:nth-child(1) { grid-column: 1 / 3; grid-row: 1; }
.win-grid .wc:nth-child(2) { grid-column: 3;     grid-row: 1; }
/* Row 2: Card 3 narrow + Card 4 wide (2 cols) */
.win-grid .wc:nth-child(3) { grid-column: 1;     grid-row: 2; }
.win-grid .wc:nth-child(4) { grid-column: 2 / 4; grid-row: 2; }

.wc {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  background: var(--dark);
  display: block;
}
.wc-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 600ms var(--ease-out);
}
.wc:hover .wc-img { transform: scale(1.04); }
.wc-shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, oklch(10% 0.012 48 / 0.82) 0%, transparent 55%);
}
.wc-body {
  position: absolute;
  inset-block-end: 0;
  inset-inline: 0;
  padding: 22px;
}
.wc-title {
  font-family: var(--ff-display);
  font-size: 20px;
  font-weight: 700;
  color: oklch(98% 0.005 76);
  margin-bottom: 4px;
}
.wc-desc {
  font-size: 13px;
  color: oklch(88% 0.005 76 / 0.75);
  max-width: 28ch;
  line-height: 1.5;
}
.wc-arrow {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 10px;
  font-size: 12px;
  font-weight: 600;
  color: var(--accent-pale);
  letter-spacing: 0.04em;
}
.see-all-row { text-align: center; margin-top: 36px; }

/* ============================================================
   DOORS SECTION
============================================================ */
.doors-section {
  background: var(--surface);
  padding-block: 96px;
}
.door-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-top: 44px;
}
.dc {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  aspect-ratio: 3 / 4;
  cursor: pointer;
  background: var(--dark);
  display: block;
}
.dc-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 600ms var(--ease-out);
}
.dc:hover .dc-img { transform: scale(1.04); }
.dc-shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, oklch(10% 0.012 48 / 0.88) 0%, transparent 55%);
}
.dc-body {
  position: absolute;
  inset-block-end: 0;
  inset-inline: 0;
  padding: 26px;
}
.dc-title {
  font-family: var(--ff-display);
  font-size: 22px;
  font-weight: 700;
  color: oklch(98% 0.005 76);
}

/* ============================================================
   GUARANTEE
============================================================ */
.guarantee {
  background: var(--dark);
  color: oklch(97% 0.005 76);
  padding-block: 96px;
}
.guar-hdr {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 52px;
}
.guar-icon { width: 60px; height: auto; filter: invert(1) opacity(0.85); }
.guar-h2 {
  font-family: var(--ff-display);
  font-size: clamp(30px, 3.5vw, 46px);
  font-weight: 700;
  letter-spacing: -0.025em;
}
.guar-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  margin-bottom: 32px;
}
.guar-card {
  background: var(--dark-2);
  border-radius: var(--radius-md);
  padding: 30px 26px;
  border: 1px solid oklch(100% 0 0 / 0.05);
}
.guar-term {
  font-family: var(--ff-display);
  font-size: 46px;
  font-weight: 700;
  color: var(--accent-pale);
  line-height: 1;
  margin-bottom: 8px;
}
.guar-type {
  font-size: 13px;
  font-weight: 600;
  color: oklch(60% 0.008 55);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 14px;
}
.guar-desc {
  font-size: 15px;
  color: oklch(78% 0.008 55);
  line-height: 1.6;
}
.guar-footnote { font-size: 13px; color: oklch(45% 0.008 55); line-height: 1.5; }

/* ============================================================
   CERTIFICATIONS
============================================================ */
.certs {
  background: var(--bg);
  padding-block: 80px;
}
.certs-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 64px;
  align-items: center;
}
.cert-icon { width: 52px; margin-bottom: 16px; opacity: 0.65; }
.cert-h2 {
  font-family: var(--ff-display);
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin-bottom: 12px;
}
.cert-desc { font-size: 15px; color: var(--muted); line-height: 1.65; max-width: 38ch; }
.cert-logos {
  display: flex;
  align-items: center;
  gap: 36px;
  flex-wrap: wrap;
}
.cert-logo {
  height: 40px;
  width: auto;
  filter: grayscale(1);
  opacity: 0.55;
  transition: opacity 200ms, filter 200ms;
}
.cert-logo:hover { opacity: 1; filter: none; }

/* ============================================================
   TRUSTED PARTNER
============================================================ */
.partner {
  background: var(--surface);
  padding-block: 96px;
}
.partner-grid {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 80px;
  align-items: start;
}
.partner-h2 {
  font-family: var(--ff-display);
  font-size: clamp(26px, 3vw, 40px);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.022em;
  margin-bottom: 18px;
}
.partner-p {
  font-size: 15px;
  color: var(--muted);
  line-height: 1.7;
  max-width: 40ch;
  margin-bottom: 32px;
}
.partner-items { display: flex; flex-direction: column; gap: 0; }
.pi {
  padding-block: 26px;
  border-top: 1px solid var(--border);
}
.pi-hd {
  font-family: var(--ff-display);
  font-size: 19px;
  font-weight: 700;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.pi-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
}
.pi-p { font-size: 14px; color: var(--muted); line-height: 1.65; max-width: 54ch; }

/* ============================================================
   REVIEWS / MAP
============================================================ */
.reviews { background: var(--bg); padding-block: 80px; }
.map-sec { background: var(--surface); padding-block: 80px; }
.sec-heading-lg {
  font-family: var(--ff-display);
  font-size: clamp(26px, 3vw, 40px);
  font-weight: 700;
  letter-spacing: -0.022em;
  margin-bottom: 36px;
}

/* ============================================================
   CTA SECTION
============================================================ */
.cta-sec {
  background: var(--dark);
  padding-block: 80px;
}
.cta-grid {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 64px;
  align-items: center;
}
.cta-h2 {
  font-family: var(--ff-display);
  font-size: clamp(30px, 4vw, 54px);
  font-weight: 700;
  color: oklch(97% 0.005 76);
  line-height: 1.10;
  letter-spacing: -0.025em;
  margin-bottom: 16px;
}
.cta-p {
  font-size: 17px;
  color: oklch(65% 0.008 55);
  margin-bottom: 34px;
  max-width: 44ch;
}
.cta-img img {
  width: 100%;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
}

/* ============================================================
   FAQ
============================================================ */
.faq { background: var(--bg); padding-block: 96px; }
.faq-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 80px;
  align-items: start;
}
.faq-h2 {
  font-family: var(--ff-display);
  font-size: clamp(26px, 3vw, 38px);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.022em;
  margin-bottom: 14px;
}
.faq-contact { font-size: 14px; color: var(--muted); line-height: 1.65; }
.faq-contact a { color: var(--accent); font-weight: 600; }
details.faq-item { border-bottom: 1px solid var(--border); }
details.faq-item:first-child { border-top: 1px solid var(--border); }
details.faq-item summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding-block: 20px;
  cursor: pointer;
  list-style: none;
  user-select: none;
}
details.faq-item summary::-webkit-details-marker { display: none; }
.faq-q {
  font-family: var(--ff-display);
  font-size: 17px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.3;
}
.faq-plus {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--accent-pale);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 300;
  flex-shrink: 0;
  transition: transform 200ms var(--ease-out), background 200ms, color 200ms;
  line-height: 0;
  padding-bottom: 1px;
}
details[open] .faq-plus {
  transform: rotate(45deg);
  background: var(--accent);
  color: oklch(97% 0.005 76);
}
.faq-ans {
  padding-block-end: 20px;
  padding-inline-end: 44px;
}
.faq-ans p { font-size: 14px; color: var(--muted); line-height: 1.75; }
.faq-ans a { color: var(--accent); }

/* ============================================================
   FOOTER
============================================================ */
.footer {
  background: var(--dark);
  color: oklch(60% 0.008 55);
  padding-block: 68px 44px;
}
.footer-main {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 64px;
  margin-bottom: 52px;
}
.footer-logo { height: 42px; width: auto; margin-bottom: 18px; }
.footer-cta {
  font-family: var(--ff-display);
  font-size: 21px;
  font-weight: 700;
  color: oklch(97% 0.005 76);
  line-height: 1.25;
  margin-bottom: 8px;
}
.footer-phone-link {
  display: block;
  font-size: 19px;
  font-weight: 600;
  color: var(--accent-pale);
  margin-bottom: 20px;
}
.footer-social { display: flex; gap: 8px; }
.footer-social a {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: oklch(100% 0 0 / 0.07);
  display: flex;
  align-items: center;
  justify-content: center;
  color: oklch(60% 0.008 55);
  transition: background 160ms, color 160ms;
}
.footer-social a:hover { background: var(--accent); color: oklch(97% 0.005 76); }
.footer-nav {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
}
.footer-col ul { display: flex; flex-direction: column; gap: 9px; }
.footer-col-link {
  font-size: 13px;
  color: oklch(52% 0.008 55);
  transition: color 160ms;
}
.footer-col-link:hover { color: oklch(88% 0.005 76); }
.footer-bottom {
  border-top: 1px solid oklch(100% 0 0 / 0.05);
  padding-top: 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}
.footer-copy { font-size: 12px; color: oklch(38% 0.008 55); }

/* ============================================================
   RESPONSIVE
============================================================ */
@media (max-width: 1100px) {
  .hero-grid { grid-template-columns: 1fr 390px; gap: 44px; }
  .partner-grid { grid-template-columns: 1fr; gap: 44px; }
  .certs-inner { grid-template-columns: 1fr; }
  .footer-main { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 900px) {
  .hero-grid { grid-template-columns: 1fr; }
  .hero-bg video { display: none; }
  .hero-bg .hero-img { display: block; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .win-grid {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto;
  }
  .win-grid .wc:nth-child(1),
  .win-grid .wc:nth-child(2),
  .win-grid .wc:nth-child(3),
  .win-grid .wc:nth-child(4) {
    grid-column: auto;
    grid-row: auto;
    aspect-ratio: 3 / 4;
  }
  .door-grid { grid-template-columns: 1fr; }
  .dc { aspect-ratio: 16 / 9; }
  .guar-grid { grid-template-columns: 1fr; gap: 16px; }
  .cta-grid { grid-template-columns: 1fr; }
  .cta-img { display: none; }
  .faq-grid { grid-template-columns: 1fr; gap: 36px; }
  .footer-main { grid-template-columns: 1fr; }
  .footer-nav { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .nav-links, .nav-phone { display: none; }
  .nav-burger { display: flex; }
  .hero-h1 { font-size: clamp(38px, 10vw, 52px); }
  .form-row { grid-template-columns: 1fr; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
  .win-grid { grid-template-columns: 1fr; }
  .win-grid .wc:nth-child(n) {
    grid-column: 1;
    grid-row: auto;
    aspect-ratio: 4 / 3;
  }
  .section-hdr { flex-direction: column; align-items: flex-start; }
  .brands-row { gap: 20px 28px; }
  .brand-img { height: 20px; }
  .certs-inner { grid-template-columns: 1fr; gap: 32px; }
  .partner-grid { gap: 28px; }
  .footer-nav { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<!-- ============================================================
     NAV
============================================================ -->
<nav class="nav" id="siteNav">
  <div class="wrap">
    <div class="nav-inner">
      <a href="/" class="nav-logo">
        <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/686ad2b4b668ce59a9c25b0e_White.avif" alt="Deluxe Windows" width="140" height="38">
      </a>
      <div class="nav-links">
        <a href="/windows" class="nav-link">Windows</a>
        <a href="/doors" class="nav-link">Doors</a>
        <a href="/brand" class="nav-link">Brands</a>
        <a href="/about" class="nav-link">About</a>
      </div>
      <div class="nav-actions">
        <a href="tel:8887304144" class="nav-phone">(888) 730-4144</a>
        <a href="#contact" class="btn btn-accent">Get Quote</a>
      </div>
      <button class="nav-burger" id="burgerBtn" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<div class="nav-drawer" id="navDrawer">
  <a href="/windows" class="drawer-link">Windows</a>
  <a href="/doors" class="drawer-link">Doors</a>
  <a href="/brand" class="drawer-link">Brands</a>
  <a href="/about" class="drawer-link">About</a>
  <a href="tel:8887304144" class="drawer-link">(888) 730-4144</a>
  <a href="#contact" class="btn btn-accent" style="margin-top:10px;justify-content:center;">Get Free Estimate</a>
</div>

<!-- ============================================================
     HERO
============================================================ -->
<section class="hero" id="contact">
  <div class="hero-bg">
    <video autoplay loop muted playsinline>
      <source src="https://s3.amazonaws.com/webflow-prod-assets/6841ddf8ace3d9d9facb14fd/687ca10e41cc245f5cdacfd5_0719_2%20copy.mp4" type="video/mp4">
    </video>
    <img class="hero-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69ce36fd76a6aaff9c68df7e_01.webp" alt="Bay Area window installation" loading="eager">
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-body wrap">
    <div class="hero-grid">
      <div class="hero-left">
        <div class="hero-badge">
          <span class="hero-badge-dot"></span>
          Bay Area's Choice for 30+ Years
        </div>
        <h1 class="hero-h1">Replace Your<br>Windows.<br><em>Transform</em><br>Your Home.</h1>
        <p class="hero-offer">
          <strong>40% off</strong> windows and doors — offer ends 7/31/26
        </p>
        <div class="hero-trust">
          <span class="hero-trust-item"><span class="hero-trust-check">✓</span>AAMA Certified Installers</span>
          <span class="hero-trust-item"><span class="hero-trust-check">✓</span>Financing Available</span>
          <span class="hero-trust-item"><span class="hero-trust-check">✓</span>100% Employee Owned</span>
        </div>
        <div class="hero-social">
          <span class="hero-stars">★★★★★</span>
          <span>4.9 from 231 verified reviews</span>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-form-card">
          <div class="form-heading">Request a Free Estimate</div>
          <div class="form-sub">No commitment. We'll reach out within 1 business day.</div>
          <form method="get" action="#">
            <div class="form-row">
              <div class="form-field">
                <label class="form-label" for="f-name">Full name</label>
                <input class="form-control" type="text" id="f-name" name="Name" placeholder="Jane Smith" required>
              </div>
              <div class="form-field">
                <label class="form-label" for="f-phone">Phone</label>
                <input class="form-control" type="tel" id="f-phone" name="Phone" placeholder="(650) 461-4446" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label" for="f-email">Email</label>
                <input class="form-control" type="email" id="f-email" name="Email" placeholder="you@email.com" required>
              </div>
              <div class="form-field">
                <label class="form-label" for="f-city">City</label>
                <input class="form-control" type="text" id="f-city" name="Subject" placeholder="San Francisco" required>
              </div>
            </div>
            <div class="form-row solo">
              <div class="form-field">
                <label class="form-label" for="f-msg">Description</label>
                <textarea class="form-control" id="f-msg" name="Message" placeholder="Tell us about your project..." required></textarea>
              </div>
            </div>
            <button type="submit" class="btn btn-accent form-submit">Request a Free Estimate</button>
          </form>
          <p class="form-fine">*Windows Replacement. Offer Expires 07/31/26</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     STATS
============================================================ -->
<section class="stats">
  <div class="wrap">
    <div class="stats-grid">
      <div class="stat fade-in">
        <span class="stat-stars">★★★★★</span>
        <div class="stat-num">4.9</div>
        <div class="stat-label">Average Rating</div>
      </div>
      <div class="stat fade-in" style="transition-delay:80ms">
        <div class="stat-num">231<sup>+</sup></div>
        <div class="stat-label">Customer Reviews</div>
      </div>
      <div class="stat fade-in" style="transition-delay:160ms">
        <div class="stat-num">30<sup>+</sup></div>
        <div class="stat-label">Years in Business</div>
      </div>
      <div class="stat fade-in" style="transition-delay:240ms">
        <div class="stat-num">100%</div>
        <div class="stat-label">Employee Owned</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     BRANDS
============================================================ -->
<div class="brands">
  <div class="wrap">
    <div class="brands-row">
      <span class="brands-label">Authorized dealer of</span>
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aaca08003de3e1e57018_marvin-logo-black.svg" alt="Marvin">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aaea85f921adbca8a4e7_milgard.svg" alt="Milgard">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915c80af96503367881f15f_anlin2.svg" alt="Anlin">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aa60264a3c99f69524c6_jv.svg" alt="Jeld-Wen">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg" alt="Andersen">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915b390bad100b6e6176ea7_westerngroup.svg" alt="Western Window Systems">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aa3a24afaaa0a93dd455_Simonton_PrimaryLogo_Inline_RGB_Gradient_0822-1-2048x427.avif" alt="Simonton" style="height:18px;">
      <img class="brand-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6915aa80238022f9197f6973_pl.svg" alt="Ply Gem">
    </div>
  </div>
</div>

<!-- ============================================================
     WINDOWS
============================================================ -->
<section class="windows-section">
  <div class="wrap">
    <div class="section-hdr">
      <div>
        <span class="eyebrow">Products</span>
        <h2 class="section-h">Discover Different<br>Window Options</h2>
      </div>
      <a href="/windows" class="btn btn-dark">See all windows</a>
    </div>
    <div class="win-grid">
      <a href="/windows/aluminum-clad-windows" class="wc fade-in">
        <img class="wc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/695e00a375e654f6496bc79b_imgi_78_PGW_Interior_LivingRoom_MIRA_AwningWindow_Black_InspirationGallery_Module_6_1696x610_p32.avif" alt="Aluminum Clad Windows" loading="lazy">
        <div class="wc-shade"></div>
        <div class="wc-body">
          <div class="wc-title">Aluminum Clad Windows</div>
          <div class="wc-desc">Durable exterior. Beautiful interior. Precision engineering.</div>
          <span class="wc-arrow">Explore options →</span>
        </div>
      </a>
      <a href="/windows/vinyl-windows" class="wc fade-in" style="transition-delay:80ms">
        <img class="wc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/684d89095fe85b113c1b5224_Frame%208.avif" alt="Vinyl Windows" loading="lazy">
        <div class="wc-shade"></div>
        <div class="wc-body">
          <div class="wc-title">Vinyl Windows</div>
          <div class="wc-desc">Low-maintenance PVC frames.</div>
          <span class="wc-arrow">Explore options →</span>
        </div>
      </a>
      <a href="/windows/wood-clad-windows" class="wc fade-in" style="transition-delay:160ms">
        <img class="wc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/687e2bbbcf84c63258838bc4_homeguide-marvin-signature-windows-and-doors.avif" alt="Wood Clad Windows" loading="lazy">
        <div class="wc-shade"></div>
        <div class="wc-body">
          <div class="wc-title">Wood Clad Windows</div>
          <div class="wc-desc">Natural wood warmth, durable exterior.</div>
          <span class="wc-arrow">Explore options →</span>
        </div>
      </a>
      <a href="/windows/fiberglass-windows" class="wc fade-in" style="transition-delay:240ms">
        <img class="wc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/684d8fd91fff20336f9a857c_Frame%2029.avif" alt="Fiberglass Windows" loading="lazy">
        <div class="wc-shade"></div>
        <div class="wc-body">
          <div class="wc-title">Fiberglass Windows</div>
          <div class="wc-desc">Strong, efficient, and pairs beautifully with wood interiors.</div>
          <span class="wc-arrow">Explore options →</span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     DOORS
============================================================ -->
<section class="doors-section">
  <div class="wrap">
    <span class="eyebrow">Doors</span>
    <h2 class="section-h">High-Quality Doors<br>for Every Home</h2>
    <div class="door-grid">
      <a href="/doors/vinyl-doors" class="dc fade-in">
        <img class="dc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/6862d4e603255742b1319d0f_Frame%2048.avif" alt="Vinyl Doors" loading="lazy">
        <div class="dc-shade"></div>
        <div class="dc-body"><div class="dc-title">Vinyl Doors</div></div>
      </a>
      <a href="/doors/wood-clad-doors" class="dc fade-in" style="transition-delay:80ms">
        <img class="dc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/687e2bbbcf84c63258838bc4_homeguide-marvin-signature-windows-and-doors.avif" alt="Wood Clad Doors" loading="lazy">
        <div class="dc-shade"></div>
        <div class="dc-body"><div class="dc-title">Wood Clad Doors</div></div>
      </a>
      <a href="/doors/fiberglass-doors" class="dc fade-in" style="transition-delay:160ms">
        <img class="dc-img" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/684e94a86602a96b9775c003_Frame%2048.avif" alt="Fiberglass Doors" loading="lazy">
        <div class="dc-shade"></div>
        <div class="dc-body"><div class="dc-title">Fiberglass Doors</div></div>
      </a>
    </div>
    <div class="see-all-row">
      <a href="/doors" class="btn btn-dark">See all doors</a>
    </div>
  </div>
</section>

<!-- ============================================================
     GUARANTEE
============================================================ -->
<section class="guarantee">
  <div class="wrap">
    <div class="guar-hdr fade-in">
      <img class="guar-icon" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69986ae97432d22237832ac2_guarantee-icon.svg" alt="">
      <h2 class="guar-h2">Our Guarantee</h2>
    </div>
    <div class="guar-grid">
      <div class="guar-card fade-in">
        <div class="guar-term">Lifetime</div>
        <div class="guar-type">Vinyl Windows</div>
        <div class="guar-desc">Full lifetime transferable warranty on parts and labor.</div>
      </div>
      <div class="guar-card fade-in" style="transition-delay:80ms">
        <div class="guar-term">20 Year</div>
        <div class="guar-type">Aluminum, Wood Clad, Fiberglass</div>
        <div class="guar-desc">20 year warranty on glass for aluminum, wood clad, and fiberglass windows.</div>
      </div>
      <div class="guar-card fade-in" style="transition-delay:160ms">
        <div class="guar-term">10 Year</div>
        <div class="guar-type">All Other Parts</div>
        <div class="guar-desc">10 year warranty covering all remaining components and hardware.</div>
      </div>
    </div>
    <p class="guar-footnote">**Manufacturer's warranty on glass and frame — lifetime. Terms and conditions apply.</p>
  </div>
</section>

<!-- ============================================================
     CERTIFICATIONS
============================================================ -->
<section class="certs">
  <div class="wrap">
    <div class="certs-inner">
      <div class="fade-in">
        <img class="cert-icon" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69986e6eb764fe1619060131_achievement-award-medal-icon.svg" alt="">
        <h2 class="cert-h2">Our Certifications</h2>
        <p class="cert-desc">Factory trained and certified installers, AAMA certified. We are an authorized dealer for the brands Bay Area homeowners trust most.</p>
      </div>
      <div class="cert-logos fade-in" style="transition-delay:100ms">
        <img class="cert-logo" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/6998617839debbabce241e8e_6915aaca08003de3e1e57018_marvin-logo-black.svg" alt="Marvin Certified" style="height:44px;">
        <img class="cert-logo" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69200ccf66431025ccaabea5_milgard.svg" alt="Milgard Certified" style="height:34px;">
        <img class="cert-logo" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69986178667bcb1013476512_6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg" alt="Andersen Certified" style="height:40px;">
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     TRUSTED PARTNER
============================================================ -->
<section class="partner">
  <div class="wrap">
    <div class="partner-grid">
      <div class="fade-in">
        <span class="eyebrow">Who We Serve</span>
        <h2 class="partner-h2">Your Trusted Partner in Professional Window Solutions</h2>
        <p class="partner-p">Serving Contractors, Property Managers, and Architects with Turnkey Services, Quality Products, and Peace of Mind from Start to Finish.</p>
        <a href="/financing" class="btn btn-dark">Learn More</a>
      </div>
      <div class="partner-items">
        <div class="pi fade-in" style="transition-delay:80ms">
          <div class="pi-hd"><span class="pi-dot"></span>For Architects</div>
          <p class="pi-p">As a trusted turnkey provider, we manage every stage in-house, delivering consistent, efficient results across multiple large-scale projects.</p>
        </div>
        <div class="pi fade-in" style="transition-delay:160ms">
          <div class="pi-hd"><span class="pi-dot"></span>For Contractors</div>
          <p class="pi-p">We deliver turnkey window and glazing solutions with proven expertise in replacements for remodeling projects, backed by long-term guarantees and the capacity to efficiently manage multiple jobs at once.</p>
        </div>
        <div class="pi fade-in" style="transition-delay:240ms">
          <div class="pi-hd"><span class="pi-dot"></span>For Property Managers &amp; Owners</div>
          <p class="pi-p">A stress-free, all-in-one service that fits your schedule and budget, efficiently managing multiple jobs to simplify your toughest tasks.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     REVIEWS
============================================================ -->
<section class="reviews">
  <div class="wrap">
    <h2 class="sec-heading-lg fade-in">Reviews</h2>
    <script src="https://elfsightcdn.com/platform.js" async></script>
    <div class="elfsight-app-b6b258cb-48f2-4f37-a4c4-f938938bbe24" data-elfsight-app-lazy></div>
  </div>
</section>

<!-- ============================================================
     MAP / PREVIOUS JOBS
============================================================ -->
<section class="map-sec">
  <div class="wrap">
    <h2 class="sec-heading-lg fade-in">Our Previous Jobs</h2>
    <div class="elfsight-app-52cd283b-2339-4964-ade7-7ada818548f7" data-elfsight-app-lazy></div>
  </div>
</section>

<!-- ============================================================
     CTA
============================================================ -->
<section class="cta-sec">
  <div class="wrap">
    <div class="cta-grid">
      <div class="fade-in">
        <span class="eyebrow eyebrow-light">Get Started</span>
        <h2 class="cta-h2">Your Dream Home<br>Starts Here</h2>
        <p class="cta-p">Tell us about your project — we'll take care of the rest.</p>
        <a href="#contact" class="btn btn-accent">Free Consultation</a>
      </div>
      <div class="cta-img fade-in" style="transition-delay:120ms">
        <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/687ca4b70b8583ef4890bad4_iPad.avif" alt="Deluxe Windows on device" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FAQ
============================================================ -->
<section class="faq">
  <div class="wrap">
    <div class="faq-grid">
      <div class="fade-in">
        <h2 class="faq-h2">Do You Have Any Question?</h2>
        <p class="faq-contact">Call us at <a href="tel:6504614446">(650) 461-4446</a> to ask your questions.</p>
      </div>
      <div>
        <details class="faq-item" open>
          <summary>
            <span class="faq-q">Which material is best for your windows?</span>
            <span class="faq-plus" aria-hidden="true">+</span>
          </summary>
          <div class="faq-ans">
            <p>The best window material depends on your home's style, climate, energy efficiency needs, and budget. We offer a variety of options like vinyl, wood, aluminum, and fiberglass — each with its own benefits. Contact us today for a personalized consultation.</p>
          </div>
        </details>
        <details class="faq-item">
          <summary>
            <span class="faq-q">Is consultation for free?</span>
            <span class="faq-plus" aria-hidden="true">+</span>
          </summary>
          <div class="faq-ans">
            <p>To get a free consultation, please fill out the <a href="#contact">form above</a>. No commitment required.</p>
          </div>
        </details>
        <details class="faq-item">
          <summary>
            <span class="faq-q">When do I need new windows?</span>
            <span class="faq-plus" aria-hidden="true">+</span>
          </summary>
          <div class="faq-ans">
            <p>If you aren't sure whether your windows need replacing, Deluxe Windows, Inc. can come to your home for a free consultation to assess and advise.</p>
          </div>
        </details>
        <details class="faq-item">
          <summary>
            <span class="faq-q">How to choose window brands and styles?</span>
            <span class="faq-plus" aria-hidden="true">+</span>
          </summary>
          <div class="faq-ans">
            <p>This can only be determined once we visit your home for a free consultation. Every home is different. Our specialist will factor in all aspects to suggest which product, style, and price range works best for you.</p>
          </div>
        </details>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="footer">
  <div class="wrap">
    <div class="footer-main">
      <div>
        <img class="footer-logo" src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/686ad2b4b668ce59a9c25b0e_White.avif" alt="Deluxe Windows">
        <div class="footer-cta">Need Help?<br>Call Us Now</div>
        <a href="tel:6504614446" class="footer-phone-link">(650) 461-4446</a>
        <div class="footer-social">
          <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none"/></svg>
          </a>
        </div>
      </div>
      <nav class="footer-nav" aria-label="Footer navigation">
        <div class="footer-col">
          <ul>
            <li><a href="/about" class="footer-col-link">About Us</a></li>
            <li><a href="/windows" class="footer-col-link">Windows</a></li>
            <li><a href="/doors" class="footer-col-link">Doors</a></li>
            <li><a href="/brand" class="footer-col-link">Brands</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <ul>
            <li><a href="/glossary" class="footer-col-link">Glossary</a></li>
            <li><a href="/contacts" class="footer-col-link">Contact Us</a></li>
            <li><a href="/testimonials" class="footer-col-link">Testimonials</a></li>
            <li><a href="/financing" class="footer-col-link">Financing</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <ul>
            <li><a href="/faq" class="footer-col-link">FAQs</a></li>
            <li><a href="/privacy-policy" class="footer-col-link">Privacy Policy</a></li>
            <li><a href="/terms" class="footer-col-link">Terms of Use</a></li>
          </ul>
        </div>
      </nav>
    </div>
    <div class="footer-bottom">
      <p class="footer-copy">©2026 Deluxe Windows, Inc. All rights reserved.</p>
    </div>
  </div>
</footer>

<script>
(function () {
  var nav = document.getElementById('siteNav');
  var burger = document.getElementById('burgerBtn');
  var drawer = document.getElementById('navDrawer');

  window.addEventListener('scroll', function () {
    nav.classList.toggle('nav-solid', window.scrollY > 60);
  }, { passive: true });

  burger.addEventListener('click', function () {
    drawer.classList.toggle('open');
  });

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.fade-in').forEach(function (el) {
    io.observe(el);
  });
}());
</script>

</body>
</html>
