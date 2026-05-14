import { BarcodeTypeConfig } from '../types/barcode';

interface BarcodeTypeSelectorProps {
    barcodeTypes: Pick<BarcodeTypeConfig, 'id' | 'name' | 'slug' | 'category' | 'description'>[];
    value: string | null;
    onChange?: (value: string) => void;
}

export default function BarcodeTypeSelector({ barcodeTypes, value, onChange }: BarcodeTypeSelectorProps) {
    return (
        <label className="flex flex-col gap-2">
            <span className="text-sm font-medium text-slate-700">Barcode type</span>
            <select
                value={value ?? ''}
                onChange={(event) => onChange?.(event.target.value)}
                className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500"
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
                This selector is only foundation UI for future config-driven generator flows. It does not generate or preview barcodes.
            </span>
        </label>
    );
}
