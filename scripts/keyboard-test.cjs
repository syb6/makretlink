/* Keyboard-only navigation audit for MarketLink.
   Drives real Chrome key events (trusted) — checks tab order, focus-ring
   visibility, Enter activation, skip link, Esc focus return, and tab traps. */
const puppeteer = require('puppeteer-core');

const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8770';
const TAB_WAIT = 150; // ms — lets smooth scrolling settle before measuring

const PAGES = ['/', '/products', '/markets', '/login', '/about'];
const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 850, tabs: 90 },
  { name: 'mobile', width: 390, height: 844, tabs: 120 },
];

function hasRing(s) {
  // 'auto' = UA ring, width>0 custom ring, or a focus box-shadow
  return s.outlineStyle === 'auto' || (s.outlineWidth !== '0px' && s.outlineStyle !== 'none') || s.boxShadow;
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: 'new',
    args: ['--no-sandbox', '--disable-gpu'],
  });
  const problems = [];
  let checks = 0;

  for (const vp of VIEWPORTS) {
    for (const path of PAGES) {
      const isAuthPage = path === '/login';
      const page = await browser.newPage();
      await page.setViewport({ width: vp.width, height: vp.height });
      const jsErrors = [];
      page.on('pageerror', (e) => jsErrors.push(e.message));
      await page.goto(BASE + path, { waitUntil: 'networkidle2', timeout: 45000 });
      await new Promise((r) => setTimeout(r, 1200));

      // Walk the tab order
      const stops = [];
      let prev = null, repeats = 0, stoppedMoving = 0;
      for (let i = 0; i < vp.tabs; i++) {
        await page.keyboard.press('Tab');
        await new Promise((r) => setTimeout(r, TAB_WAIT));
        const stop = await page.evaluate(() => {
          const el = document.activeElement;
          if (!el || el === document.body) return null;
          const cs = getComputedStyle(el);
          const r = el.getBoundingClientRect();
          return {
            tag: el.tagName, id: el.id || '',
            cls: typeof el.className === 'string' ? el.className : '',
            text: (el.textContent || '').trim().slice(0, 22),
            outlineStyle: cs.outlineStyle, outlineWidth: cs.outlineWidth,
            boxShadow: cs.boxShadow !== 'none',
            visible: cs.visibility !== 'hidden' && cs.display !== 'none',
            inView: r.top >= 0 && r.top < innerHeight && r.left >= 0 && r.left < innerWidth,
          };
        });
        if (!stop) { if (++stoppedMoving > 3) break; continue; }
        stoppedMoving = 0;
        const key = stop.tag + stop.id + stop.cls + stop.text;
        if (prev === key) { if (++repeats > 5) { problems.push(`${path}[${vp.name}]: tab trap at ${key}`); break; } }
        else { repeats = 0; prev = key; }
        stops.push(stop);
      }

      const reachable = stops.filter(Boolean);
      checks++;

      // 1) Every interactive stop must show a visible focus indicator, in view
      const bad = reachable.filter((s) => s.visible && s.inView && !hasRing(s));
      if (bad.length) problems.push(`${path}[${vp.name}]: ${bad.length} stops lack a visible focus ring (e.g. ${bad[0].tag}.${(bad[0].cls || '').split(' ')[0]} "${bad[0].text}")`);

      // 2) Skip link must be the very first tab stop
      const first = reachable[0];
      checks++;
      if (!first || first.cls.indexOf('skip-link') === -1) problems.push(`${path}[${vp.name}]: first tab stop is not the skip link (${first ? first.tag + '.' + first.cls : 'none'})`);

      // 3) Must-reach controls
      const ids = new Set(reachable.map((s) => s.id));
      checks++;
      if (!ids.has('themeToggle')) problems.push(`${path}[${vp.name}]: theme toggle unreachable by keyboard`);
      if (!isAuthPage) {
        checks++;
        if (!ids.has('chatbotFab')) problems.push(`${path}[${vp.name}]: chat FAB unreachable by keyboard`);
        checks++;
        if (!ids.has('backToTop')) problems.push(`${path}[${vp.name}]: back-to-top unreachable (never shown/focused)`);
      }

      // 4) Enter activates the skip link -> focus lands in main content
      await page.evaluate(() => document.querySelector('.skip-link').focus());
      await page.keyboard.press('Enter');
      await new Promise((r) => setTimeout(r, 700));
      const skipWorked = await page.evaluate(() => {
        const el = document.activeElement;
        return el && (el.id === 'main' || document.getElementById('main').contains(el));
      });
      checks++;
      if (!skipWorked) problems.push(`${path}[${vp.name}]: skip link Enter does not move focus into #main`);

      // 5) Enter toggles theme
      const themeBefore = await page.evaluate(() => document.documentElement.getAttribute('data-bs-theme'));
      await page.evaluate(() => document.getElementById('themeToggle').focus());
      await page.keyboard.press('Enter');
      await new Promise((r) => setTimeout(r, 120));
      const themeAfter = await page.evaluate(() => document.documentElement.getAttribute('data-bs-theme'));
      checks++;
      if (themeBefore === themeAfter) problems.push(`${path}[${vp.name}]: Enter does not activate theme toggle`);

      // 6) Chat: Enter opens, typed input works, Esc closes + refocuses FAB
      if (!isAuthPage) {
        await page.evaluate(() => document.getElementById('chatbotFab').focus());
        await page.keyboard.press('Enter');
        await new Promise((r) => setTimeout(r, 200));
        const opened = await page.evaluate(() => document.getElementById('chatbotWindow').classList.contains('open'));
        checks++;
        if (!opened) problems.push(`${path}[${vp.name}]: Enter does not open chat from FAB`);
        await page.keyboard.type('hi');
        const typed = await page.evaluate(() => document.getElementById('chatbotInput').value);
        checks++;
        if (typed !== 'hi') problems.push(`${path}[${vp.name}]: chat input not keyboard-typable (got "${typed}")`);
        await page.keyboard.press('Escape');
        await new Promise((r) => setTimeout(r, 150));
        const closed = await page.evaluate(() => !document.getElementById('chatbotWindow').classList.contains('open'));
        const refocused = await page.evaluate(() => document.activeElement && document.activeElement.id === 'chatbotFab');
        checks++;
        if (!closed) problems.push(`${path}[${vp.name}]: Esc does not close chat`);
        checks++;
        if (!refocused) problems.push(`${path}[${vp.name}]: Esc does not return focus to FAB`);
      }

      if (jsErrors.length) problems.push(`${path}[${vp.name}]: JS errors: ${jsErrors[0]}`);
      await page.close();
    }
  }

  await browser.close();
  console.log(`Keyboard audit: ${checks} checks, ${problems.length} problem(s)`);
  problems.forEach((p) => console.log('  ✗ ' + p));
  if (!problems.length) console.log('  ✓ tab order, skip link, focus rings, Enter & Esc all behave');
  process.exit(problems.length ? 1 : 0);
})();
