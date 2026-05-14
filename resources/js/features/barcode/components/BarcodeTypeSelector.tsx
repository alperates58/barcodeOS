import { BarcodeTypeOption } from '../types/barcode';

interface BarcodeTypeSelectorProps {
    barcodeTypes: BarcodeTypeOption[];
    value: string | null;
    disabled?: boolean;
    onChange?: (value: string) => void;
}

export default function BarcodeTypeSelector({ barcodeTypes, value, disabled = false, onChange }: BarcodeTypeSelectorProps) {
    return (
        <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-slate-700">Barcode type</span>
            <select
                value={value ?? ''}
                disabled={disabled}
                onChange={(event) => onChange?.(event.target.value)}
                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:bg-slate-100"
            >
                <option value="">Select a barcode type</option>
                {barcodeTypes.map((barcodeType) => (
                    <option key={barcodeType.id} value={barcodeType.slug}>
                        {barcodeType.name}
                        {barcodeType.category ? ` (${barcodeType.category.name})` : ''}
                    </option>
                ))}
            </select>
            <span className="text-xs text-slate-500">
                Type configuration is loaded from the live config endpoint. This page validates configuration only and does not render or preview barcodes.
            </span>
        </label>
    );
}
