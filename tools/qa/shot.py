import sys, asyncio
from playwright.async_api import async_playwright

async def main():
    url = sys.argv[1]; out = sys.argv[2]; width = int(sys.argv[3]) if len(sys.argv)>3 else 1440
    full = (sys.argv[4] if len(sys.argv)>4 else 'top') == 'full'
    async with async_playwright() as p:
        b = await p.chromium.launch()
        pg = await b.new_page(viewport={'width':width,'height':1000}, device_scale_factor=1)
        errs=[]
        pg.on('console', lambda m: errs.append(m.text) if m.type=='error' else None)
        pg.on('pageerror', lambda e: errs.append('PAGEERROR '+str(e)))
        await pg.goto(url, wait_until='networkidle', timeout=60000)
        await pg.wait_for_timeout(800)
        if full:
            await pg.screenshot(path=out, full_page=True)
        else:
            await pg.locator('header.zc-header-bar, .zc-header-bar').first.screenshot(path=out) if await pg.locator('.zc-header-bar').count() else await pg.screenshot(path=out)
        print('ERRORS:', errs[:5])
        # geometry probe
        data = await pg.evaluate("""() => {
          const q = s => document.querySelector(s);
          const r = e => e ? e.getBoundingClientRect() : null;
          const pill=q('.zc-header-pill'), nav=q('.zc-nav-pill'), brand=q('.zc-brand'), cta=q('.zc-header-cta-btn .elementor-button'), tog=q('.zc-mobile-menu-toggle'), acc=q('.zc-header-account');
          return {pill: r(pill)&&[Math.round(r(pill).width), Math.round(r(pill).height)], nav: r(nav)&&[Math.round(r(nav).width),Math.round(r(nav).height)],
            brand: r(brand)&&[Math.round(r(brand).width),Math.round(r(brand).height)], acc: r(acc)&&[Math.round(r(acc).width),Math.round(r(acc).height)],
            cta: r(cta)&&[Math.round(r(cta).width),Math.round(r(cta).height)], tog: r(tog)&&[Math.round(r(tog).width),Math.round(r(tog).height)],
            pillPos: pill&&getComputedStyle(pill).position, barPos: q('.zc-header-bar')&&getComputedStyle(q('.zc-header-bar')).position,
            navDisplay: nav&&getComputedStyle(nav).display, navItems: document.querySelectorAll('.zc-nav-pill > li > a').length,
            scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth};
        }""")
        print('GEOM:', data)
        await b.close()

asyncio.run(main())
