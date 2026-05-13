import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BarChart3, CreditCard, History, Layers3 } from 'lucide-react';

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
    stats: {
        generated_count: number;
        export_count: number;
    };
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
    const { currentPlan, usageSummary, recentBarcodes, stats } = usePage<DashboardPageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Current plan</p>
                                <h1 className="mt-2 text-3xl font-semibold text-slate-950">{currentPlan?.name ?? 'No plan resolved yet'}</h1>
                                <p className="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                    {currentPlan?.description ?? 'Plan resolution is ready. Billing and subscription flows will be connected in a later phase.'}
                                </p>
                            </div>

                            <Link
                                href={route('pricing')}
                                className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                View Plans
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>

                        <div className="mt-6 grid gap-4 md:grid-cols-3">
                            {[
                                { label: 'Generated records', value: stats.generated_count, icon: BarChart3 },
                                { label: 'Stored exports', value: stats.export_count, icon: Layers3 },
                                { label: 'Trial foundation', value: currentPlan?.trial_days ?? 0, icon: CreditCard, suffix: 'days' },
                            ].map((item) => (
                                <div key={item.label} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div className="flex items-center gap-2 text-slate-500">
                                        <item.icon className="size-4" />
                                        <span className="text-sm font-medium">{item.label}</span>
                                    </div>
                                    <p className="mt-4 text-3xl font-semibold text-slate-950">
                                        {item.value}
                                        {item.suffix ? <span className="ml-1 text-sm font-normal text-slate-500">{item.suffix}</span> : null}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-lg shadow-blue-950/10">
                        <p className="text-sm font-semibold tracking-[0.24em] text-blue-100 uppercase">Foundation status</p>
                        <h2 className="mt-2 text-2xl font-semibold">Barcode engine is intentionally not active yet</h2>
                        <p className="mt-3 text-sm leading-7 text-slate-300">
                            This dashboard is connected to real plan and usage foundation data. Barcode rendering, payments, bulk generation and API generation remain outside this task by design.
                        </p>

                        <div className="mt-6 space-y-3">
                            {[
                                'Plans and feature entitlements are stored in the database.',
                                'Usage counters can resolve daily, monthly, API and bulk limits safely.',
                                'Pricing and admin foundations are ready for the next phase.',
                            ].map((line) => (
                                <div key={line} className="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100">
                                    <History className="mt-0.5 size-4 shrink-0 text-blue-300" />
                                    <span>{line}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {Object.entries(usageSummary).map(([key, usage]) => (
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
                            {usage.limit !== null && (
                                <div className="mt-4 h-2 rounded-full bg-slate-100">
                                    <div
                                        className="h-2 rounded-full bg-blue-600 transition-all"
                                        style={{ width: `${Math.min(100, Math.round((usage.used / usage.limit) * 100))}%` }}
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
                            <p className="mt-2 text-sm text-slate-600">This placeholder is wired to the real history table and will populate once the barcode engine phase begins.</p>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
