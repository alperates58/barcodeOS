import PublicLayout from '@/layouts/public-layout';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Globe2, Layers3, ShieldCheck } from 'lucide-react';

interface LandingProps extends SharedData {
    stats: {
        plan_count: number;
        barcode_type_count: number;
        category_count: number;
        language_count: number;
    };
    featuredTypes: Array<{
        name: string;
        slug: string;
        category: string | null;
        default_format: string;
        description: string | null;
    }>;
    categories: Array<{
        name: string;
        slug: string;
        description: string | null;
    }>;
    plansPreview: Array<{
        name: string;
        slug: string;
        description: string | null;
        monthly_price: number | string | null;
        currency: string;
    }>;
}

const statLabels: Record<keyof LandingProps['stats'], string> = {
    plan_count: 'Plans seeded',
    barcode_type_count: 'Barcode types ready',
    category_count: 'Catalog categories',
    language_count: 'Languages enabled',
};

function formatCurrency(amount: number | string | null, currency: string): string {
    if (amount === null) {
        return 'Custom';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(amount));
}

export default function Welcome() {
    const { auth, stats, featuredTypes, categories, plansPreview } = usePage<LandingProps>().props;

    return (
        <PublicLayout>
            <Head title="BarcodeOS" />

            <section className="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                <div className="space-y-6">
                    <div className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
                        <ShieldCheck className="size-4" />
                        SaaS-ready Laravel foundation with admin-managed controls
                    </div>

                    <div className="space-y-4">
                        <h1 className="max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 md:text-6xl">
                            Create professional barcodes with a foundation built for real SaaS growth.
                        </h1>
                        <p className="max-w-2xl text-lg leading-8 text-slate-600">
                            BarcodeOS is prepared for 1D, 2D, GS1, logistics and payment workflows with plans, entitlements,
                            barcode catalog management, Redis-backed queues and a secure admin foundation.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={auth.user ? route('dashboard') : route('register')}
                            className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            {auth.user ? 'Open Dashboard' : 'Start for Free'}
                            <ArrowRight className="size-4" />
                        </Link>
                        <Link
                            href={route('pricing')}
                            className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-950"
                        >
                            View Pricing
                        </Link>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {Object.entries(stats).map(([key, value]) => (
                            <div key={key} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p className="text-3xl font-semibold text-slate-950">{value}</p>
                                <p className="mt-1 text-sm text-slate-500">{statLabels[key as keyof LandingProps['stats']]}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 p-8 text-white shadow-2xl shadow-blue-950/10">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.28),transparent_38%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.18),transparent_32%)]" />
                    <div className="relative space-y-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.28em] text-blue-200 uppercase">Phase 0 + Phase 1</p>
                                <h2 className="mt-2 text-2xl font-semibold">Foundation Snapshot</h2>
                            </div>
                            <div className="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-blue-100">English default</div>
                        </div>

                        <div className="grid gap-3">
                            {featuredTypes.slice(0, 4).map((barcodeType) => (
                                <div key={barcodeType.slug} className="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-lg font-semibold">{barcodeType.name}</p>
                                            <p className="mt-1 text-sm text-slate-300">{barcodeType.description ?? barcodeType.category}</p>
                                        </div>
                                        <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-blue-100">
                                            {barcodeType.default_format}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="mt-16 grid gap-6 lg:grid-cols-3">
                {[
                    {
                        icon: Layers3,
                        title: 'Admin-managed catalog',
                        description: 'Barcode categories, barcode types and parameter schemas come from the database, not hard-coded lists.',
                    },
                    {
                        icon: CheckCircle2,
                        title: 'Entitlement-first architecture',
                        description: 'Plans, features and usage counters are ready for real SaaS access control before barcode rendering starts.',
                    },
                    {
                        icon: Globe2,
                        title: 'Deployment-safe operations',
                        description: 'Docker-first local setup and Coolify-ready deployment controls keep secrets in environment variables only.',
                    },
                ].map((pillar) => (
                    <div key={pillar.title} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <pillar.icon className="size-5" />
                        </div>
                        <h3 className="mt-5 text-xl font-semibold text-slate-950">{pillar.title}</h3>
                        <p className="mt-3 text-sm leading-7 text-slate-600">{pillar.description}</p>
                    </div>
                ))}
            </section>

            <section className="mt-16 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Supported categories</p>
                    <div className="mt-5 flex flex-wrap gap-3">
                        {categories.map((category) => (
                            <div key={category.slug} className="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700">
                                {category.name}
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Pricing foundation</p>
                            <h3 className="mt-2 text-2xl font-semibold text-slate-950">Database-driven plans from day one</h3>
                        </div>
                        <Link href={route('pricing')} className="text-sm font-semibold text-blue-700 transition hover:text-blue-900">
                            See all plans
                        </Link>
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {plansPreview.map((plan) => (
                            <div key={plan.slug} className="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <p className="text-sm font-semibold text-slate-500">{plan.name}</p>
                                <p className="mt-2 text-2xl font-semibold text-slate-950">
                                    {formatCurrency(plan.monthly_price, plan.currency)}
                                    <span className="ml-1 text-sm font-normal text-slate-500">/ month</span>
                                </p>
                                <p className="mt-3 text-sm text-slate-600">{plan.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
