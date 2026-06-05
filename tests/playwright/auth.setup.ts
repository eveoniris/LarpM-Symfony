import { test as setup, expect } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';

const authFile = path.join(__dirname, '.auth/user.json');

setup('authenticate', async ({ page }) => {
    const login = process.env.TEST_USER ?? 'admin@example.com';
    const password = process.env.TEST_PASSWORD ?? 'password';

    await page.goto('/login');
    await page.fill('input[name="username"]', login);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"], input[type="submit"]');

    await page.waitForURL(url => !url.toString().includes('/login'));
    await expect(page).not.toHaveURL(/\/login/);

    fs.mkdirSync(path.dirname(authFile), { recursive: true });
    await page.context().storageState({ path: authFile });
});
