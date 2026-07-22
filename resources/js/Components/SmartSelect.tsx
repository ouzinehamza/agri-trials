import { Combobox, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { Check, ChevronsUpDown, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { cn } from '@/lib/cn';

export type SelectOption = { value: string | number; label: string; meta?: string };

export default function SmartSelect({ value, onChange, options = [], placeholder = 'Sélectionner…', multiple = false, loadUrl }: { value: any; onChange: (value: any) => void; options?: SelectOption[]; placeholder?: string; multiple?: boolean; loadUrl?: string }) {
    const [query, setQuery] = useState('');
    const [remote, setRemote] = useState<SelectOption[]>([]);
    const [loading, setLoading] = useState(false);

    // Prime remote options once so preselected values resolve to labels (edit mode).
    useEffect(() => {
        if (!loadUrl) return;
        const timer = setTimeout(async () => {
            setLoading(true);
            try {
                const response = await fetch(`${loadUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
                setRemote(await response.json());
            } finally {
                setLoading(false);
            }
        }, 250);
        return () => clearTimeout(timer);
    }, [loadUrl, query]);

    const source = loadUrl ? remote : options;
    const filtered = useMemo(() => (loadUrl ? source : source.filter((o) => o.label.toLowerCase().includes(query.toLowerCase()))), [source, query, loadUrl]);

    // Persistent id→label cache so chips keep their labels after the option list changes.
    const cache = useRef<Record<string, string>>({});
    source.forEach((o) => { cache.current[String(o.value)] = o.label; });
    const labelFor = (v: string | number) => cache.current[String(v)] ?? String(v);

    const selected: (string | number)[] = multiple ? (Array.isArray(value) ? value : []) : [];
    const removeChip = (v: string | number) => onChange(selected.filter((x) => String(x) !== String(v)));

    return (
        <Combobox value={value} onChange={onChange} multiple={multiple as false}>
            <div className="relative">
                {multiple && selected.length > 0 && (
                    <div className="mb-1.5 flex flex-wrap gap-1">
                        {selected.map((v) => (
                            <span key={String(v)} className="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 text-xs text-brand">
                                {labelFor(v)}
                                <button type="button" onClick={() => removeChip(v)} aria-label="Retirer"><X size={12} /></button>
                            </span>
                        ))}
                    </div>
                )}
                <div className="relative">
                    <Search size={15} className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-ink-faint" />
                    <ComboboxInput
                        displayValue={(selectedValue: any) => (multiple ? '' : (source.find((o) => String(o.value) === String(selectedValue))?.label ?? (selectedValue ? labelFor(selectedValue) : '')))}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder={multiple && selected.length > 0 ? 'Ajouter…' : placeholder}
                        className="w-full rounded-md border border-line bg-page py-2.5 pe-9 ps-9 text-sm outline-none focus:border-brand"
                    />
                    <ChevronsUpDown size={15} className="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-ink-faint" />
                </div>
                <ComboboxOptions anchor="bottom start" className="z-[70] mt-1 max-h-64 w-[var(--input-width)] overflow-auto rounded-lg border border-line bg-surface p-1 shadow-xl empty:invisible">
                    {loading && <div className="p-3 text-xs text-ink-faint">Recherche…</div>}
                    {filtered.map((option) => (
                        <ComboboxOption key={option.value} value={option.value} className="group flex cursor-pointer items-center justify-between rounded-md px-3 py-2 text-sm data-[focus]:bg-surface-2">
                            <span>
                                <span>{option.label}</span>
                                {option.meta && <span className="ms-2 text-xs text-ink-faint">{option.meta}</span>}
                            </span>
                            <Check size={14} className={cn('opacity-0', 'group-data-[selected]:opacity-100')} />
                        </ComboboxOption>
                    ))}
                    {!loading && filtered.length === 0 && <div className="p-3 text-xs text-ink-faint">Aucun résultat</div>}
                </ComboboxOptions>
            </div>
        </Combobox>
    );
}
