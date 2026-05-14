export type BarcodeParameterType =
    | 'text'
    | 'number'
    | 'integer'
    | 'boolean'
    | 'select'
    | 'multi_select'
    | 'color';

export interface BarcodeParameterSchemaItem {
    key: string;
    label: string;
    type: BarcodeParameterType;
    required: boolean;
    default: string | number | boolean | string[] | null;
    has_default?: boolean;
    min: string | number | null;
    max: string | number | null;
    options: string[];
    help_text: string | null;
    available_features: string[];
    sort_order: number;
    source: string;
}

export interface BarcodeTypeCategorySummary {
    id: number;
    name: string;
    slug: string;
}

export interface BarcodeTypeExportAvailability {
    format: string;
    allowed: boolean;
    feature_key: string | null;
}

export interface BarcodeTypeConfig {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: BarcodeTypeCategorySummary | null;
    example_value: string | null;
    default_format: string | null;
    supported_export_formats: string[];
    required_features: string[];
    parameter_schema: BarcodeParameterSchemaItem[];
    validation_rules: Record<string, unknown>;
    access: {
        can_use: boolean;
        missing_features: string[];
    };
    export_formats: BarcodeTypeExportAvailability[];
}

export interface BarcodeTypeOption {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    example_value?: string | null;
    category: BarcodeTypeCategorySummary | null;
}

export interface BarcodeCategoryOption {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

export interface UsageSummaryItem {
    limit: number | null;
    used: number;
    remaining: number | null;
    period_type: string | null;
    source: string;
}

export interface BarcodeValidationError {
    code: string;
    message: string;
    field: string | null;
    meta: Record<string, unknown>;
}

export interface BarcodeValidationResult {
    valid: boolean;
    errors: BarcodeValidationError[];
    normalized: {
        data: string | null;
        format: string | null;
        parameters: Record<string, unknown>;
    };
}
