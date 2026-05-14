interface UsageLimitNoticeProps {
    title?: string;
    message: string;
}

export default function UsageLimitNotice({
    title = 'Generator foundation notice',
    message,
}: UsageLimitNoticeProps) {
    return (
        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p className="font-semibold">{title}</p>
            <p className="mt-2 leading-6">{message}</p>
        </div>
    );
}
