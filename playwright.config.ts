import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/playwright',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: [['html', { open: 'never' }], ['list']],
    use: {
        baseURL: process.env.BASE_URL ?? 'http://localhost:8080',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: '**/auth.setup.ts' },
        {
            name: 'desktop',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'tests/playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'mobile',
            use: {
                ...devices['Pixel 5'],
                storageState: 'tests/playwright/.auth/user.json',
            },
            dependencies: ['setup'],
        },
    ],
});
