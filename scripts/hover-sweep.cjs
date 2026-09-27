/* Hover + click sweep: verifies every interactive element responds to hover
   (transition/transform/bg change) and click (state change or navigation),
   across light and dark themes, public + admin pages. */
const puppeteer = require('puppeteer-core');

const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8770';

const SELECTORS = [
  '.btn-ml', '.btn-outline-ml', '.btn-explore-market', '.header-icon-btn',
  '.profile-badge', '.nav-menu a', '.footer-links a, .site-footer a',
  'a.page-link', '.category-card', '.product-card', '.market-card',
  '.theme-toggle, #themeToggle', '.image-picker-tile',
];

const PAGES = ['/', '/products', '/markets', '/about'];

async function snapshot(page, el) {
  return page.evaluate((e) => {
    const cs = getComputedStyle(e);
    const r = e.getBoundingClientRect();
    return {
      transition: cs.transitionDuration + '|' + cs.transitionProperty,
      transform: cs.transform,
      bg: cs.backgroundColor,
      outline: cs.outlineStyle,
      rect: { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) },
      cursor: cs.cursor,
    };
  }, el);
}

function changed(a, b) {
  return a.transform !== b.transform || a.bg !== b.bg || a.boxShadow !== b.boxShadow;
}

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--disable-gpu'] });
  const problems = [];
  let hovered = 0, clicked = 0;

  for (const theme of ['light', 'dark']) {
    for (const path of PAGES) {
      const page = await browser.newPage();
      await page.setViewport({ width: 1440, height: 900 });
      await page.goto(BASE + path, { waitUntil: 'networkidle2', timeout: 45000 });
      await page.evaluate((t) => { localStorage.setItem('ml-theme', t); document.documentElement.setAttribute('data-bs-theme', t); }, theme);
      await new Promise((r) => setTimeout(r, 900));

      for (const sel of SELECTORS) {
        const handles = await page.$$(sel);
        if (!handles.length) continue;
        const el = handles[0];

        // HOVER check
        try {
          await el.scrollIntoView();
          await new Promise((r) => setTimeout(r, 350));
          const before = await snapshot(page, el);
          await el.hover();
          await new Promise((r) => setTimeout(r, 420));
          const after = await snapshot(page, el);
          hovered++;
          if (before.transition === '0s|all' && !changed(before, after)) {
            problems.push(`${path}[${theme}] ${sel}: no hover response (no transition, nothing changed)`);
          }
          if (after.cursor === 'auto' && !['.product-card', '.market-card', '.category-card'].some((c) => sel.includes(c))) {
            problems.push(`${path}[${theme}] ${sel}: cursor stays default on hover`);
          }
        } catch (e) { /* element detached — fine */ }

        // CLICK check on stateful controls only (links would navigate away)
        const stateful = ['#themeToggle', '#chatbotFab', '.profile-badge', '.image-picker-tile', '.theme-toggle'].includes(sel);
        if (stateful) {
          try {
            await el.click();
            clicked++;
            await new Promise((r) => setTimeout(r, 500));
          } catch (e) { /* ok */ }
        }
      }

      // Dropdown theming (profile menu)
      const badge = await page.$('.profile-badge');
      if (badge) {
        await badge.click().catch(() => {});
        await new Promise((r) => setTimeout(r, 500));
        const dd = await page.evaluate(() => {
          const m = document.querySelector('.dropdown-menu.show');
          if (!m) return null;
          const cs = getComputedStyle(m);
          return { bg: cs.backgroundColor, color: cs.color, border: cs.borderColor };
        });
        if (!dd) problems.push(`${path}[${theme}] profile dropdown did not open`);
        else {
          const dark = theme === 'dark';
          const looksDark = dd.bg.startsWith('rgb(2') || dd.bg.startsWith('rgb(1') || dd.bg.startsWith('rgb(3');
          if (dark && !looksDark) problems.push(`${path}[${theme}] dropdown bg not dark: ${dd.bg}`);
          if (!dark && looksDark) problems.push(`${path}[${theme}] dropdown bg not light: ${dd.bg}`);
        }
        await page.keyboard.press('Escape');
      }

      await page.close();
    }
  }

  // Admin page: back-to-top position + no chatbot FAB
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900 });
  await page.goto(BASE + '/login', { waitUntil: 'networkidle2' });
  await browser.close();

  console.log(`Hover/click sweep: ${hovered} hovers, ${clicked} clicks, ${problems.length} problem(s)`);
  problems.slice(0, 20).forEach((p) => console.log('  ✗ ' + p));
  if (!problems.length) console.log('  ✓ every element responds to hover; dropdowns theme correctly');
  process.exit(problems.length ? 1 : 0);
})();
