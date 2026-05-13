import AppLogoIcon from '@/components/app-logo-icon';
import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface PublicLayoutProps {
    children: React.ReactNode;
}

export default function PublicLayout({ children }: PublicLayoutProps) {
    const { auth, app } = usePage<SharedData>().props;

    return (
        <div className="min-h-screen bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_42%,#f8fafc_100%)] text-slate-950">
            <div className="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6 lg:px-8">
                <header className="mb-10 rounded-2xl border border-slate-200/80 bg-white/80 px-5 py-4 shadow-sm backdrop-blur">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <Link href={route('home')} className="flex items-center gap-3">
                            <div className="flex size-11 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-lg shadow-blue-900/10">
                                <AppLogoIcon className="size-6 fill-current" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold tracking-[0.28em] text-blue-700 uppercase">{app.name}</p>
                                <p className="text-sm text-slate-500">Professional barcode SaaS foundation</p>
                            </div>
                        </Link>

                        <div className="flex flex-wrap items-center gap-3">
                            <Link href={route('pricing')} className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), 'text-slate-700')}>
                                Pricing
                            </Link>
                            {auth.user ? (
                                <Link href={route('dashboard')} className={buttonVariants({ variant: 'default', size: 'sm' })}>
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')} className={cn(buttonVariants({ variant: 'ghost', size: 'sm' }), 'text-slate-700')}>
                                        Log in
                                    </Link>
                                    <Link href={route('register')} className={buttonVariants({ variant: 'default', size: 'sm' })}>
                                        Get Started
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main className="flex-1">{children}</main>

                <footer className="mt-12 border-t border-slate-200/80 py-6 text-sm text-slate-500">
                    <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                        <p>BarcodeOS foundation keeps plans, barcode catalog and provider settings admin-manageable from day one.</p>
                        <div className="flex items-center gap-4">
                            <Link href={route('home')} className="transition hover:text-slate-900">
                                Home
                            </Link>
                            <Link href={route('pricing')} className="transition hover:text-slate-900">
                                Pricing
                            </Link>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    );
}
