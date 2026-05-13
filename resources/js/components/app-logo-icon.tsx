import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="8" width="4" height="32" rx="1.5" />
            <rect x="11" y="8" width="2" height="32" rx="1" />
            <rect x="16" y="8" width="5" height="32" rx="1.5" />
            <rect x="24" y="8" width="3" height="32" rx="1.5" />
            <rect x="30" y="8" width="6" height="32" rx="1.5" />
            <rect x="39" y="8" width="3" height="32" rx="1.5" />
            <rect x="6" y="35" width="36" height="3" rx="1.5" className="opacity-70" />
        </svg>
    );
}
