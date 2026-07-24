import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LockKeyhole, Mail } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ email: '', password: '', remember: false });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title="Connexion" />
            <div className="mb-7">
                <div className="mb-4 flex size-11 items-center justify-center rounded-xl bg-brand-soft text-brand"><LockKeyhole className="size-5" /></div>
                <h1 className="text-2xl font-medium">Bienvenue</h1>
                <p className="mt-2 text-sm leading-6 text-ink-muted">Connectez-vous à votre espace sécurisé de gestion des essais.</p>
            </div>
            {status && <div className="mb-5 rounded-lg bg-success-soft p-3 text-sm text-success">{status}</div>}
            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Adresse e-mail" />
                    <div className="relative mt-1.5">
                        <Mail className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
                        <TextInput id="email" type="email" name="email" value={data.email} className="block w-full ps-10" autoComplete="username" isFocused onChange={(e) => setData('email', e.target.value)} required />
                    </div>
                    <InputError message={errors.email} className="mt-2" />
                </div>
                <div>
                    <div className="flex items-center justify-between">
                        <InputLabel htmlFor="password" value="Mot de passe" />
                        {canResetPassword && <Link href={route('password.request')} className="text-xs font-medium text-brand hover:underline">Mot de passe oublié ?</Link>}
                    </div>
                    <div className="relative mt-1.5">
                        <LockKeyhole className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-faint" />
                        <TextInput id="password" type={showPassword ? 'text' : 'password'} name="password" value={data.password} className="block w-full px-10" autoComplete="current-password" onChange={(e) => setData('password', e.target.value)} required />
                        <button type="button" onClick={() => setShowPassword((value) => !value)} className="absolute end-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-ink-faint hover:bg-surface-2 hover:text-ink" aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}>
                            {showPassword ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                        </button>
                    </div>
                    <InputError message={errors.password} className="mt-2" />
                </div>
                <label className="flex items-center gap-2 text-sm text-ink-muted">
                    <Checkbox name="remember" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
                    Garder ma session active
                </label>
                <PrimaryButton className="w-full" disabled={processing}>{processing ? 'Connexion…' : 'Se connecter'}</PrimaryButton>
            </form>
            <div className="mt-6 border-t border-line pt-5 text-center text-xs leading-5 text-ink-faint">Accès réservé aux collaborateurs et partenaires autorisés.</div>
        </GuestLayout>
    );
}
