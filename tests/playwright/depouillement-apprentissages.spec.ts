import { test, expect, Page } from '@playwright/test';
import * as path from 'path';

/**
 * Non-régression de la zone « Appris pendant le jeu » de l'étape Contenu d'un
 * dépouillement de joueur : la liste de chaque champ doit s'ouvrir, proposer
 * des choix, et le choix doit être conservé après enregistrement et rechargement.
 * Les tests se sautent proprement si la base locale n'a pas de dépouillement joueur.
 */

const authFile = path.join(__dirname, '.auth/user.json');
test.use({ storageState: authFile });

test.setTimeout(120_000);

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        const style = document.createElement('style');
        style.textContent = '.sf-toolbar, .sf-minitoolbar { display: none !important; }';
        document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
    });
});

/** URL de l'étape Contenu d'un dépouillement de joueur (surchargeable par DEPOUILLEMENT_URL). */
async function urlContenu(page: Page): Promise<string | null> {
    const url = process.env.DEPOUILLEMENT_URL ?? '/gn/7/depouillement/6/etape/contenu';
    const reponse = await page.goto(url);
    return reponse && reponse.ok() && (await page.locator('select[name$="[prieres][]"]').count()) > 0 ? url : null;
}

for (const champ of ['prieres', 'sorts', 'potions']) {
    test(`la liste « ${champ} » s'ouvre, se choisit et se conserve`, async ({ page }) => {
        const url = await urlContenu(page);
        test.skip(url === null, 'Aucun dépouillement joueur en base locale');

        const erreurs: string[] = [];
        page.on('pageerror', (e) => erreurs.push(e.message));

        await page.goto(url as string);
        const select = page.locator(`select[name$="[${champ}][]"], select[name$="[${champ}]"]`).first();
        await expect(select, `le select ${champ} existe`).toHaveCount(1);

        const controle = page.locator(`select[name$="[${champ}][]"] + .ts-wrapper .ts-control`).first();
        await expect(controle, 'le contrôle de liste est visible').toBeVisible();
        const boite = (await controle.boundingBox())!;
        await controle.click({ position: { x: boite.width - 15, y: boite.height / 2 } });

        const options = page.locator('.ts-dropdown:visible .option');
        await expect(options.first(), 'la liste propose des choix').toBeVisible();
        const libelle = (await options.first().innerText()).trim();
        await options.first().click();

        await page.locator('form button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');

        await page.goto(url as string);
        await expect(page.locator('.ts-wrapper .item', { hasText: libelle }).first()).toBeVisible();
        expect(erreurs, 'aucune erreur JavaScript').toEqual([]);
    });
}
