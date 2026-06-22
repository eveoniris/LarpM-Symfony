import { test, expect, Page } from '@playwright/test';
import * as path from 'path';

/**
 * Vérification end-to-end des commits du 2026-06-19 :
 *  - 39ade950 feat(territoire) : édition des langues par le scénariste du groupe
 *  - b61cac3f feat(personnage) : choix/modification de la source d'une langue
 *  - 7ad4e749 fix : le staff est exempté de la limite de 3 personnages
 *
 * Les tests se connectent via le storageState (auth.setup). Ils se sautent
 * proprement (test.skip) quand la donnée nécessaire n'existe pas dans la base
 * locale, à l'image de ux-audit.spec.ts.
 */

const authFile = path.join(__dirname, '.auth/user.json');
test.use({ storageState: authFile });

/** Premier lien de la page correspondant au pattern (le pattern $-ancré sur un id
 *  numérique exclut les liens d'action comme /updateLangues, /delete…). */
async function getFirstDetailHref(page: Page, pattern: RegExp): Promise<string | null> {
    return page.evaluate((pat) => {
        const links = Array.from(document.querySelectorAll('a[href]')) as HTMLAnchorElement[];
        const found = links.find(a => new RegExp(pat).test(a.getAttribute('href') ?? ''));
        return found?.getAttribute('href') ?? null;
    }, pattern.source);
}

// ─────────────────────────────────────────────────────────────────────────────
// 39ade950 — Territoire : édition des langues
// ─────────────────────────────────────────────────────────────────────────────

test.describe('39ade950 — territoire : édition des langues', () => {
    test("la fiche territoire expose un bouton vers l'édition des langues", async ({ page }) => {
        await page.goto('/territoire');
        const href = await getFirstDetailHref(page, /^\/territoire\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        await expect(page.locator('a[href*="/updateLangues"]').first()).toBeVisible();
    });

    test('la page updateLangues affiche le formulaire (langue principale + langues parlées)', async ({ page }) => {
        await page.goto('/territoire');
        const href = await getFirstDetailHref(page, /^\/territoire\/\d+$/);
        if (!href) test.skip();
        const id = href!.match(/(\d+)$/)![1];

        const resp = await page.goto(`/territoire/${id}/updateLangues`);
        expect(resp?.status(), 'updateLangues doit répondre sans erreur').toBeLessThan(400);

        await expect(page.getByRole('heading', { name: /Modifier les langues/i })).toBeVisible();
        await expect(page.locator('[name*="languePrincipale"]').first()).toBeVisible();
        await expect(page.locator('[name*="[langues]"]').first()).toBeVisible();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// b61cac3f — Personnage : source d'une langue
// ─────────────────────────────────────────────────────────────────────────────

test.describe('b61cac3f — personnage : source de langue', () => {
    test('la fiche affiche la source des langues et permet de la modifier', async ({ page }) => {
        await page.goto('/personnage/list');
        const href = await getFirstDetailHref(page, /^\/personnage\/\d+$/);
        if (!href) test.skip();
        await page.goto(href!);

        const editLink = page.locator('a[href*="/editLangue/"]');
        if (await editLink.count() === 0) test.skip(); // personnage sans langue

        await editLink.first().click();

        const select = page.locator('select[name$="[source]"]');
        await expect(select).toBeVisible();

        // Le menu déroulant doit proposer les libellés de l'enum LangueSourceType.
        const options = await select.locator('option').allInnerTexts();
        expect(options).toEqual(
            expect.arrayContaining(['Origine', 'Groupe', 'Littérature']),
        );
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// 7ad4e749 — Staff exempté de la limite de personnages
// ─────────────────────────────────────────────────────────────────────────────

test.describe('7ad4e749 — staff exempté de la limite de personnages', () => {
    // La logique d'exemption (isStaff/canCreatePersonnage) est couverte finement
    // par PersonnageServiceStaffTest. Ici, simple smoke : un membre du staff
    // (utilisateur authentifié pour la session) atteint bien la création.
    test('le staff accède à la création de personnage', async ({ page }) => {
        const resp = await page.goto('/personnage/admin/add');
        expect(resp?.status(), 'le staff doit pouvoir accéder à la création').toBeLessThan(400);
        await expect(page).not.toHaveURL(/\/login/);
    });
});
