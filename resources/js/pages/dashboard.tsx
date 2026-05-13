import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface DashboardPageProps {
    currentPlan: {
        name: string;
        slug: string;
        description: string | null;
        trial_days: number;
        currency: string;
    } | null;
    usageSummary: Record<
        string,
        {
            limit: number | null;
            used: number;
            remaining: number | null;
            period_type: string | null;
            source: string;
        }
    >;
    recentBarcodes: Array<{
        id: number;
        barcode_type: string | null;
        export_format: string;
        status: string;
        generated_at: string | null;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const usageLabels: Record<string, string> = {
    daily_generation_limit: 'Daily generation',
    monthly_generation_limit: 'Monthly generation',
    api_monthly_request_limit: 'API requests',
    bulk_monthly_job_limit: 'Bulk jobs',
};

export default function Dashboard() {
    const { currentPlan, usageSummary, recentBarcodes } = usePage<DashboardPageProps>().props;
    const usageEntries = ['daily_generation_limit', 'monthly_generation_limit']
        .map((key) => ({
            key,
            usage: usageSummary[key] ?? {
                limit: null,
                used: 0,
                remaining: null,
                period_type: null,
                source: 'web',
            },
        }))
        .map(({ key, usage }) => ({
            key,
            usage,
            percent:
                usage.limit === null
                    ? null
                    : usage.limit === 0
                      ? 100
                      : Math.min(100, Math.round((usage.used / usage.limit) * 100)),
        }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Current plan</p>
                            <h1 className="mt-2 text-3xl font-semibold text-slate-950">{currentPlan?.name ?? 'No plan resolved yet'}</h1>
                            <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                {currentPlan?.description ?? 'A current plan could not be resolved yet. Check the free fallback plan configuration in the admin panel.'}
                            </p>
                        </div>

                        <Link
                            href={route('pricing')}
                            className="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 transition hover:border-slate-300 hover:bg-slate-100"
                        >
                            View plans
                        </Link>
                    </div>

                    <div className="mt-6 flex flex-wrap gap-3">
                        <div className="rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-900">
                            Plan key: {currentPlan?.slug ?? 'unresolved'}
                        </div>
                        <div className="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700">
                            Trial days: {currentPlan?.trial_days ?? 0}
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {usageEntries.map(({ key, usage, percent }) => (
                        <article key={key} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm font-semibold text-slate-500">{usageLabels[key] ?? key}</p>
                            <div className="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    <p className="text-3xl font-semibold text-slate-950">{usage.used}</p>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {usage.limit === null ? 'No limit configured yet' : `${usage.remaining ?? 0} remaining`}
                                    </p>
                                </div>
                                <div className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                                    {usage.limit === null ? 'Flexible' : `${usage.limit} max`}
                                </div>
                            </div>
                            {percent !== null && (
                                <div className="mt-4 h-2 rounded-full bg-slate-100">
                                    <div
                                        className="h-2 rounded-full bg-blue-600 transition-all"
                                        style={{ width: `${percent}%` }}
                                    />
                                </div>
                            )}
                        </article>
                    ))}
                </section>

                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Recent barcode history</p>
                            <h2 className="mt-2 text-2xl font-semibold text-slate-950">Connected to real records</h2>
                        </div>
                        <Link href={route('profile.edit')} className="text-sm font-semibold text-blue-700 transition hover:text-blue-900">
                            Manage account settings
                        </Link>
                    </div>

                    {recentBarcodes.length > 0 ? (
                        <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                            <div className="grid grid-cols-4 gap-4 bg-slate-50 px-5 py-3 text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">
                                <span>Type</span>
                                <span>Status</span>
                                <span>Format</span>
                                <span>Generated</span>
                            </div>
                            {recentBarcodes.map((barcode) => (
                                <div key={barcode.id} className="grid grid-cols-4 gap-4 border-t border-slate-200 px-5 py-4 text-sm text-slate-700">
                                    <span>{barcode.barcode_type ?? 'Unknown type'}</span>
                                    <span>{barcode.status}</span>
                                    <span>{barcode.export_format}</span>
                                    <span>{barcode.generated_at ?? 'Pending'}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <p className="text-lg font-semibold text-slate-900">No barcodes generated yet.</p>
                            <p className="mt-2 text-sm text-slate-600">This empty state is backed by the real generated barcode history table.</p>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
