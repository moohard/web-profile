import type { SVGAttributes } from 'react';

// Monogram "P" Papenajam; warna mengikuti fill-current dari pemakaian
// (sidebar/auth/header) dan kontras huruf lewat fill-background.
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="9" />
            <text
                x="20"
                y="28"
                textAnchor="middle"
                fontFamily="inherit"
                fontSize="21"
                fontWeight="700"
                className="fill-background"
            >
                P
            </text>
        </svg>
    );
}
