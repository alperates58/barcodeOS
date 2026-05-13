import PublicLayout from '@/layouts/public-layout';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Check, Sparkles } from 'lucide-react';

interface PricingPageProps extends SharedData {
    plans: Array<{
        name: string;
        slug: string;
        description: string | null;
        monthly_price: number | string | null;
        yearly_price: number | string | null;
        currency: string;
        trial_days: number;
        is_recommended: boolean;
        cta_label: string;
        highlights: Array<{
            key: string;
            name: string;
            limit_value: number | null;
            value: string | null;
            value_type: string;
        }>;
    }>;
}

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

export default function Pricing() {
    const { auth, plans } = usePage<PricingPageProps>().props;
    const ctaHref = auth.user ? route('dashboard') : route('register');

    return (
        <PublicLayout>
            <Head title="Pricing" />

            <section className="mx-auto max-w-5xl text-center">
                <div className="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800">
                    <Sparkles className="size-4" />
                    Pricing data is loaded from the database
                </div>
                <h1 className="mt-6 text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">Flexible plans for barcode teams and SaaS growth</h1>
                <p className="mt-4 text-lg leading-8 text-slate-600">
                    This phase prepares plan, feature and pricing management. Real payment checkout is intentionally not active yet.
                </p>
            </section>

            <section className="mt-12 grid gap-6 xl:grid-cols-5">
                {plans.map((plan) => (
                    <article
                        key={plan.slug}
                        className={`relative flex flex-col rounded-3xl border p-6 shadow-sm transition ${
                            plan.is_recommended ? 'border-blue-500 bg-slate-950 text-white shadow-xl shadow-blue-950/10 xl:col-span-2' : 'border-slate-200 bg-white text-slate-950 xl:col-span-1'
                        }`}
                    >
                        {plan.is_recommended && (
                            <div className="absolute right-5 top-5 rounded-full bg-blue-500/20 px-3 py-1 text-xs font-semibold text-blue-100">Recommended</div>
                        )}

                        <div>
                            <p className={`text-sm font-semibold ${plan.is_recommended ? 'text-blue-100' : 'text-slate-500'}`}>{plan.name}</p>
                            <p className="mt-3 text-4xl font-semibold">
                                {formatCurrency(plan.monthly_price, plan.currency)}
                                <span className={`ml-1 text-sm font-normal ${plan.is_recommended ? 'text-slate-300' : 'text-slate-500'}`}>/ month</span>
                            </p>
                            <p className={`mt-2 text-sm ${plan.is_recommended ? 'text-slate-300' : 'text-slate-600'}`}>{plan.description}</p>
                        </div>

                        <div className={`mt-6 rounded-2xl border p-4 ${plan.is_recommended ? 'border-white/10 bg-white/5' : 'border-slate-200 bg-slate-50'}`}>
                            <p className={`text-xs font-semibold tracking-[0.22em] uppercase ${plan.is_recommended ? 'text-blue-100' : 'text-slate-500'}`}>Billing foundation</p>
                            <p className={`mt-2 text-sm ${plan.is_recommended ? 'text-slate-200' : 'text-slate-700'}`}>
                                {formatCurrency(plan.yearly_price, plan.currency)} yearly
                                {plan.trial_days > 0 ? ` • ${plan.trial_days}-day trial` : ' • Trial configurable from admin'}
                            </p>
                        </div>

                        <ul className="mt-6 flex-1 space-y-3">
                            {plan.highlights.length > 0 ? (
                                plan.highlights.map((highlight) => (
                                    <li key={`${plan.slug}-${highlight.key}`} className="flex items-start gap-3 text-sm">
                                        <Check className={`mt-0.5 size-4 shrink-0 ${plan.is_recommended ? 'text-blue-300' : 'text-blue-700'}`} />
                                        <span className={plan.is_recommended ? 'text-slate-100' : 'text-slate-700'}>
                                            {highlight.name}
                                            {highlight.limit_value !== null ? ` (${highlight.limit_value})` : ''}
                                        </span>
                                    </li>
                                ))
                            ) : (
                                <li className={`text-sm ${plan.is_recommended ? 'text-slate-200' : 'text-slate-600'}`}>Feature entitlements will appear here as admins refine plan mappings.</li>
                            )}
                        </ul>

                        <Link
                            href={ctaHref}
                            className={`mt-8 inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold transition ${
                                plan.is_recommended ? 'bg-white text-slate-950 hover:bg-slate-100' : 'bg-slate-950 text-white hover:bg-slate-800'
                            }`}
                        >
                            {plan.cta_label}
                        </Link>
                    </article>
                ))}
            </section>

            <section className="mt-12 rounded-3xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 shadow-sm">
                Subscribe/Buy Now actions are intentionally disabled in this phase. Pricing, plans and feature entitlements are live from the database, while checkout and provider-specific payment flows will be added in a later billing phase.
            </section>
        </PublicLayout>
    );
}
