import ApplicationLogo from '@/Components/ApplicationLogo';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    ClipboardCheck,
    Database,
    FlaskConical,
    Languages,
    ShieldCheck,
    Sprout,
} from 'lucide-react';
import type { ComponentType } from 'react';

type Feature = {
    title: string;
    description: string;
    icon: ComponentType<{ className?: string }>;
};

const content = {
    title: 'Agri-Trials',
    eyebrow: 'Variety trials management',
    subtitle:
        'A field-ready workspace for comparing new crop varieties against trusted controls, tracking observations, and turning trial evidence into confident launch decisions.',
    primaryAction: 'Open dashboard',
    loginAction: 'Log in',
    registerAction: 'Create account',
    metrics: [
        { value: '6', label: 'seeded trials' },
        { value: '3', label: 'crop families' },
        { value: '100%', label: 'metadata driven' },
    ],
    pipeline: ['Planning', 'Field follow-up', 'Harvest', 'Scorecard', 'Decision'],
    features: [
        {
            title: 'Trials with context',
            description:
                'Follow each season, location, crop, stage, and control variety from the same calm operational view.',
            icon: FlaskConical,
        },
        {
            title: 'Metadata that adapts',
            description:
                'Custom fields, catalogs, measurements, and workflows can evolve without rebuilding every screen.',
            icon: Database,
        },
        {
            title: 'Decision-grade evidence',
            description:
                'Harvest observations feed scorecards so launch, re-trial, and reject decisions stay traceable.',
            icon: ClipboardCheck,
        },
        {
            title: 'Built for real teams',
            description:
                'Workspace scoping, permissions, imports, exports, and multilingual content support daily field operations.',
            icon: ShieldCheck,
        },
    ] satisfies Feature[],
};

export default function Welcome({ auth }: PageProps) {
    const dashboardHref = route('dashboard');
    const primaryHref = auth.user ? dashboardHref : route('login');

    return (
        <>
            <Head title={content.title} />

            <main className="min-h-screen bg-page text-ink">
                <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 py-6 sm:px-8 lg:px-10">
                    <header className="flex items-center justify-between gap-4">
                        <Link
                            href="/"
                            className="flex items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-page"
                            aria-label={content.title}
                        >
                            <span className="flex size-10 items-center justify-center rounded-lg border border-line bg-surface">
                                <ApplicationLogo className="size-6 fill-brand" />
                            </span>
                            <span className="text-base font-medium text-ink">
                                {content.title}
                            </span>
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Link
                                    href={dashboardHref}
                                    className="inline-flex items-center gap-2 rounded-md border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink transition hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                >
                                    <BarChart3 className="size-4" />
                                    {content.primaryAction}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center rounded-md px-3.5 py-2 text-sm font-medium text-ink-muted transition hover:bg-surface-2 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    >
                                        {content.loginAction}
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="hidden rounded-md border border-line bg-surface px-3.5 py-2 text-sm font-medium text-ink transition hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:inline-flex"
                                    >
                                        {content.registerAction}
                                    </Link>
                                </>
                            )}
                        </nav>
                    </header>

                    <section className="grid flex-1 items-center gap-10 py-12 lg:grid-cols-[1.02fr_0.98fr] lg:py-16">
                        <div className="max-w-2xl animate-fade-in-up">
                            <div className="inline-flex items-center gap-2 rounded-md border border-line bg-surface px-3 py-1.5 text-sm font-medium text-ink-muted">
                                <Sprout className="size-4 text-brand" />
                                {content.eyebrow}
                            </div>

                            <h1 className="mt-6 text-4xl font-medium leading-tight tracking-normal text-ink sm:text-5xl">
                                Decide which varieties are ready for production.
                            </h1>

                            <p className="mt-5 max-w-xl text-base leading-7 text-ink-muted sm:text-lg">
                                {content.subtitle}
                            </p>

                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link
                                    href={primaryHref}
                                    className="inline-flex items-center gap-2 rounded-md border border-transparent bg-brand px-4 py-2.5 text-sm font-medium text-brand-fg transition hover:bg-brand-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-page"
                                >
                                    {auth.user
                                        ? content.primaryAction
                                        : content.loginAction}
                                    <ArrowRight className="size-4" />
                                </Link>
                                {!auth.user && (
                                    <Link
                                        href={route('register')}
                                        className="inline-flex items-center rounded-md border border-line bg-surface px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-surface-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    >
                                        {content.registerAction}
                                    </Link>
                                )}
                            </div>

                            <dl className="mt-10 grid max-w-xl grid-cols-3 gap-3">
                                {content.metrics.map((metric) => (
                                    <div
                                        key={metric.label}
                                        className="rounded-lg border border-line bg-surface p-4"
                                    >
                                        <dt className="text-2xl font-medium text-ink">
                                            {metric.value}
                                        </dt>
                                        <dd className="mt-1 text-sm text-ink-muted">
                                            {metric.label}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        <div className="animate-scale-in rounded-lg border border-line bg-surface p-4">
                            <div className="rounded-lg bg-surface-2 p-4">
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
                                    <div>
                                        <p className="text-sm text-ink-muted">
                                            Decision pipeline
                                        </p>
                                        <h2 className="mt-1 text-lg font-medium text-ink">
                                            Tomato HT-214 field trial
                                        </h2>
                                    </div>
                                    <span className="rounded-md bg-warning-soft px-2.5 py-1 text-xs font-medium text-warning">
                                        In harvest
                                    </span>
                                </div>

                                <ol className="mt-5 space-y-3">
                                    {content.pipeline.map((stage, index) => (
                                        <li
                                            key={stage}
                                            className="grid grid-cols-[2rem_1fr_auto] items-center gap-3 rounded-md bg-surface px-3 py-3"
                                        >
                                            <span className="flex size-8 items-center justify-center rounded-md bg-success-soft text-sm font-medium text-success">
                                                {index + 1}
                                            </span>
                                            <span className="text-sm font-medium text-ink">
                                                {stage}
                                            </span>
                                            <span
                                                className={
                                                    index < 3
                                                        ? 'text-xs text-success'
                                                        : 'text-xs text-ink-faint'
                                                }
                                            >
                                                {index < 3 ? 'Ready' : 'Next'}
                                            </span>
                                        </li>
                                    ))}
                                </ol>

                                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-md border border-line bg-surface p-4">
                                        <p className="text-sm text-ink-muted">
                                            Yield vs control
                                        </p>
                                        <p className="mt-2 text-2xl font-medium text-success">
                                            +12.4%
                                        </p>
                                    </div>
                                    <div className="rounded-md border border-line bg-surface p-4">
                                        <p className="text-sm text-ink-muted">
                                            Decision confidence
                                        </p>
                                        <p className="mt-2 text-2xl font-medium text-ink">
                                            87%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-4 pb-10 md:grid-cols-2 lg:grid-cols-4">
                        {content.features.map((feature) => {
                            const Icon = feature.icon;

                            return (
                                <article
                                    key={feature.title}
                                    className="rounded-lg border border-line bg-surface p-5"
                                >
                                    <div className="flex size-10 items-center justify-center rounded-md bg-success-soft text-success">
                                        <Icon className="size-5" />
                                    </div>
                                    <h2 className="mt-4 text-base font-medium text-ink">
                                        {feature.title}
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-ink-muted">
                                        {feature.description}
                                    </p>
                                </article>
                            );
                        })}
                    </section>

                    <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-line py-5 text-sm text-ink-muted">
                        <span>Designed for Morocco and South Europe trials.</span>
                        <span className="inline-flex items-center gap-2">
                            <Languages className="size-4" />
                            French, English, and Arabic content ready
                        </span>
                    </footer>
                </div>
            </main>
        </>
    );
}
