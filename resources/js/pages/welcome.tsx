import { buttonVariants } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Blocks,
    Cable,
    CheckCircle2,
    ChevronRight,
    DatabaseZap,
    Layers3,
    LockKeyhole,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface BarcodeCategorySummary {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    types: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
}

interface BarcodeTypeSummary {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    example_value: string | null;
    default_format: string | null;
    supported_export_formats: string[];
    required_features: string[];
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
}

interface PlanPreview {
    name: string;
    slug: string;
    description: string | null;
    monthly_price: number | string | null;
    yearly_price: number | string | null;
    currency: string;
    cta_label: string;
    is_recommended: boolean;
}

interface LandingProps extends SharedData {
    heroStats: {
        plan_count: number;
        barcode_type_count: number;
        category_count: number;
        language_count: number;
    };
    barcodeCategories: BarcodeCategorySummary[];
    barcodeTypes: BarcodeTypeSummary[];
    featuredBarcodeTypes: Array<{
        id: number;
        name: string;
        slug: string;
        description: string | null;
        default_format: string;
        supported_export_formats: string[];
        category: {
            id: number;
            name: string;
            slug: string;
        } | null;
    }>;
    plansPreview: PlanPreview[];
    generatorPanel: {
        default_barcode_type_slug: string | null;
        selected_type: BarcodeTypeSummary | null;
        categories: BarcodeCategorySummary[];
        export_formats: string[];
        status: {
            eyebrow: string;
            message: string;
            validate_message: string;
        };
    };
    teaserSections: {
        api: {
            title: string;
            description: string;
        };
        bulk: {
            title: string;
            description: string;
        };
        faq: Array<{
            question: string;
            answer: string;
        }>;
        trust: string[];
    };
}

const statLabels: Record<keyof LandingProps['heroStats'], string> = {
    plan_count: 'Public plans',
    barcode_type_count: 'Active barcode types',
    category_count: 'Active categories',
    language_count: 'Enabled languages',
};

const useCases = [
    'Retail labeling for products and shelves',
    'Warehouse and logistics barcode operations',
    'GS1-ready product identification workflows',
    'Developer-first integrations planned for later phases',
];

function formatPrice(amount: number | string | null, currency: string): string {
    if (amount === null) {
        return 'Custom';
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(amount));
}

function formatFormatChip(format: string): string {
    return format.toUpperCase();
}

export default function Welcome() {
    const { auth, heroStats, barcodeCategories, barcodeTypes, featuredBarcodeTypes, plansPreview, generatorPanel, teaserSections } =
        usePage<LandingProps>().props;

    const [selectedTypeSlug, setSelectedTypeSlug] = useState(generatorPanel.default_barcode_type_slug ?? '');
    const [sampleInput, setSampleInput] = useState(generatorPanel.selected_type?.example_value ?? '');

    const selectedType = useMemo(
        () => barcodeTypes.find((barcodeType) => barcodeType.slug === selectedTypeSlug) ?? generatorPanel.selected_type,
        [barcodeTypes, generatorPanel.selected_type, selectedTypeSlug],
    );

    const selectedCategory = selectedType?.category
        ? barcodeCategories.find((category) => category.slug === selectedType.category?.slug) ?? null
        : null;

    const selectedExportFormats = selectedType?.supported_export_formats ?? generatorPanel.export_formats;
    const primaryCtaHref = auth.user ? route('app.barcodes.generator') : route('login');
    const primaryCtaLabel = auth.user ? 'Open Generator' : 'Sign in to validate';
    const secondaryCtaHref = auth.user ? route('dashboard') : route('register');
    const secondaryCtaLabel = auth.user ? 'Open App' : 'Get Started';

    return (
        <PublicLayout>
            <Head title="BarcodeOS" />

            <section className="relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(135deg,#0f172a_0%,#13213f_32%,#123a7a_100%)] px-6 py-8 text-white shadow-2xl shadow-blue-950/10 md:px-10 md:py-12">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(96,165,250,0.22),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(34,197,94,0.14),transparent_28%)]" />
                <div className="relative grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div className="space-y-6">
                        <div className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-blue-100">
                            <ShieldCheck className="size-4" />
                            Phase 3 public discovery foundation
                        </div>

                        <div className="space-y-4">
                            <h1 className="max-w-4xl text-4xl font-semibold tracking-tight md:text-6xl">
                                Professional barcode generation, with a modern SaaS foundation before rendering goes live.
                            </h1>
                            <p className="max-w-3xl text-base leading-8 text-slate-200 md:text-lg">
                                Explore BarcodeOS for 1D, 2D, GS1 and retail barcode workflows. The public homepage highlights the live catalog,
                                pricing foundation and planned API and bulk paths without pretending rendering or downloads already exist.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={primaryCtaHref}
                                className="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                            >
                                {primaryCtaLabel}
                                <ArrowRight className="size-4" />
                            </Link>
                            <Link
                                href={secondaryCtaHref}
                                className="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                            >
                                {secondaryCtaLabel}
                            </Link>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            {Object.entries(heroStats).map(([key, value]) => (
                                <div key={key} className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                                    <p className="text-3xl font-semibold text-white">{value}</p>
                                    <p className="mt-1 text-sm text-slate-300">{statLabels[key as keyof LandingProps['heroStats']]}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-[1.75rem] border border-white/10 bg-white/10 p-6 backdrop-blur">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.26em] text-blue-100 uppercase">Featured types</p>
                                <h2 className="mt-2 text-2xl font-semibold">Catalog ready for discovery</h2>
                            </div>
                            <div className="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-blue-50">
                                Read-only public view
                            </div>
                        </div>

                        <div className="mt-6 grid gap-3">
                            {featuredBarcodeTypes.map((barcodeType) => (
                                <div key={barcodeType.slug} className="rounded-2xl border border-white/10 bg-slate-950/20 p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-lg font-semibold">{barcodeType.name}</p>
                                            <p className="mt-1 text-sm text-slate-300">
                                                {barcodeType.description ?? barcodeType.category?.name ?? 'Catalog-managed barcode type'}
                                            </p>
                                        </div>
                                        <div className="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold text-blue-100">
                                            {barcodeType.default_format}
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {barcodeType.supported_export_formats.map((format) => (
                                            <span
                                                key={`${barcodeType.slug}-${format}`}
                                                className="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-200"
                                            >
                                                {format.toUpperCase()} planned output
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section id="generator" className="mt-16 scroll-mt-28">
                <div className="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.24em] text-blue-700 uppercase">{generatorPanel.status.eyebrow}</p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">Public generator landing panel</h2>
                        <p className="mt-3 max-w-3xl text-sm leading-7 text-slate-600 md:text-base">
                            The layout previews how BarcodeOS organizes barcode type discovery, input preparation and future preview workflows. The
                            panel is intentionally non-rendering and does not call validation or config endpoints on the public site.
                        </p>
                    </div>
                    <div className="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                        <span className="font-semibold">Phase boundary:</span> {generatorPanel.status.message}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[0.92fr_1.1fr_0.95fr]">
                    <section className="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <Layers3 className="size-4 text-blue-700" />
                            Barcode catalog
                        </div>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Active categories and types below are loaded from the database and grouped for quick discovery.
                        </p>

                        <div className="mt-5 space-y-4">
                            {generatorPanel.categories.map((category) => (
                                <div key={category.slug} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-slate-950">{category.name}</p>
                                            <p className="text-xs text-slate-500">{category.types.length} active type{category.types.length === 1 ? '' : 's'}</p>
                                        </div>
                                        <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                                            {category.slug}
                                        </span>
                                    </div>

                                    <div className="mt-3 space-y-2">
                                        {category.types.map((barcodeType) => {
                                            const isSelected = barcodeType.slug === selectedTypeSlug;

                                            return (
                                                <button
                                                    key={barcodeType.slug}
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedTypeSlug(barcodeType.slug);
                                                        const nextType = barcodeTypes.find((item) => item.slug === barcodeType.slug);
                                                        setSampleInput(nextType?.example_value ?? '');
                                                    }}
                                                    className={cn(
                                                        'flex w-full items-center justify-between rounded-2xl border px-3 py-3 text-left text-sm transition',
                                                        isSelected
                                                            ? 'border-blue-300 bg-blue-50 text-blue-950'
                                                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:text-slate-950',
                                                    )}
                                                >
                                                    <span className="font-medium">{barcodeType.name}</span>
                                                    <ChevronRight className={cn('size-4', isSelected ? 'text-blue-700' : 'text-slate-400')} />
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p className="text-sm font-semibold tracking-[0.22em] text-slate-500 uppercase">Input foundation</p>
                                <h3 className="mt-2 text-2xl font-semibold text-slate-950">
                                    {selectedType?.name ?? 'Select a barcode type'}
                                </h3>
                                <p className="mt-2 text-sm leading-7 text-slate-600">
                                    {selectedType?.description ??
                                        'This panel mirrors the future generator workflow while keeping rendering and download actions disabled.'}
                                </p>
                            </div>

                            {selectedCategory && (
                                <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <p className="font-semibold text-slate-900">{selectedCategory.name}</p>
                                    <p className="mt-1">Catalog-driven category</p>
                                </div>
                            )}
                        </div>

                        <div className="mt-6 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                            <div>
                                <label htmlFor="homepage-barcode-input" className="text-sm font-medium text-slate-700">
                                    Barcode data
                                </label>
                                <textarea
                                    id="homepage-barcode-input"
                                    value={sampleInput}
                                    onChange={(event) => setSampleInput(event.target.value)}
                                    rows={6}
                                    placeholder={selectedType?.example_value ?? 'Enter data to prepare for validation inside the app'}
                                    className="mt-2 w-full rounded-[1.25rem] border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500"
                                />
                                <p className="mt-2 text-xs leading-6 text-slate-500">
                                    Local input only. The public homepage never sends this data to validation or generation services.
                                </p>
                            </div>

                            <div className="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4">
                                <p className="text-sm font-semibold text-slate-900">Selected type info</p>
                                <div className="mt-3 space-y-3 text-sm text-slate-600">
                                    <div className="rounded-2xl bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase">Default format</p>
                                        <p className="mt-2 font-medium text-slate-900">
                                            {selectedType?.default_format ? selectedType.default_format.toUpperCase() : 'Not configured'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase">Example value</p>
                                        <p className="mt-2 break-all font-medium text-slate-900">
                                            {selectedType?.example_value ?? 'Managed from admin'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl bg-white px-4 py-3">
                                        <p className="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase">Feature access model</p>
                                        <p className="mt-2 font-medium text-slate-900">
                                            {selectedType?.required_features.length ? selectedType.required_features.join(', ') : 'No extra feature labels'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 rounded-[1.25rem] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
                            <p className="font-semibold">Validate-only path lives inside the app.</p>
                            <p className="mt-2 leading-6">{generatorPanel.status.validate_message}</p>
                        </div>
                    </section>

                    <aside className="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <Blocks className="size-4 text-blue-700" />
                            Preview placeholder
                        </div>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            This area reserves space for Phase 4 preview and export flows without showing a fake barcode image.
                        </p>

                        <div className="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-[linear-gradient(180deg,#f8fafc_0%,#eef2ff_100%)] p-5">
                            <div className="rounded-[1.25rem] border border-white bg-white/80 p-5 shadow-sm">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase">Preview state</p>
                                        <p className="mt-2 text-lg font-semibold text-slate-950">Rendering not active yet</p>
                                    </div>
                                    <div className="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900">
                                        Phase 4
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3">
                                    <div className="rounded-2xl bg-slate-100 px-4 py-4 text-sm text-slate-600">
                                        Barcode previews, secure exports and file persistence will be connected after the rendering engine is introduced.
                                    </div>
                                    <div className="rounded-2xl bg-slate-950 px-4 py-4 text-sm text-slate-100">
                                        No preview image, no file output, no download behavior and no record creation happen on this page.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-5">
                            <p className="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase">Export formats</p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {selectedExportFormats.length > 0 ? (
                                    selectedExportFormats.map((format) => (
                                        <span
                                            key={format}
                                            className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                                        >
                                            {formatFormatChip(format)} planned
                                        </span>
                                    ))
                                ) : (
                                    <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                        Formats managed from admin
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="mt-6 space-y-3">
                            <Link href={primaryCtaHref} className={cn(buttonVariants({ size: 'lg' }), 'w-full rounded-2xl')}>
                                {primaryCtaLabel}
                            </Link>
                            <Link
                                href={secondaryCtaHref}
                                className={cn(
                                    buttonVariants({ variant: 'outline', size: 'lg' }),
                                    'w-full rounded-2xl border-slate-300 text-slate-700',
                                )}
                            >
                                {secondaryCtaLabel}
                            </Link>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="barcode-types" className="mt-16 scroll-mt-28">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Supported categories and types</p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Real catalog data, grouped for business discovery</h2>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
                        Only active categories and active barcode types are shown on the public homepage.
                    </div>
                </div>

                <div className="mt-6 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                    {barcodeCategories.map((category) => (
                        <article key={category.slug} className="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <h3 className="text-xl font-semibold text-slate-950">{category.name}</h3>
                                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                    {category.types.length} types
                                </span>
                            </div>
                            <p className="mt-2 text-sm leading-6 text-slate-600">
                                {category.description ?? 'Admin-managed category structure for pricing, access and future rendering workflows.'}
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {category.types.map((barcodeType) => (
                                    <button
                                        key={`${category.slug}-${barcodeType.slug}`}
                                        type="button"
                                        onClick={() => {
                                            setSelectedTypeSlug(barcodeType.slug);
                                            const nextType = barcodeTypes.find((item) => item.slug === barcodeType.slug);
                                            setSampleInput(nextType?.example_value ?? '');
                                        }}
                                        className="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                    >
                                        {barcodeType.name}
                                    </button>
                                ))}
                            </div>
                        </article>
                    ))}
                </div>
            </section>

            <section className="mt-16 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div className="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Pricing teaser</p>
                            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Subscription foundation backed by live plans</h2>
                        </div>
                        <Link href={route('pricing')} className="text-sm font-semibold text-blue-700 transition hover:text-blue-900">
                            View pricing
                        </Link>
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        {plansPreview.length > 0 ? (
                            plansPreview.map((plan) => (
                                <article
                                    key={plan.slug}
                                    className={cn(
                                        'rounded-[1.5rem] border p-5',
                                        plan.is_recommended ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-slate-50 text-slate-950',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <p className={cn('text-sm font-semibold', plan.is_recommended ? 'text-blue-100' : 'text-slate-500')}>{plan.name}</p>
                                        {plan.is_recommended && (
                                            <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-blue-100">Recommended</span>
                                        )}
                                    </div>
                                    <p className="mt-3 text-3xl font-semibold">
                                        {formatPrice(plan.monthly_price, plan.currency)}
                                        {plan.monthly_price !== null && (
                                            <span className={cn('ml-1 text-sm font-normal', plan.is_recommended ? 'text-slate-300' : 'text-slate-500')}>
                                                / month
                                            </span>
                                        )}
                                    </p>
                                    <p className={cn('mt-3 text-sm leading-7', plan.is_recommended ? 'text-slate-200' : 'text-slate-600')}>{plan.description}</p>
                                    <p className={cn('mt-4 text-xs font-medium', plan.is_recommended ? 'text-slate-300' : 'text-slate-500')}>
                                        {plan.yearly_price === null
                                            ? 'Managed from admin or contact sales'
                                            : `${formatPrice(plan.yearly_price, plan.currency)} yearly`}
                                    </p>
                                    <div className="mt-5 inline-flex rounded-full border border-current/10 px-3 py-1 text-xs font-semibold">
                                        {plan.cta_label}
                                    </div>
                                </article>
                            ))
                        ) : (
                            <div className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                                Public plan pricing will appear here as active plans are managed from admin.
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid gap-6">
                    <section id="api" className="scroll-mt-28 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex items-start gap-4">
                            <div className="flex size-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                                <Cable className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">API foundation</p>
                                <h3 className="mt-2 text-2xl font-semibold text-slate-950">{teaserSections.api.title}</h3>
                                <p className="mt-3 text-sm leading-7 text-slate-600">{teaserSections.api.description}</p>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex items-start gap-4">
                            <div className="flex size-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <DatabaseZap className="size-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Bulk foundation</p>
                                <h3 className="mt-2 text-2xl font-semibold text-slate-950">{teaserSections.bulk.title}</h3>
                                <p className="mt-3 text-sm leading-7 text-slate-600">{teaserSections.bulk.description}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>

            <section id="use-cases" className="mt-16 scroll-mt-28 grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <div className="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Business use cases</p>
                    <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Built for operational barcode work, not demo screens</h2>

                    <div className="mt-6 grid gap-3">
                        {useCases.map((useCase) => (
                            <div key={useCase} className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <CheckCircle2 className="mt-0.5 size-5 text-blue-700" />
                                <p className="text-sm leading-7 text-slate-700">{useCase}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-[1.75rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-950/10">
                    <p className="text-sm font-semibold tracking-[0.24em] text-blue-100 uppercase">Trust and readiness</p>
                    <h2 className="mt-2 text-3xl font-semibold tracking-tight">A premium discovery surface with honest product boundaries</h2>

                    <div className="mt-6 grid gap-3">
                        {teaserSections.trust.map((item) => (
                            <div key={item} className="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                <BadgeCheck className="mt-0.5 size-5 text-blue-300" />
                                <p className="text-sm leading-7 text-slate-100">{item}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section id="faq" className="mt-16 scroll-mt-28 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">FAQ teaser</p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Clear expectations before Phase 4 rendering starts</h2>
                    </div>
                    <div className="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900">
                        <LockKeyhole className="size-4" />
                        Rendering and download remain future work
                    </div>
                </div>

                <div className="mt-6 grid gap-4 lg:grid-cols-3">
                    {teaserSections.faq.map((item) => (
                        <article key={item.question} className="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <div className="flex items-start gap-3">
                                <Sparkles className="mt-1 size-4 text-blue-700" />
                                <div>
                                    <h3 className="text-lg font-semibold text-slate-950">{item.question}</h3>
                                    <p className="mt-3 text-sm leading-7 text-slate-600">{item.answer}</p>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            </section>

            <section className="mt-16 rounded-[2rem] border border-slate-200 bg-[linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#f8fafc_100%)] p-8 shadow-sm">
                <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div>
                        <p className="text-sm font-semibold tracking-[0.24em] text-blue-700 uppercase">Next step</p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Move from discovery into the authenticated generator foundation</h2>
                        <p className="mt-3 max-w-3xl text-sm leading-7 text-slate-600 md:text-base">
                            BarcodeOS already exposes the authenticated generator page and validate-only workflow. Public discovery now bridges product
                            clarity and SaaS positioning before barcode rendering, export files and history persistence are introduced in later phases.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 md:flex-row lg:justify-end">
                        <Link href={primaryCtaHref} className={cn(buttonVariants({ size: 'lg' }), 'rounded-2xl')}>
                            {primaryCtaLabel}
                        </Link>
                        <Link
                            href={route('pricing')}
                            className={cn(buttonVariants({ variant: 'outline', size: 'lg' }), 'rounded-2xl border-slate-300 text-slate-700')}
                        >
                            Review Plans
                        </Link>
                    </div>
                </div>
            </section>

            <section className="sr-only">
                <h2>Professional barcode generation foundation</h2>
                <p>BarcodeOS supports 1D, 2D, GS1 and retail barcode workflows with subscription-based limits.</p>
                <p>Bulk generation is planned for a later phase.</p>
                <p>API access is planned for a later phase.</p>
            </section>
        </PublicLayout>
    );
}
