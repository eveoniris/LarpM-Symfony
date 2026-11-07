import { test, expect, Browser, Page } from '@playwright/test';

/**
 * Rulebook (bêta), contre la base locale de développement.
 *
 * Comptes (mot de passe `azerty`) :
 * - test@test.com : super admin, donc bêta + équipe règle (hérite de ROLE_REGLE) ;
 * - scenariste@test.com : doit avoir ROLE_BETA_TEST en plus de ROLE_SCENARISTE (à ajouter en base locale).
 *
 * Données attendues : règles importées par `app:rulebook:importer`, TERR-01 non publiée, TERR-02 publiée avec une version claire.
 * Lancer : BASE_URL=http://localhost TEST_USER=test@test.com TEST_PASSWORD=azerty npx playwright test rulebook
 */
test.use({ storageState: { cookies: [], origins: [] } });

async function connecter(browser: Browser, email: string): Promise<Page> {
    const context = await browser.newContext({ ...test.info().project.use, storageState: undefined });
    const page = await context.newPage();
    await page.goto('/login');
    await page.fill('input[name="username"]', email);
    await page.fill('input[name="password"]', 'azerty');
    await page.click('button[type="submit"], input[type="submit"]');
    await page.waitForURL((url) => !url.toString().includes('/login'));

    return page;
}

async function sansDebordement(page: Page): Promise<void> {
    const debordement = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(debordement, 'pas de défilement horizontal de la page').toBeLessThanOrEqual(1);
}

test.describe('équipe règle', () => {
    test('liste complète, statuts et filtre', async ({ browser }, testInfo) => {
        const page = await connecter(browser, 'test@test.com');
        await page.goto('/rulebook');

        await expect(page.getByRole('heading', { name: 'Rulebook', level: 1 })).toBeVisible();
        await expect(page.locator('select[name="statut"]')).toBeVisible();
        expect(await page.locator('tbody tr').count()).toBeGreaterThanOrEqual(20);
        await sansDebordement(page);
        await page.screenshot({ path: testInfo.outputPath('rulebook-equipe-liste.png'), fullPage: true });

        await page.selectOption('select[name="statut"]', 'todo');
        await page.getByRole('button', { name: 'Filtrer' }).click();
        await expect(page.getByText('TERR-01')).toBeVisible();
        await expect(page.getByText('TERR-02')).toHaveCount(0);
    });

    test('détail technique et tests visibles', async ({ browser }, testInfo) => {
        const page = await connecter(browser, 'test@test.com');
        await page.goto('/rulebook/regle/TERR-01');

        await expect(page.getByRole('heading', { name: 'Détail technique' })).toBeVisible();
        // Le détail importé contient sa propre section « Tests » : elle n'est pas répétée dans un second bloc.
        await expect(page.getByRole('heading', { name: 'Tests', exact: true })).toHaveCount(1);
        await sansDebordement(page);
        await page.screenshot({ path: testInfo.outputPath('rulebook-equipe-regle.png'), fullPage: true });
    });

    test('éditeur markdown chargé, aperçu rendu par le serveur, sans enregistrer', async ({ browser }, testInfo) => {
        const page = await connecter(browser, 'test@test.com');
        const erreurs: string[] = [];
        page.on('pageerror', (e) => erreurs.push(e.message));
        test.setTimeout(120_000);
        await page.goto('/rulebook/regle/TERR-02/editer', { timeout: 90_000 });

        await expect(page.locator('.EasyMDEContainer')).toHaveCount(2);
        await sansDebordement(page);

        const zone = page.locator('.EasyMDEContainer').first();
        await zone.locator('.CodeMirror').click();
        await page.keyboard.type('Texte **en gras** de test');
        await zone.locator('button.preview').click();
        await expect(zone.locator('.editor-preview-active strong', { hasText: 'en gras' })).toHaveCount(1);
        await page.screenshot({ path: testInfo.outputPath('rulebook-editeur.png'), fullPage: true });
        expect(erreurs).toEqual([]);
    });

    test('recherche dans le détail technique', async ({ browser }) => {
        const page = await connecter(browser, 'test@test.com');
        await page.goto('/rulebook?q=ceil');

        await expect(page.getByText('TERR-01')).toBeVisible();
    });
});

test.describe('scénariste', () => {
    test('ne voit que les règles publiées, sans statut ni détail technique', async ({ browser }, testInfo) => {
        const page = await connecter(browser, 'scenariste@test.com');
        await page.goto('/rulebook');

        await expect(page.getByRole('heading', { name: 'Rulebook', level: 1 })).toBeVisible();
        await expect(page.locator('select[name="statut"]')).toHaveCount(0);
        await expect(page.getByText('TERR-02')).toBeVisible();
        await expect(page.getByText('TERR-01')).toHaveCount(0);
        await sansDebordement(page);
        await page.screenshot({ path: testInfo.outputPath('rulebook-scenariste-liste.png'), fullPage: true });

        await page.goto('/rulebook/regle/TERR-02');
        await expect(page.getByText('moitie, arrondie au superieur')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Détail technique' })).toHaveCount(0);
        await sansDebordement(page);
        await page.screenshot({ path: testInfo.outputPath('rulebook-scenariste-regle.png'), fullPage: true });
    });

    test('une règle non publiée répond 404', async ({ browser }) => {
        const page = await connecter(browser, 'scenariste@test.com');
        const reponse = await page.goto('/rulebook/regle/TERR-01');

        expect(reponse?.status()).toBe(404);
    });
});
