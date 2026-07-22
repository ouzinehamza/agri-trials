import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './theme/ThemeProvider';
import { UiLocaleProvider } from './i18n/uiLocale';
import { ContentLocaleProvider } from './i18n/contentLocale';

const appName = import.meta.env.VITE_APP_NAME || 'Agri-Trials';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        const serverTheme = (props.initialPage.props as { theme?: Record<string, unknown> | null }).theme ?? undefined;

        root.render(
            <ThemeProvider initial={serverTheme as never}>
                <UiLocaleProvider initial={(props.initialPage.props as { uiLocale?: string }).uiLocale ?? 'fr'}>
                    <ContentLocaleProvider defaultLocale={(props.initialPage.props as { defaultLocale?: string }).defaultLocale ?? 'fr'} locales={(props.initialPage.props as { locales?: string[] }).locales ?? ['fr']}>
                        <App {...props} />
                    </ContentLocaleProvider>
                </UiLocaleProvider>
            </ThemeProvider>,
        );
    },
    progress: {
        color: '#1D9E75',
    },
});
