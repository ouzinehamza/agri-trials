import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, PageHeader } from '@/Components/ui';

export default function Placeholder({ title, note }: { title: string; note: string }) {
    return (
        <AppLayout>
            <Head title={title} />
            <PageHeader title={title} />
            <Card className="flex flex-col items-center justify-center gap-3 p-16 text-center">
                <Construction size={28} className="text-ink-faint" />
                <p className="max-w-md text-sm text-ink-muted">{note}</p>
            </Card>
        </AppLayout>
    );
}
