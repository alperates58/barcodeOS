import { BarcodeTypeExportAvailability } from '../types/barcode';

interface ExportFormatSelectorProps {
    formats: BarcodeTypeExportAvailability[];
    value: string | null;
    disabled?: boolean;
    error?: string | null;
    onChange?: (value: string) => void;
}

export default function ExportFormatSelector({ formats, value, disabled = false, error = null, onChange }: ExportFormatSelectorProps) {
    return (
        <div className="flex flex-col gap-2">
            <label className="text-sm font-medium text-slate-700" htmlFor="barcode-format">
                Export format
            </label>
            <select
                id="barcode-format"
                value={value ?? ''}
                disabled={disabled}
                onChange={(event) => onChange?.(event.target.value)}
                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
            >
                <option value="">Choose a format</option>
                {formats.map((format) => (
                    <option key={format.format} value={format.format} disabled={!format.allowed}>
                        {format.format.toUpperCase()}
                        {!format.allowed && format.feature_key ? ` (requires ${format.feature_key})` : ''}
                    </option>
                ))}
            </select>
            {error && <p className="text-sm text-rose-600">{error}</p>}
            {formats.some((format) => !format.allowed) && (
                <p className="text-xs text-slate-500">
                    Locked formats stay visible for transparency and remain disabled until the required feature is available.
                </p>
            )}
            <span className="text-xs text-slate-500">Availability here is entitlement metadata only. No export generation is implemented yet.</span>
        </div>
    );
}
