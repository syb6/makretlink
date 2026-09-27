const puppeteer = require('puppeteer-core');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const BASE = 'http://127.0.0.1:8770';
const delay = (ms) => new Promise((r) => setTimeout(r, ms));

const USERS = [
  { role: 'customer', email: 'customer1@marketlink.test', path: '/customer/dashboard' },
  { role: 'farmer', email: 'farmer1@marketlink.test', path: '/farmer/dashboard' },
  { role: 'admin', email: 'admin@marketlink.test', path: '/admin/dashboard' },
];

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--disable-gpu'] });
  const problems = [];

  for (const u of USERS) {
    for (const theme of ['light', 'dark']) {
      const page = await browser.newPage();
      const jsErrors = [];
      page.on('pageerror', (e) => jsErrors.push(e.message));
      await page.setViewport({ width: 1440, height: 900 });
      await page.goto(BASE + '/login', { waitUntil: 'networkidle2' }).catch(() => {});
      // Still signed in from a previous role? Log out via CSRF-less POST form.
      if (!page.url().includes('/login')) {
        await page.evaluate(() => {
          const f = document.querySelector('form[action*="logout"]');
          if (f) f.submit();
        });
        await page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {});
        await page.goto(BASE + '/login', { waitUntil: 'networkidle2' });
      }
      await page.evaluate((t) => localStorage.setItem('ml-theme', t), theme);
      await page.type('#email', u.email);
      await page.type('#password', 'password');
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), page.click('button[type="submit"]')]);
      await delay(1000);

      const audit = await page.evaluate(() => {
        const h = document.documentElement;
        const overflow = Math.max(h.scrollWidth, document.body.scrollWidth) - innerWidth;
        const broken = [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.src && !i.src.includes('data:')).length;
        const ids = {};
        document.querySelectorAll('[id]').forEach((e) => { ids[e.id] = (ids[e.id] || 0) + 1; });
        const dups = Object.keys(ids).filter((k) => ids[k] > 1);
        return { theme: h.getAttribute('data-bs-theme'), overflow, broken, dups };
      });

      if (audit.overflow > 0) problems.push(`${u.role}[${theme}]: overflow ${audit.overflow}px`);
      if (audit.broken > 0) problems.push(`${u.role}[${theme}]: ${audit.broken} broken images`);
      if (audit.dups.length) problems.push(`${u.role}[${theme}]: duplicate ids ${audit.dups.join(',')}`);
      if (audit.theme !== theme) problems.push(`${u.role}[${theme}]: theme not applied`);
      if (jsErrors.length) problems.push(`${u.role}[${theme}]: JS error: ${jsErrors[0].slice(0, 80)}`);
      await page.close();
    }
  }

  await browser.close();
  console.log(`Dashboard audit: 6 runs, ${problems.length} problem(s)`);
  problems.forEach((p) => console.log('  ✗ ' + p));
  if (!problems.length) console.log('  ✓ customer, farmer, admin dashboards clean in both themes');
  process.exit(problems.length ? 1 : 0);
})();
