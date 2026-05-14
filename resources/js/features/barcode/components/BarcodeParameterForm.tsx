import { BarcodeParameterSchemaItem } from '../types/barcode';
import { normalizeParameters } from '../utils/normalizeParameters';

interface BarcodeParameterFormProps {
    parameters: BarcodeParameterSchemaItem[];
    values: Record<string, string | number | boolean | string[]>;
    errors?: Record<string, string>;
    disabled?: boolean;
    onChange?: (key: string, value: string | number | boolean | string[]) => void;
}

const supportedTypes = new Set(['text', 'number', 'integer', 'boolean', 'select', 'multi_select', 'color']);

export default function BarcodeParameterForm({
    parameters,
    values,
    errors = {},
    disabled = false,
    onChange,
}: BarcodeParameterFormProps) {
    const normalizedParameters = normalizeParameters(parameters);

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {normalizedParameters.map((parameter) => {
                if (!supportedTypes.has(parameter.type)) {
                    return null;
                }

                const fieldId = `barcode-parameter-${parameter.key}`;
                const fieldError = errors[parameter.key];
                const value = values[parameter.key];

                return (
                    <div key={parameter.key} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <label htmlFor={fieldId} className="text-sm font-semibold text-slate-900">
                                    {parameter.label}
                                </label>
                                <p className="text-xs text-slate-500">{parameter.key}</p>
                            </div>
                            <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">
                                {parameter.type}
                            </span>
                        </div>

                        {parameter.help_text && (
                            <p className="mt-3 text-sm leading-6 text-slate-600">{parameter.help_text}</p>
                        )}

                        <div className="mt-4">
                            {parameter.type === 'text' && (
                                <input
                                    id={fieldId}
                                    type="text"
                                    disabled={disabled}
                                    value={typeof value === 'string' ? value : ''}
                                    onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                    className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                />
                            )}

                            {parameter.type === 'number' && (
                                <input
                                    id={fieldId}
                                    type="number"
                                    disabled={disabled}
                                    min={parameter.min ?? undefined}
                                    max={parameter.max ?? undefined}
                                    step="any"
                                    value={typeof value === 'number' || typeof value === 'string' ? value : ''}
                                    onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                    className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                />
                            )}

                            {parameter.type === 'integer' && (
                                <input
                                    id={fieldId}
                                    type="number"
                                    disabled={disabled}
                                    min={parameter.min ?? undefined}
                                    max={parameter.max ?? undefined}
                                    step="1"
                                    value={typeof value === 'number' || typeof value === 'string' ? value : ''}
                                    onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                    className="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                />
                            )}

                            {parameter.type === 'boolean' && (
                                <label className="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
                                    <input
                                        id={fieldId}
                                        type="checkbox"
                                        disabled={disabled}
                                        checked={Boolean(value)}
                                        onChange={(event) => onChange?.(parameter.key, event.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span className="text-sm text-slate-700">Enable this option</span>
                                </label>
                            )}

                            {parameter.type === 'select' && (
                                <select
                                    id={fieldId}
                                    disabled={disabled}
                                    value={typeof value === 'string' ? value : ''}
                                    onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                    className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                >
                                    <option value="">Select an option</option>
                                    {parameter.options.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                            )}

                            {parameter.type === 'multi_select' && (
                                <select
                                    id={fieldId}
                                    multiple
                                    disabled={disabled}
                                    value={Array.isArray(value) ? value : []}
                                    onChange={(event) =>
                                        onChange?.(
                                            parameter.key,
                                            Array.from(event.target.selectedOptions, (option) => option.value),
                                        )
                                    }
                                    className="min-h-32 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                >
                                    {parameter.options.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                            )}

                            {parameter.type === 'color' && (
                                <div className="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
                                    <input
                                        id={fieldId}
                                        type="color"
                                        disabled={disabled}
                                        value={typeof value === 'string' && value !== '' ? value : '#000000'}
                                        onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                        className="h-10 w-14 rounded border border-slate-300 bg-white p-1 disabled:cursor-not-allowed disabled:bg-slate-100"
                                    />
                                    <input
                                        type="text"
                                        disabled={disabled}
                                        value={typeof value === 'string' ? value : ''}
                                        onChange={(event) => onChange?.(parameter.key, event.target.value)}
                                        className="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
                                    />
                                </div>
                            )}
                        </div>

                        {fieldError && <p className="mt-3 text-sm text-rose-600">{fieldError}</p>}

                        <div className="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
                            <span className="rounded-full bg-slate-100 px-2.5 py-1">
                                {parameter.required ? 'Required' : 'Optional'}
                            </span>
                            {parameter.default !== null && (
                                <span className="rounded-full bg-slate-100 px-2.5 py-1">Default available</span>
                            )}
                            {parameter.options.length > 0 && (
                                <span className="rounded-full bg-slate-100 px-2.5 py-1">{parameter.options.length} options</span>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
