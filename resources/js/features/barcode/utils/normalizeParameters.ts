import { BarcodeParameterSchemaItem } from '../types/barcode';

export function normalizeParameters(parameters: BarcodeParameterSchemaItem[]): BarcodeParameterSchemaItem[] {
    return [...parameters]
        .filter((parameter) => parameter.key.trim() !== '')
        .sort((left, right) => {
            if (left.sort_order === right.sort_order) {
                return left.label.localeCompare(right.label);
            }

            return left.sort_order - right.sort_order;
        });
}
