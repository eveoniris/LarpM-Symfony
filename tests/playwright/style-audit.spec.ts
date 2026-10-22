import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

/**
 * Audit de style de l'interface bêta : repère les pages où du texte est posé directement sur la photo de fond
 * (aucun ancêtre n'a de fond suffisamment opaque : alpha >= 0,45 ou image de fond).
 *
 * Prérequis : site lancé (BASE_URL, http://localhost par défaut ici), compte de test avec l'interface bêta active
 * (TEST_USER / TEST_PASSWORD, voir auth.setup.ts). Liste des pages : style-audit-urls.json (routes GET sans paramètre),
 * à régénérer avec `php bin/console debug:router --format=json` quand des routes sont ajoutées.
 *
 * Lancer : BASE_URL=http://localhost TEST_USER=test@test.com TEST_PASSWORD=azerty npx playwright test style-audit --project=desktop
 */
const urls: string[] = JSON.parse(fs.readFileSync(path.join(__dirname, 'style-audit-urls.json'), 'utf-8'));

// Pages connues et acceptées (texte volontairement sans cadre) : à tenir à jour.
const ACCEPTED: string[] = [];


for (const url of urls) {
    test(`texte sur fond de page : ${url}`, async ({ page }) => {
        test.setTimeout(120_000);
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90_000 });
        test.skip((await page.locator('html').getAttribute('data-ui')) !== 'beta', "compte sans interface bêta : page ignorée");

        const result = await page.evaluate(() => {
            const SKIP = '.navbar,footer,.offcanvas,.sf-toolbarreset,[id^=sfwdt],.sf-minitoolbar,script,style,.dropdown-menu';
            const alpha = (c: string): number => {
                if (c === 'transparent') { return 0; }
                if (c.startsWith('color')) { const m = c.match(/\/\s*([\d.]+)/); return m ? parseFloat(m[1]) : 1; }
                const m = c.match(/[\d.]+/g);
                return c.startsWith('rgba') && m && m.length > 3 ? parseFloat(m[3]) : 1;
            };
            const covered = (el: Element): boolean => {
                let p: Element | null = el;
                while (p && p !== document.body && p !== document.documentElement) {
                    const cs = getComputedStyle(p);
                    if (alpha(cs.backgroundColor) >= 0.45 || cs.backgroundImage !== 'none') { return true; }
                    p = p.parentElement;
                }
                return false;
            };
            let naked = 0;
            const samples: string[] = [];
            document.body.querySelectorAll('p,li,td,th,div,span,h1,h2,h3,h4,h5,h6,a,label,dd,dt,small,strong').forEach((el) => {
                if (el.closest(SKIP)) { return; }
                const own = Array.from(el.childNodes).filter((n) => n.nodeType === 3).map((n) => n.textContent ?? '').join('').trim();
                if (own.length < 25 || covered(el)) { return; }
                naked++;
                if (samples.length < 3) { samples.push(`${el.tagName.toLowerCase()}.${String(el.className).slice(0, 30)} | ${own.slice(0, 40)}`); }
            });
            return { naked, samples };
        });

        if (ACCEPTED.includes(url)) { test.skip(true, 'page acceptée'); }
        expect(result.naked, `${url} : ${result.naked} texte(s) posé(s) sur la photo, ex. ${result.samples.join(' ; ')}`).toBe(0);
    });
}
