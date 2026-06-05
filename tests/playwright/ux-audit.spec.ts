import { test, expect, Page } from '@playwright/test';
import * as path from 'path';

const authFile = path.join(__dirname, '.auth/user.json');

test.use({ storageState: authFile });

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/** Vérifie le scroll horizontal de la page.
 *  Compare scrollWidth au vrai viewport (innerWidth), pas au clientWidth
 *  qui exclut le scrollbar vertical et génère de faux positifs. */
async function hasHorizontalOverflow(page: Page): Promise<boolean> {
    return page.evaluate(() => {
        // window.innerWidth = largeur réelle du viewport (scrollbar vertical inclus)
        // scrollWidth = largeur du contenu dépassant le viewport
        return document.documentElement.scrollWidth > window.innerWidth + 5;
    });
}

/** Trouve le premier lien de détail dans une table (exclu les liens de tri d'entête) */
async function getFirstDetailHref(page: Page, pattern: RegExp): Promise<string | null> {
    return page.evaluate((pat) => {
        const links = Array.from(document.querySelectorAll('table tbody a[href]')) as HTMLAnchorElement[];
        const found = links.find(a => new RegExp(pat).test(a.getAttribute('href') ?? ''));
        return found?.getAttribute('href') ?? null;
    }, pattern.source);
}

async function assertNoClass(page: Page, selector: string, errorMsg: string) {
    const count = await page.locator(selector).count();
    expect(count, errorMsg).toBe(0);
}

// ─────────────────────────────────────────────────────────────────────────────
// P1 — table-responsive (aucune table sans wrapper)
// ─────────────────────────────────────────────────────────────────────────────

test.describe('P1 — table-responsive', () => {
    const pages = [
        '/personnage/list',
        '/user/list',
        '/territoire',
        '/gn',
    ];

    for (const url of pages) {
        test(`${url} — aucune table sans .table-responsive`, async ({ page }) => {
            await page.goto(url);
            const tablesWithoutWrapper = await page.evaluate(() =>
                Array.from(document.querySelectorAll('table.table'))
                    .filter(t => !t.closest('.table-responsive')).length
            );
            expect(tablesWithoutWrapper, `tables sans .table-responsive sur ${url}`).toBe(0);
        });

        test(`${url} — pas de débordement horizontal (mobile 375px)`, async ({ page, isMobile }) => {
            if (!isMobile) test.skip();
            await page.goto(url);
            expect(await hasHorizontalOverflow(page)).toBe(false);
        });
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// P2 — plus de .table-condensed (Bootstrap 3 legacy)
// ─────────────────────────────────────────────────────────────────────────────

test.describe('P2 — suppression table-condensed', () => {
    const pages = ['/gn', '/territoire', '/personnage/list', '/user/list'];

    for (const url of pages) {
        test(`${url} — aucune classe table-condensed`, async ({ page }) => {
            await page.goto(url);
            await assertNoClass(page, '.table-condensed', `table-condensed présent sur ${url}`);
        });
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// P3 — toolbar groupe/detail avec flex-wrap
// ─────────────────────────────────────────────────────────────────────────────

test.describe('P3 — toolbar groupe/detail flex-wrap', () => {
    test('groupe/detail — toolbar a flex-wrap', async ({ page }) => {
        await page.goto('/groupe');
        // On cherche un lien de détail (ex: /groupe/2) dans le tbody
        const href = await getFirstDetailHref(page, /^\/groupe\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);
        await expect(page.locator('.btn-toolbar').first()).toHaveClass(/flex-wrap/);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// P4 — plus de .pull-right / .pull-left (Bootstrap 3 legacy)
// ─────────────────────────────────────────────────────────────────────────────

test.describe('P4 — suppression pull-right/pull-left', () => {
    const pages = ['/personnage/list', '/groupe', '/gn', '/territoire', '/user/list'];

    for (const url of pages) {
        test(`${url} — aucune classe pull-right ou pull-left`, async ({ page }) => {
            await page.goto(url);
            await assertNoClass(page, '.pull-right, .pull-left', `pull-right/left présent sur ${url}`);
        });
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// P5 — footer avec flex-wrap
// ─────────────────────────────────────────────────────────────────────────────

test.describe('P5 — footer flex-wrap', () => {
    test('footer a la classe flex-wrap', async ({ page }) => {
        await page.goto('/');
        await expect(page.locator('footer')).toHaveClass(/flex-wrap/);
    });

    test('footer ne déborde pas à 320px', async ({ page }) => {
        await page.setViewportSize({ width: 320, height: 568 });
        await page.goto('/');
        expect(await hasHorizontalOverflow(page)).toBe(false);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// O1 — trombinoscope : bouton direct (pas de dropdown)
// ─────────────────────────────────────────────────────────────────────────────

test.describe('O1 — trombinoscope bouton direct', () => {
    test('trombinoscope utilise un bouton direct, pas un dropdown', async ({ page }) => {
        await page.goto('/personnage/list');
        const href = await getFirstDetailHref(page, /^\/personnage\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        // Le bloc trombinoscope ne doit pas contenir de dropdown
        const dropdownCount = await page.locator('.thumbnail ~ .caption .dropdown').count();
        expect(dropdownCount, 'dropdown présent dans trombinoscope').toBe(0);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// O2 — labels mobiles sur boutons icônes de la fiche personnage
// ─────────────────────────────────────────────────────────────────────────────

test.describe('O2 — labels mobiles fiche personnage', () => {
    test('les spans de label mobile sont présents dans le DOM', async ({ page }) => {
        await page.goto('/personnage/list');
        const href = await getFirstDetailHref(page, /^\/personnage\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        const mobileLabels = page.locator('.personnage-actions .d-block.d-md-none');
        const count = await mobileLabels.count();
        expect(count, 'aucun label mobile dans les boutons personnage').toBeGreaterThan(0);
    });

    test('les labels mobiles sont visibles à 375px', async ({ page, isMobile }) => {
        if (!isMobile) test.skip();
        await page.goto('/personnage/list');
        const href = await getFirstDetailHref(page, /^\/personnage\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        const firstLabel = page.locator('.personnage-actions .d-block.d-md-none').first();
        await expect(firstLabel).toBeVisible();
    });

    test('les labels mobiles sont masqués en desktop (≥768px)', async ({ page, isMobile }) => {
        if (isMobile) test.skip();
        await page.goto('/personnage/list');
        const href = await getFirstDetailHref(page, /^\/personnage\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        const firstLabel = page.locator('.personnage-actions .d-block.d-md-none').first();
        await expect(firstLabel).toBeHidden();
    });
});
