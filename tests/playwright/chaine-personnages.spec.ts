import { test, expect, Page } from '@playwright/test';
import * as path from 'path';

/**
 * Parcours end-to-end de la chaîne de personnages d'une participation :
 * principal -> substitution (si l'opus l'active) -> relève -> archétype de secours,
 * et de la bascule du personnage actif sur LarpManager.
 *
 * Comme les autres specs du projet, les tests se sautent proprement (test.skip)
 * quand la base locale ne contient pas la donnée nécessaire.
 */

const authFile = path.join(__dirname, '.auth/user.json');
test.use({ storageState: authFile });

/**
 * La barre de debug Symfony est ancrée en bas de l'écran et intercepte les clics
 * sur petit viewport. C'est un artefact de l'environnement de développement, pas
 * un défaut de l'interface : on la neutralise pour tous les tests du fichier.
 */
test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        const style = document.createElement('style');
        style.textContent = '.sf-toolbar, .sf-minitoolbar { display: none !important; }';
        document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
    });
});

/** Première participation de la base qui dispose d'un personnage principal. */
async function trouverParticipationAvecPersonnage(page: Page): Promise<string | null> {
    await page.goto('/gn');
    const gnHref = await premierLien(page, /^\/gn\/\d+$/);
    if (!gnHref) return null;

    await page.goto(`${gnHref}/participants`);
    const lignes = page.locator('tbody tr').filter({ has: page.locator('a[href^="/personnage/"]') });
    if ((await lignes.count()) === 0) return null;

    return lignes.first().locator('a[href^="/participant/"]').first().getAttribute('href');
}

async function premierLien(page: Page, pattern: RegExp): Promise<string | null> {
    return page.evaluate((pat) => {
        const liens = Array.from(document.querySelectorAll('a[href]')) as HTMLAnchorElement[];
        const trouve = liens.find(a => new RegExp(pat).test(a.getAttribute('href') ?? ''));
        return trouve?.getAttribute('href') ?? null;
    }, pattern.source);
}

// ─────────────────────────────────────────────────────────────────────────────
// Chaîne de personnages
// ─────────────────────────────────────────────────────────────────────────────

test.describe('chaîne de personnages', () => {
    test('le détail de participation affiche les rôles typés', async ({ page }) => {
        const href = await trouverParticipationAvecPersonnage(page);
        if (!href) test.skip();

        await page.goto(`${href!.replace(/\/$/, '')}`);

        const chaine = page.locator('ul.list-group.mb-3').filter({ hasText: 'Personnage principal' }).first();
        await expect(chaine).toBeVisible();
        await expect(chaine).toContainText('Personnage de relève');
        await expect(chaine).toContainText('Archétype de secours');
    });

    test('chaque rôle porte une info-bulle explicative', async ({ page }) => {
        const href = await trouverParticipationAvecPersonnage(page);
        if (!href) test.skip();

        await page.goto(href!);

        const chaine = page.locator('ul.list-group.mb-3').filter({ hasText: 'Personnage principal' }).first();
        const icones = chaine.locator('i[data-bs-toggle="tooltip"]');

        expect(await icones.count()).toBeGreaterThanOrEqual(3);
        // Bootstrap déplace title vers data-bs-original-title une fois initialisé.
        const bulle = await icones.first().getAttribute('data-bs-original-title')
            ?? await icones.first().getAttribute('title');
        expect(bulle ?? '').not.toEqual('');
    });

    test("la page de choix de la relève affiche l'encart d'explication et la chaîne", async ({ page }) => {
        const href = await trouverParticipationAvecPersonnage(page);
        if (!href) test.skip();

        const id = href!.match(/\/participant\/(\d+)/)?.[1];
        if (!id) test.skip();

        await page.goto(`/participant/${id}/personnageReleve`);

        // Le groupe peut être verrouillé : la page redirige alors vers la participation.
        if (page.url().includes('/personnageReleve')) {
            await expect(page.getByRole('heading', { name: /Choix du personnage de relève/i })).toBeVisible();
            await expect(page.locator('.card-text')).toContainText('trépasser');
            await expect(page.locator('ul.list-group')).toContainText('Personnage principal');
        }
    });

    test('un groupe verrouillé bloque la modification et propose le déverrouillage', async ({ page }) => {
        const href = await trouverParticipationAvecPersonnage(page);
        if (!href) test.skip();

        await page.goto(href!);

        const chaine = page.locator('ul.list-group.mb-3').filter({ hasText: 'Personnage principal' }).first();
        const verrou = chaine.locator('a[href*="/unlock"], span:has-text("Verrouillé")');

        // Selon l'état du groupe, soit des liens de modification, soit le verrou.
        if ((await verrou.count()) > 0) {
            await expect(verrou.first()).toBeVisible();
            await expect(chaine.locator('a[href*="/personnageReleve"]')).toHaveCount(0);
        } else {
            await expect(chaine.locator('a[href*="/personnageReleve"]')).toHaveCount(1);
        }
    });

    test('les listes de participants portent des badges et non la chaîne complète', async ({ page }) => {
        await page.goto('/gn');
        const gnHref = await premierLien(page, /^\/gn\/\d+$/);
        if (!gnHref) test.skip();

        await page.goto(`${gnHref}/participants`);

        const tableau = page.locator('table').first();
        await expect(tableau).toBeVisible();
        // La chaîne détaillée n'a pas sa place dans une liste.
        await expect(tableau).not.toContainText('Archétype de secours :');
    });

    test('la fiche imprimée liste les personnages jouables', async ({ page }) => {
        await page.goto('/groupe');
        const href = await premierLien(page, /^\/groupe\/\d+$/);
        if (!href) test.skip();

        const reponse = await page.goto(`${href}/print/perso`);
        if (!reponse || reponse.status() >= 400) test.skip();

        const corps = page.locator('body');
        if (await corps.locator('text=Personnages jouables').count()) {
            await expect(corps).toContainText('Personnage principal :');
            await expect(corps).toContainText('Archétype de secours :');
        }
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Personnage actif sur LarpManager
// ─────────────────────────────────────────────────────────────────────────────

test.describe('personnage actif sur LarpManager', () => {
    /** Le déclencheur du sélecteur, dans la barre de menu. */
    function declencheur(page: Page) {
        return page.locator('a[data-bs-toggle="dropdown"]:has(i.fa-user-secret)');
    }

    /**
     * En dessous de lg, toute la barre vit dans un offcanvas : le sélecteur n'est
     * atteignable qu'après ouverture du menu hamburger.
     */
    async function ouvrirLaBarre(page: Page): Promise<void> {
        const hamburger = page.locator('button.navbar-toggler');
        if (await hamburger.isVisible()) {
            await hamburger.click();
            await expect(page.locator('#navbarOffcanvas')).toHaveClass(/show/);
        }
    }

    /**
     * Le menu du personnage actif, et lui seul : d'autres menus déroulants de la
     * barre peuvent être ouverts en même temps.
     */
    function menuPersonnageActif(page: Page) {
        return page.locator('ul.dropdown-menu.show').filter({ hasText: 'Personnage actif sur LarpManager' });
    }

    test('le menu propose un sélecteur explicite', async ({ page }) => {
        await page.goto('/');
        if ((await declencheur(page).count()) === 0) test.skip();

        await ouvrirLaBarre(page);
        await declencheur(page).click();

        const menu = menuPersonnageActif(page);
        await expect(menu).toContainText('Personnage actif sur LarpManager');
        await expect(menu).toContainText("Sans rapport avec le personnage principal d'une participation");
        await expect(menu.locator('a[href*="/personage/default"]')).toBeVisible();
    });

    test('la bascule change le personnage actif en un clic', async ({ page }, testInfo) => {
        // Seul test mutatif du fichier : il change une donnée du compte de test.
        // Les projets desktop et mobile partagent ce compte, donc la même session
        // PHP et le même jeton CSRF — les faire basculer en parallèle les ferait
        // s'écraser mutuellement. Le rendu du sélecteur reste couvert sur mobile
        // par les deux autres tests.
        test.skip(testInfo.project.name !== 'desktop', 'Test mutatif, limité à un seul projet.');

        await page.goto('/');
        if ((await declencheur(page).count()) === 0) test.skip();

        await ouvrirLaBarre(page);
        await declencheur(page).click();

        const inactifs = menuPersonnageActif(page).locator('button.dropdown-item:not(.active)');
        if ((await inactifs.count()) === 0) test.skip();

        const cible = (await inactifs.first().textContent())?.trim() ?? '';

        await Promise.all([
            page.waitForURL(() => true),
            inactifs.first().click(),
        ]);

        // On vérifie l'effet, pas le message : le flash est consommé au premier rendu
        // et une requête concurrente du navigateur peut le faire disparaître.
        // Le sélecteur reflète le nouvel état. La page a rechargé : sur mobile il
        // faut rouvrir la barre.
        await ouvrirLaBarre(page);
        await declencheur(page).click();
        await expect(menuPersonnageActif(page).locator('button.dropdown-item.active')).toContainText(cible);
    });

    test('la page de choix ne propose que des personnages vivants', async ({ page }) => {
        await page.goto('/');
        if ((await declencheur(page).count()) === 0) test.skip();

        await ouvrirLaBarre(page);
        await declencheur(page).click();
        await menuPersonnageActif(page).locator('a[href*="/personage/default"]').click();

        await expect(page.getByRole('heading', { name: /Choix du personnage actif sur LarpManager/i })).toBeVisible();
        await expect(page.locator('.card-text')).toContainText('vivants');
    });
});
