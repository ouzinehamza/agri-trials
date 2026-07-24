import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { CheckCircle2, FlaskConical, Languages, ShieldCheck, Sprout } from 'lucide-react';
import { PropsWithChildren } from 'react';

const benefits = [
    { icon: FlaskConical, text: 'Des essais structurés par des workflows configurables' },
    { icon: Sprout, text: 'Des observations terrain jusqu’à la décision variétale' },
    { icon: ShieldCheck, text: 'Données privées, rôles contrôlés et décisions traçables' },
];

export default function Guest({ children }: PropsWithChildren) {
    return (
        <main className="min-h-screen bg-page text-ink">
            <div className="grid min-h-screen lg:grid-cols-[minmax(420px,0.9fr)_minmax(520px,1.1fr)]">
                <section className="relative hidden overflow-hidden border-e border-line bg-ink p-10 text-page lg:flex lg:flex-col">
                    <div className="absolute -end-28 -top-28 size-80 rounded-full border border-page/10" />
                    <div className="absolute -end-12 -top-12 size-48 rounded-full border border-page/10" />
                    <Link href="/" className="relative z-10 flex items-center gap-3">
                        <span className="flex size-11 items-center justify-center rounded-xl bg-brand text-brand-fg">
                            <ApplicationLogo className="size-7 fill-current" />
                        </span>
                        <span>
                            <span className="block text-lg font-medium">Agri-Trials</span>
                            <span className="block text-xs text-page/55">Variety intelligence workspace</span>
                        </span>
                    </Link>
                    <div className="relative z-10 my-auto max-w-xl py-12">
                        <span className="inline-flex items-center gap-2 rounded-full border border-page/15 px-3 py-1.5 text-xs text-page/70">
                            <Languages className="size-4" /> Maroc · Europe du Sud · FR / EN / AR
                        </span>
                        <h1 className="mt-7 text-4xl font-medium leading-tight">Transformer les essais terrain en décisions de lancement solides.</h1>
                        <p className="mt-5 max-w-lg text-base leading-7 text-page/65">Une plateforme unique pour piloter variétés, témoins, récoltes, mesures et décisions.</p>
                        <div className="mt-10 space-y-4">
                            {benefits.map(({ icon: Icon, text }) => (
                                <div key={text} className="flex items-center gap-3 text-sm text-page/75">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-page/10"><Icon className="size-4" /></span>
                                    {text}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="relative z-10 flex items-center gap-2 text-xs text-page/45"><CheckCircle2 className="size-4 text-brand" /> Installation privée · Données de l’entreprise</div>
                </section>
                <section className="flex min-h-screen flex-col">
                    <header className="flex items-center justify-between p-5 sm:p-7">
                        <Link href="/" className="flex items-center gap-2 lg:hidden">
                            <span className="flex size-9 items-center justify-center rounded-lg bg-brand text-brand-fg"><ApplicationLogo className="size-5 fill-current" /></span>
                            <span className="font-medium">Agri-Trials</span>
                        </Link>
                        <Link href="/" className="ms-auto text-sm text-ink-muted hover:text-ink">Retour à l’accueil</Link>
                    </header>
                    <div className="flex flex-1 items-center justify-center px-5 pb-16 pt-4 sm:px-8">
                        <div className="w-full max-w-md rounded-2xl border border-line bg-surface p-6 sm:p-8">{children}</div>
                    </div>
                </section>
            </div>
        </main>
    );
}
