import BarcodeParameterForm from '@/features/barcode/components/BarcodeParameterForm';
import BarcodeTypeSelector from '@/features/barcode/components/BarcodeTypeSelector';
import ExportFormatSelector from '@/features/barcode/components/ExportFormatSelector';
import UsageLimitNotice from '@/features/barcode/components/UsageLimitNotice';
import {
    BarcodeCategoryOption,
    BarcodeTypeConfig,
    BarcodeTypeOption,
    BarcodeValidationResult,
    UsageSummaryItem,
} from '@/features/barcode/types/barcode';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';

interface GeneratorPageProps {
    barcodeCategories: BarcodeCategoryOption[];
    barcodeTypes: BarcodeTypeOption[];
    defaultBarcodeTypeSlug: string | null;
    currentPlan: {
        name: string;
        slug: string;
        description: string | null;
        trial_days: number;
        currency: string;
    } | null;
    usageSummary: Record<string, UsageSummaryItem>;
    routes: {
        config: string;
        validate: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Barcode Generator',
        href: '/app/barcodes/generator',
    },
];

const usageLabels: Record<string, string> = {
    daily_generation_limit: 'Daily generation quota status',
    monthly_generation_limit: 'Monthly generation quota status',
};

function buildParameterDefaults(config: BarcodeTypeConfig | null): Record<string, string | number | boolean | string[]> {
    if (!config) {
        return {};
    }

    return config.parameter_schema.reduce<Record<string, string | number | boolean | string[]>>((carry, parameter) => {
        if (parameter.type === 'multi_select') {
            carry[parameter.key] = Array.isArray(parameter.default) ? parameter.default : [];

            return carry;
        }

        if (parameter.type === 'boolean') {
            carry[parameter.key] = typeof parameter.default === 'boolean' ? parameter.default : false;

            return carry;
        }

        if (parameter.default !== null) {
            carry[parameter.key] = parameter.default as string | number | boolean | string[];

            return carry;
        }

        carry[parameter.key] = parameter.type === 'color' ? '#000000' : '';

        return carry;
    }, {});
}

function resolveDefaultFormat(config: BarcodeTypeConfig | null): string {
    if (!config) {
        return '';
    }

    const preferred = config.export_formats.find((format) => format.allowed && format.format === config.default_format);

    if (preferred) {
        return preferred.format;
    }

    return config.export_formats.find((format) => format.allowed)?.format ?? '';
}

function buildFieldErrors(result: BarcodeValidationResult | null): Record<string, string> {
    if (!result) {
        return {};
    }

    return result.errors.reduce<Record<string, string>>((carry, error) => {
        if (!error.field) {
            return carry;
        }

        if (error.field === 'data') {
            carry.data = error.message;

            return carry;
        }

        if (error.field === 'format') {
            carry.format = error.message;

            return carry;
        }

        if (error.field.startsWith('parameters.')) {
            carry[error.field.replace('parameters.', '')] = error.message;
        }

        return carry;
    }, {});
}

export default function BarcodeGeneratorPage() {
    const { barcodeCategories, barcodeTypes, defaultBarcodeTypeSlug, currentPlan, usageSummary, routes } = usePage<GeneratorPageProps>().props;
    const [selectedBarcodeType, setSelectedBarcodeType] = useState<string>(defaultBarcodeTypeSlug ?? '');
    const [barcodeData, setBarcodeData] = useState('');
    const [selectedFormat, setSelectedFormat] = useState('');
    const [parameterValues, setParameterValues] = useState<Record<string, string | number | boolean | string[]>>({});
    const [config, setConfig] = useState<BarcodeTypeConfig | null>(null);
    const [configLoading, setConfigLoading] = useState(false);
    const [configError, setConfigError] = useState<string | null>(null);
    const [validationResult, setValidationResult] = useState<BarcodeValidationResult | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const selectedTypeOption = useMemo(
        () => barcodeTypes.find((barcodeType) => barcodeType.slug === selectedBarcodeType) ?? null,
        [barcodeTypes, selectedBarcodeType],
    );

    useEffect(() => {
        if (!selectedBarcodeType) {
            setConfig(null);
            setSelectedFormat('');
            setParameterValues({});
            setValidationResult(null);

            return;
        }

        const controller = new AbortController();
        const endpoint = routes.config.replace('__BARCODE_TYPE__', encodeURIComponent(selectedBarcodeType));

        setConfigLoading(true);
        setConfigError(null);
        setValidationResult(null);

        fetch(endpoint, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`Config request failed with status ${response.status}.`);
                }

                return (await response.json()) as BarcodeTypeConfig;
            })
            .then((nextConfig) => {
                setConfig(nextConfig);
                setParameterValues(buildParameterDefaults(nextConfig));
                setSelectedFormat(resolveDefaultFormat(nextConfig));
            })
            .catch((error) => {
                if (controller.signal.aborted) {
                    return;
                }

                setConfig(null);
                setSelectedFormat('');
                setParameterValues({});
                setConfigError(error instanceof Error ? error.message : 'The barcode type configuration could not be loaded.');
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setConfigLoading(false);
                }
            });

        return () => controller.abort();
    }, [routes.config, selectedBarcodeType]);

    const fieldErrors = useMemo(() => buildFieldErrors(validationResult), [validationResult]);
    const activeCategoryCount = barcodeCategories.length;

    const handleParameterChange = (key: string, value: string | number | boolean | string[]) => {
        setParameterValues((current) => ({
            ...current,
            [key]: value,
        }));
    };

    const handleValidate = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedBarcodeType) {
            return;
        }

        setIsSubmitting(true);
        setValidationResult(null);

        try {
            const response = await fetch(routes.validate, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    barcode_type_slug: selectedBarcodeType,
                    data: barcodeData,
                    format: selectedFormat || null,
                    parameters: parameterValues,
                }),
            });

            if (!response.ok) {
                throw new Error(`Validation request failed with status ${response.status}.`);
            }

            const result = (await response.json()) as BarcodeValidationResult;
            setValidationResult(result);
        } catch (error) {
            setValidationResult({
                valid: false,
                errors: [
                    {
                        code: 'validation_request_failed',
                        message: error instanceof Error ? error.message : 'Validation could not be completed right now.',
                        field: null,
                        meta: {},
                    },
                ],
                normalized: {
                    data: null,
                    format: null,
                    parameters: {},
                },
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Barcode Generator" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Phase 3 foundation</p>
                            <h1 className="mt-2 text-3xl font-semibold text-slate-950">Barcode Generator</h1>
                            <p className="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                This page validates barcode configuration against live catalog metadata. Rendering, export output, history persistence,
                                and usage consumption are still future work.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                            <p className="font-semibold text-slate-900">{currentPlan?.name ?? 'Plan unavailable'}</p>
                            <p className="mt-1">Plan key: {currentPlan?.slug ?? 'unresolved'}</p>
                            <p className="mt-1">{activeCategoryCount} active barcode categories available in this catalog foundation.</p>
                        </div>
                    </div>

                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                        <UsageLimitNotice
                            title="Validate-only flow"
                            message="Validate-only flow does not consume usage. This only checks the current generation entitlement and limit status. No barcode is generated yet."
                        />
                        <UsageLimitNotice
                            title="Usage summary"
                            tone="info"
                            message={['daily_generation_limit', 'monthly_generation_limit']
                                .map((key) => {
                                    const usage = usageSummary[key];

                                    if (!usage) {
                                        return `${usageLabels[key] ?? key}: unavailable`;
                                    }

                                    if (usage.limit === null) {
                                        return `${usageLabels[key] ?? key}: no configured limit, validate-only flow still consumes nothing`;
                                    }

                                    return `${usageLabels[key] ?? key}: ${usage.used}/${usage.limit} used, ${usage.remaining ?? 0} remaining for future generation; validation does not consume usage`;
                                })
                                .join(' | ')}
                        />
                    </div>
                </section>

                <form onSubmit={handleValidate} className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                    <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="grid gap-5 md:grid-cols-2">
                            <BarcodeTypeSelector
                                barcodeTypes={barcodeTypes}
                                value={selectedBarcodeType}
                                disabled={isSubmitting}
                                onChange={setSelectedBarcodeType}
                            />

                            <ExportFormatSelector
                                formats={config?.export_formats ?? []}
                                value={selectedFormat}
                                disabled={configLoading || !config || isSubmitting}
                                error={fieldErrors.format ?? null}
                                onChange={setSelectedFormat}
                            />
                        </div>

                        <div className="mt-5">
                            <label htmlFor="barcode-data" className="text-sm font-medium text-slate-700">
                                Barcode data
                            </label>
                            <textarea
                                id="barcode-data"
                                value={barcodeData}
                                disabled={isSubmitting}
                                onChange={(event) => setBarcodeData(event.target.value)}
                                rows={5}
                                placeholder={selectedTypeOption?.example_value ?? 'Enter barcode content to validate'}
                                className="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                            />
                            {fieldErrors.data && <p className="mt-2 text-sm text-rose-600">{fieldErrors.data}</p>}
                            <p className="mt-2 text-xs text-slate-500">
                                Validation runs against barcode type rules and parameter schema only. No barcode image or document is produced.
                            </p>
                        </div>

                        <div className="mt-6">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-lg font-semibold text-slate-950">Dynamic parameters</h2>
                                    <p className="mt-1 text-sm text-slate-600">
                                        Parameter fields come from the selected barcode type configuration endpoint.
                                    </p>
                                </div>
                                {configLoading && (
                                    <span className="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">
                                        Loading config
                                    </span>
                                )}
                            </div>

                            {configError ? (
                                <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                                    {configError}
                                </div>
                            ) : config ? (
                                <div className="mt-4">
                                    <BarcodeParameterForm
                                        parameters={config.parameter_schema}
                                        values={parameterValues}
                                        errors={fieldErrors}
                                        disabled={isSubmitting}
                                        onChange={handleParameterChange}
                                    />
                                </div>
                            ) : (
                                <div className="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">
                                    Select a barcode type to load its resolved parameter schema.
                                </div>
                            )}
                        </div>
                    </section>

                    <aside className="flex flex-col gap-6">
                        <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Selected type access</h2>
                            {config?.access.can_use === false ? (
                                <div className="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                                    <p className="font-semibold">This barcode type is currently locked.</p>
                                    <p className="mt-2 leading-6">
                                        Missing features: {config.access.missing_features.length > 0 ? config.access.missing_features.join(', ') : 'Unknown'}
                                    </p>
                                </div>
                            ) : (
                                <div className="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
                                    <p className="font-semibold">Access metadata looks available.</p>
                                    <p className="mt-2 leading-6">
                                        You can validate this barcode type configuration. Rendering and export generation still remain disabled.
                                    </p>
                                </div>
                            )}

                            {selectedTypeOption?.description && (
                                <p className="mt-4 text-sm leading-6 text-slate-600">{selectedTypeOption.description}</p>
                            )}
                        </section>

                        <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Validation result</h2>

                            {validationResult ? (
                                <div className="mt-4 space-y-4">
                                    <div
                                        className={`rounded-2xl border p-4 text-sm ${
                                            validationResult.valid
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
                                                : 'border-rose-200 bg-rose-50 text-rose-950'
                                        }`}
                                    >
                                        <p className="font-semibold">
                                            {validationResult.valid
                                                ? 'Configuration passed validation.'
                                                : 'Configuration needs attention before rendering can exist in a later phase.'}
                                        </p>
                                    </div>

                                    {validationResult.errors.length > 0 && (
                                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <p className="text-sm font-semibold text-slate-900">Issues</p>
                                            <ul className="mt-3 space-y-2 text-sm text-slate-700">
                                                {validationResult.errors.map((error, index) => (
                                                    <li key={`${error.code}-${index}`} className="rounded-xl bg-white px-3 py-2">
                                                        <span className="font-medium">{error.code}</span>: {error.message}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    {validationResult.valid && (
                                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                            <p className="font-semibold text-slate-900">Normalized payload</p>
                                            <pre className="mt-3 overflow-x-auto whitespace-pre-wrap rounded-xl bg-slate-950 p-4 text-xs text-slate-100">
                                                {JSON.stringify(validationResult.normalized, null, 2)}
                                            </pre>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <p className="mt-4 text-sm leading-6 text-slate-600">
                                    Submit the current configuration to run side-effect-free validation. This does not create history, files, or usage records.
                                </p>
                            )}

                            <button
                                type="submit"
                                disabled={!selectedBarcodeType || configLoading || isSubmitting || !!configError}
                                className="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                {isSubmitting ? 'Checking Input...' : 'Validate Configuration'}
                            </button>
                        </section>
                    </aside>
                </form>
            </div>
        </AppLayout>
    );
}
