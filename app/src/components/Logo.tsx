interface LogoProps {
  size?: number;
  className?: string;
}

export default function Logo({ size = 40, className = "" }: LogoProps) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 120 120"
      width={size}
      height={size}
      role="img"
      aria-labelledby="oatLogoTitle"
      className={className}
    >
      <title id="oatLogoTitle">Oro alla Tocca</title>
      <defs>
        <linearGradient id="goldStreak" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stopColor="#e2a730" />
          <stop offset="50%" stopColor="#f5db8d" />
          <stop offset="100%" stopColor="#ab6f20" />
        </linearGradient>
        <linearGradient id="dropGradient" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stopColor="#f5db8d" />
          <stop offset="100%" stopColor="#cf9027" />
        </linearGradient>
      </defs>

      <rect x="0" y="0" width="120" height="120" rx="20" fill="#100e0a" />

      <g transform="rotate(-8 60 66)">
        <rect x="14" y="52" width="92" height="28" rx="5" fill="#221d15" stroke="#3a3122" strokeWidth="1.5" />
        <rect x="24" y="62" width="66" height="7" rx="3.5" fill="url(#goldStreak)" />
      </g>

      <path
        d="M85 24c0 8-9 13-9 22 0 5.5 4 9.5 9 9.5s9-4 9-9.5c0-9-9-14-9-22z"
        fill="url(#dropGradient)"
      />
      <circle cx="85" cy="46" r="9.5" fill="none" stroke="#100e0a" strokeWidth="1" opacity="0.15" />
    </svg>
  );
}
