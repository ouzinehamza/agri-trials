import { cn } from '@/lib/cn';

/** Shimmering placeholder block. Compose these to mirror the shape of loading content. */
export function Skeleton({ className }: { className?: string }) {
    return <div className={cn('skeleton rounded-md', className)} aria-hidden="true" />;
}

export function SkeletonText({ lines = 3, className }: { lines?: number; className?: string }) {
    return (
        <div className={cn('space-y-2', className)} aria-hidden="true">
            {Array.from({ length: lines }).map((_, i) => (
                <Skeleton key={i} className={cn('h-3.5', i === lines - 1 ? 'w-2/3' : 'w-full')} />
            ))}
        </div>
    );
}

export function SkeletonCard({ className }: { className?: string }) {
    return (
        <div className={cn('rounded-lg border border-line bg-surface p-5', className)}>
            <Skeleton className="mb-4 h-4 w-1/3" />
            <SkeletonText lines={3} />
        </div>
    );
}

/** Generic full-page placeholder shown during route navigation (title + stat tiles + table). */
export function PageSkeleton() {
    return (
        <div className="animate-fade-in">
            <div className="mb-6 flex items-start justify-between gap-3">
                <div className="space-y-2"><Skeleton className="h-6 w-56" /><Skeleton className="h-3.5 w-72" /></div>
                <Skeleton className="h-9 w-32 rounded-lg" />
            </div>
            <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-20 rounded-lg" />)}
            </div>
            <TableSkeleton rows={7} cols={5} />
        </div>
    );
}

/** Placeholder for a data table while rows load. */
export function TableSkeleton({ rows = 6, cols = 5 }: { rows?: number; cols?: number }) {
    return (
        <div className="overflow-hidden rounded-lg border border-line bg-surface" aria-hidden="true">
            <div className="flex gap-4 border-b border-line px-4 py-3">
                {Array.from({ length: cols }).map((_, i) => <Skeleton key={i} className="h-3 flex-1" />)}
            </div>
            {Array.from({ length: rows }).map((_, r) => (
                <div key={r} className="flex gap-4 border-b border-line px-4 py-3.5 last:border-0">
                    {Array.from({ length: cols }).map((_, c) => (
                        <Skeleton key={c} className={cn('h-3.5 flex-1', c === 0 && 'max-w-[40%]')} />
                    ))}
                </div>
            ))}
        </div>
    );
}
