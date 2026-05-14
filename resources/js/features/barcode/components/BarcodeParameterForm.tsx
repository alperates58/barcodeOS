import { BarcodeParameterSchemaItem } from '../types/barcode';
import { normalizeParameters } from '../utils/normalizeParameters';

interface BarcodeParameterFormProps {
    parameters: BarcodeParameterSchemaItem[];
}

export default function BarcodeParameterForm({ parameters }: BarcodeParameterFormProps) {
    const normalizedParameters = normalizeParameters(parameters);

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {normalizedParameters.map((parameter) => (
                <div key={parameter.key} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">{parameter.label}</p>
                            <p className="text-xs text-slate-500">{parameter.key}</p>
                        </div>
                        <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">
                            {parameter.type}
                        </span>
                    </div>

                    {parameter.help_text && (
                        <p className="mt-3 text-sm leading-6 text-slate-600">{parameter.help_text}</p>
                    )}

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
            ))}
        </div>
    );
}
