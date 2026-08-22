import { startStimulusApp } from '@symfony/stimulus-bundle';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
// Guarded against double-start (cause of the previous "Detected multiple instances
// of Stimulus" error that led to this bootstrap being disabled entirely).
export const app = globalThis.__larpmanagerStimulusApp__ ??= startStimulusApp();

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
