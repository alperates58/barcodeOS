import { BarcodeTypeExportAvailability } from '../types/barcode';

interface ExportFormatSelectorProps {
    formats: BarcodeTypeExportAvailability[];
    value: string | null;
    onChange?: (value: string) => void;
}

export default function ExportFormatSelector({ formats, value, onChange }: ExportFormatSelectorProps) {
    return (
        <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-slate-700">Export format</span>
            <select
                value={value ?? ''}
                onChange={(event) => onChange?.(event.target.value)}
                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500"
            >
                <option value="">Choose a format</option>
                {formats.map((format) => (
                    <option key={format.format} value={format.format} disabled={!format.allowed}>
                        {format.format.toUpperCase()}
                        {!format.allowed && format.feature_key ? ` (requires ${format.feature_key})` : ''}
                    </option>
                ))}
            </select>
            <span className="text-xs text-slate-500">Availability here is entitlement metadata only. No export generation is implemented yet.</span>
        </label>
    );
}
