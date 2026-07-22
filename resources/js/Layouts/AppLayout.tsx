import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, FlaskConical, Boxes, Database, Receipt, SlidersHorizontal, Palette, Users, Building2, Search, Sprout, FileText, Images, Globe2, UserRound, LogOut, ChevronDown, ChevronUp, History, type LucideIcon } from 'lucide-react';
import { useContentLocale } from '@/i18n/contentLocale';
import { useUiLocale, type UiLocale } from '@/i18n/uiLocale';
import { cn } from '@/lib/cn';
import { PageSkeleton } from '@/Components/Skeleton';

const nav = [
    { href: '/dashboard', key: 'dashboard', icon: LayoutDashboard },
    { href: '/trials', key: 'trials', icon: FlaskConical },
    { href: '/referentiels/fournisseurs', key: 'references', icon: Database },
    { href: '/stock', key: 'stock', icon: Boxes },
    { href: '/expenses', key: 'expenses', icon: Receipt },
    { href: '/media', key: 'media', icon: Images },
    { href: '/workspaces', key: 'workspaces', icon: Building2, adminOnly: true },
    { href: '/configuration', key: 'configuration', icon: SlidersHorizontal, adminOnly: true },
    { href: '/branding', key: 'branding', icon: Palette, adminOnly: true },
    { href: '/users', key: 'users', icon: Users, adminOnly: true },
    { href: '/audit', key: 'audit', icon: History, adminOnly: true },
] as const;

/** Section root of an href ("/referentiels/fournisseurs" → "/referentiels") so sibling tabs stay active. */
const section = (href: string) => '/' + href.split('/')[1];

function LanguageMenu() {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    const ui = useUiLocale();
    const content = useContentLocale();
    useEffect(() => {
        const close = (e: MouseEvent) => { if (!ref.current?.contains(e.target as Node)) setOpen(false); };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, []);
    return (
        <div className="relative" ref={ref}>
            <button onClick={() => setOpen((v) => !v)} aria-expanded={open} aria-label={ui.t('interfaceLanguage')} className="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-2 text-xs font-medium text-ink-muted transition-colors hover:bg-surface-2">
                <Globe2 size={16} /><span className="uppercase">{ui.locale}</span><ChevronDown size={13} className={cn('transition-transform', open && 'rotate-180')} />
            </button>
            {open && (
                <div className="absolute end-0 top-full z-50 mt-2 w-64 origin-top rounded-xl border border-line bg-surface p-3 shadow-xl animate-scale-in">
                    <div className="mb-2 text-[11px] font-medium text-ink-faint">{ui.t('interfaceLanguage')}</div>
                    <div className="grid grid-cols-3 gap-1">
                        {(['fr', 'en', 'ar'] as UiLocale[]).map((l) => (
                            <button key={l} onClick={() => ui.setLocale(l)} className={cn('rounded-md px-3 py-2 text-xs font-medium uppercase transition-colors', ui.locale === l ? 'bg-brand text-brand-fg' : 'bg-surface-2 text-ink-muted hover:text-ink')}>{l}</button>
                        ))}
                    </div>
                    {content.locales.length > 1 && (
                        <>
                            <div className="mb-2 mt-4 text-[11px] font-medium text-ink-faint">{ui.t('contentLanguage')}</div>
                            <div className="flex gap-1">
                                {content.locales.map((l) => (
                                    <button key={l} onClick={() => content.setLocale(l)} className={cn('flex-1 rounded-md px-3 py-2 text-xs font-medium uppercase transition-colors', content.locale === l ? 'bg-info text-white' : 'bg-surface-2 text-ink-muted hover:text-ink')}>{l}</button>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}

/** A dock item: icon always visible; the label reveals smoothly on hover/focus and stays for the active page. */
function DockItem({ href, icon: Icon, label, active, tone, ...link }: { href: string; icon: LucideIcon; label: string; active: boolean; tone?: string } & Record<string, unknown>) {
    return (
        <Link
            href={href}
            {...link}
            title={label}
            aria-label={label}
            aria-current={active ? 'page' : undefined}
            data-active={active}
            className={cn(
                'reveal-trigger group flex h-11 shrink-0 items-center rounded-full px-3 text-xs font-medium outline-none transition-colors duration-200 focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 focus-visible:ring-offset-surface',
                active ? 'bg-brand text-brand-fg' : cn('text-ink-muted hover:bg-surface-2 hover:text-ink', tone),
            )}
        >
            <Icon size={18} />
            <span className="reveal-label ps-2">{label}</span>
        </Link>
    );
}

function FloatingDock({ visible, url, isAdmin }: { visible: typeof nav[number][]; url: string; isAdmin: boolean }) {
    const { t } = useUiLocale();
    const [shown, setShown] = useState(() => localStorage.getItem('agri-dock-hidden') !== '1');
    const set = (value: boolean) => { setShown(value); localStorage.setItem('agri-dock-hidden', value ? '0' : '1'); };

    if (!shown) {
        return (
            <button onClick={() => set(true)} aria-label="Afficher le menu" className="fixed bottom-4 left-1/2 z-40 -translate-x-1/2 rounded-full border border-line bg-ink p-3 text-page shadow-xl transition hover:scale-105 active:scale-95 animate-fade-in">
                <ChevronUp size={18} />
            </button>
        );
    }
    return (
        <nav aria-label="Navigation principale" className="scroll-slim fixed bottom-3 left-1/2 z-40 flex max-w-[calc(100vw-20px)] -translate-x-1/2 items-center gap-1 overflow-x-auto rounded-2xl border border-line bg-surface/90 p-1.5 shadow-xl backdrop-blur-md animate-fade-in sm:bottom-5 sm:rounded-full">
            {visible.map((item) => <DockItem key={item.href} href={item.href} icon={item.icon} label={t(item.key)} active={url.startsWith(section(item.href))} />)}
            <span className="mx-1 hidden h-6 w-px bg-line sm:block" />
            <DockItem href="/profile" icon={UserRound} label={t('profile')} active={url.startsWith('/profile')} />
            <DockItem href="/logout" icon={LogOut} label={t('logout')} active={false} tone="hover:bg-danger-soft hover:text-danger" method="post" as="button" />
            <button onClick={() => set(false)} aria-label="Masquer le menu" title="Masquer le menu" className="hidden h-11 w-9 shrink-0 items-center justify-center rounded-full text-ink-faint transition-colors hover:bg-surface-2 sm:flex">
                <ChevronDown size={16} />
            </button>
        </nav>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const { url, props } = usePage();
    const { t } = useUiLocale();
    const user = (props.auth as { user?: { name?: string; role?: string } } | undefined)?.user;
    const isAdmin = user?.role === 'admin';
    const visible = nav.filter((i) => !('adminOnly' in i) || !i.adminOnly || isAdmin) as typeof nav[number][];
    const trialMatch = url.match(/^\/trials\/(\d+)/);

    // Show a page skeleton while navigating between full pages (not partial/deferred reloads or forms).
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | undefined;
        const off1 = router.on('start', (e) => {
            const v = e.detail.visit;
            if (v.method === 'get' && v.only.length === 0) timer = setTimeout(() => setLoading(true), 150);
        });
        const done = () => { clearTimeout(timer); setLoading(false); };
        const off2 = router.on('finish', done);
        return () => { off1(); off2(); clearTimeout(timer); };
    }, []);
    return (
        <div className="min-h-screen">
            <header className="sticky top-0 z-30 flex items-center gap-2 border-b border-line bg-surface/90 px-4 py-3 backdrop-blur-md sm:px-6">
                <Link href="/dashboard" className="me-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand text-xs font-medium text-brand-fg transition-transform hover:scale-105">AGT</Link>
                <div className="relative max-w-xl flex-1">
                    <Search size={16} className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-ink-faint" />
                    <input placeholder={t('search')} className="w-full rounded-full border border-line bg-page py-2 pe-3 ps-9 text-sm outline-none transition-colors focus:border-brand" />
                </div>
                {trialMatch && (
                    <div className="flex gap-1">
                        <Link href={`/trials/${trialMatch[1]}/harvests`} aria-label={t('harvests')} className="inline-flex h-9 items-center gap-1.5 rounded-full border border-line px-2.5 text-xs transition-colors hover:bg-surface-2"><Sprout size={15} /><span className="hidden lg:inline">{t('harvests')}</span></Link>
                        <Link href={`/trials/${trialMatch[1]}/report`} aria-label={t('report')} className="inline-flex h-9 items-center gap-1.5 rounded-full border border-line px-2.5 text-xs transition-colors hover:bg-surface-2"><FileText size={15} /><span className="hidden lg:inline">{t('report')}</span></Link>
                    </div>
                )}
                <LanguageMenu />
            </header>
            <main className="mx-auto min-h-[calc(100vh-65px)] w-full max-w-[1680px] p-4 pb-28 sm:p-6 sm:pb-32 lg:p-8">
                {loading ? <PageSkeleton /> : <div key={url} className="animate-fade-in-up">{children}</div>}
            </main>
            <FloatingDock visible={visible} url={url} isAdmin={isAdmin} />
        </div>
    );
}
