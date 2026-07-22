import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

export type UiLocale = 'fr' | 'en' | 'ar';

const messages = {
    fr: {
        dashboard: 'Tableau de bord', trials: 'Essais', references: 'Référentiels', stock: 'Stock', expenses: 'Charges & factures',
        workspaces: 'Espaces de travail', configuration: 'Configuration', branding: 'Marque & thème', users: 'Utilisateurs', audit: "Journal d'activité",
        search: 'Rechercher…', contentLanguage: 'Langue du contenu', interfaceLanguage: 'Langue de l’interface', harvests: 'Récoltes',
        report: 'Rapport', media: 'Médias', profile: 'Profil', logout: 'Déconnexion', administrator: 'Administrateur', division: 'Division Semences', menu: 'Menu',
        backTrial: 'Retour à l’essai', newHarvest: 'Nouvelle récolte', capturedHarvests: 'Récoltes saisies', trackedMeasures: 'Mesures suivies', comparisons: 'Comparaisons',
        noHarvest: 'Aucune récolte enregistrée', noHarvestHelp: 'Ajoutez la première observation pour alimenter l’évaluation et le scorecard.',
        values: 'valeurs', measure: 'Mesure', aggregatedSummary: 'Synthèse agrégée', better: 'meilleur', behind: 'en retrait', neutral: 'neutre',
        trial: 'Essai', control: 'Témoin', aggregation: 'agrégation', date: 'Date', location: 'Emplacement', notes: 'Notes', cancel: 'Annuler', saveHarvest: 'Enregistrer la récolte',
        missingSite: 'Site non renseigné', missingSeason: 'Saison non renseignée', captureHint: 'mesures définies par le profil d’essai', close: 'Fermer',
    },
    en: {
        dashboard: 'Dashboard', trials: 'Trials', references: 'Master data', stock: 'Stock', expenses: 'Expenses & invoices',
        workspaces: 'Workspaces', configuration: 'Configuration', branding: 'Brand & theme', users: 'Users', audit: 'Activity log',
        search: 'Search…', contentLanguage: 'Content language', interfaceLanguage: 'Interface language', harvests: 'Harvests',
        report: 'Report', media: 'Media', profile: 'Profile', logout: 'Log out', administrator: 'Administrator', division: 'Seeds division', menu: 'Menu',
        backTrial: 'Back to trial', newHarvest: 'New harvest', capturedHarvests: 'Harvests captured', trackedMeasures: 'Measures tracked', comparisons: 'Comparisons',
        noHarvest: 'No harvest recorded', noHarvestHelp: 'Add the first observation to feed evaluation and scoring.',
        values: 'values', measure: 'Measure', aggregatedSummary: 'Aggregated summary', better: 'better', behind: 'behind', neutral: 'neutral',
        trial: 'Trial', control: 'Control', aggregation: 'aggregation', date: 'Date', location: 'Location', notes: 'Notes', cancel: 'Cancel', saveHarvest: 'Save harvest',
        missingSite: 'Site not provided', missingSeason: 'Season not provided', captureHint: 'measures defined by the trial profile', close: 'Close',
    },
    ar: {
        dashboard: 'لوحة القيادة', trials: 'التجارب', references: 'البيانات المرجعية', stock: 'المخزون', expenses: 'المصاريف والفواتير',
        workspaces: 'مساحات العمل', configuration: 'الإعدادات', branding: 'الهوية والمظهر', users: 'المستخدمون', audit: 'سجل النشاط',
        search: 'بحث…', contentLanguage: 'لغة المحتوى', interfaceLanguage: 'لغة الواجهة', harvests: 'الجني',
        report: 'التقرير', media: 'الوسائط', profile: 'الملف الشخصي', logout: 'تسجيل الخروج', administrator: 'مدير النظام', division: 'قسم البذور', menu: 'القائمة',
        backTrial: 'العودة إلى التجربة', newHarvest: 'جني جديد', capturedHarvests: 'عمليات الجني', trackedMeasures: 'المقاييس المتابعة', comparisons: 'المقارنات',
        noHarvest: 'لم يتم تسجيل أي جني', noHarvestHelp: 'أضف الملاحظة الأولى لتغذية التقييم والنتيجة.',
        values: 'قيم', measure: 'المقياس', aggregatedSummary: 'الملخص المجمّع', better: 'أفضل', behind: 'أقل', neutral: 'محايد',
        trial: 'التجربة', control: 'الشاهد', aggregation: 'التجميع', date: 'التاريخ', location: 'الموقع', notes: 'ملاحظات', cancel: 'إلغاء', saveHarvest: 'حفظ الجني',
        missingSite: 'الموقع غير محدد', missingSeason: 'الموسم غير محدد', captureHint: 'مقاييس يحددها نموذج التجربة', close: 'إغلاق',
    },
} as const;

type Key = keyof typeof messages.fr;
type Context = { locale: UiLocale; setLocale: (locale: UiLocale) => void; dir: 'ltr' | 'rtl'; t: (key: Key) => string };
const UiLocaleContext = createContext<Context | null>(null);

export function UiLocaleProvider({ children, initial = 'fr' }: { children: ReactNode; initial?: string }) {
    const [locale, setLocale] = useState<UiLocale>(() => {
        const saved = localStorage.getItem('agri-ui-locale');
        return (saved === 'en' || saved === 'ar' || saved === 'fr' ? saved : initial) as UiLocale;
    });
    const dir: 'ltr' | 'rtl' = locale === 'ar' ? 'rtl' : 'ltr';
    useEffect(() => {
        localStorage.setItem('agri-ui-locale', locale);
        document.documentElement.lang = locale;
        document.documentElement.dir = dir;
    }, [locale, dir]);
    const value = useMemo(() => ({ locale, setLocale, dir, t: (key: Key) => messages[locale][key] ?? messages.fr[key] }), [locale, dir]);
    return <UiLocaleContext.Provider value={value}>{children}</UiLocaleContext.Provider>;
}

export function useUiLocale() {
    const value = useContext(UiLocaleContext);
    if (!value) throw new Error('useUiLocale must be used inside UiLocaleProvider');
    return value;
}
