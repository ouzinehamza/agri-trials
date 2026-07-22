import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';

type ContentLocaleValue = {
    locale: string;
    setLocale: (l: string) => void;
    locales: string[];
};

const ContentLocaleContext = createContext<ContentLocaleValue | null>(null);

export function ContentLocaleProvider({
    defaultLocale,
    locales,
    children,
}: {
    defaultLocale: string;
    locales: string[];
    children: ReactNode;
}) {
    const [locale, setLocale] = useState(() => {
        const saved = localStorage.getItem('agri-content-locale');
        return saved && locales.includes(saved) ? saved : defaultLocale;
    });
    useEffect(() => localStorage.setItem('agri-content-locale', locale), [locale]);
    return (
        <ContentLocaleContext.Provider value={{ locale, setLocale, locales: locales.length ? locales : [defaultLocale] }}>
            {children}
        </ContentLocaleContext.Provider>
    );
}

export function useContentLocale(): ContentLocaleValue {
    return (
        useContext(ContentLocaleContext) ?? { locale: 'fr', setLocale: () => {}, locales: ['fr'] }
    );
}
