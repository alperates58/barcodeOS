interface UsageLimitNoticeProps {
    title?: string;
    message: string;
    tone?: 'warning' | 'info';
}

export default function UsageLimitNotice({
    title = 'Generator foundation notice',
    message,
    tone = 'warning',
}: UsageLimitNoticeProps) {
    const styles =
        tone === 'info'
            ? 'border-blue-200 bg-blue-50 text-blue-950'
            : 'border-amber-200 bg-amber-50 text-amber-900';

    return (
        <div className={`rounded-2xl border p-4 text-sm ${styles}`}>
            <p className="font-semibold">{title}</p>
            <p className="mt-2 leading-6">{message}</p>
        </div>
    );
}
