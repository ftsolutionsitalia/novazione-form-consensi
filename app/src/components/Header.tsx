import Logo from "./Logo";

export type Tab = "dashboard" | "tocca" | "valutazione" | "registro" | "impostazioni";

const TABS: { id: Tab; label: string }[] = [
  { id: "dashboard", label: "Dashboard" },
  { id: "tocca", label: "Prova alla Tocca" },
  { id: "valutazione", label: "Valutazione" },
  { id: "registro", label: "Registro" },
  { id: "impostazioni", label: "Impostazioni" },
];

interface HeaderProps {
  active: Tab;
  onChange: (tab: Tab) => void;
}

export default function Header({ active, onChange }: HeaderProps) {
  return (
    <header className="border-b border-white/10 bg-stone-950/60 backdrop-blur sticky top-0 z-10">
      <div className="mx-auto max-w-5xl px-4 py-3 flex items-center gap-3">
        <Logo size={36} />
        <div>
          <h1 className="font-serif-display text-lg text-gold-200 leading-tight">Oro alla Tocca</h1>
          <p className="text-xs text-white/50 leading-tight">Prova &amp; valutazione dell'oro</p>
        </div>
      </div>
      <nav className="mx-auto max-w-5xl px-4 flex gap-1 overflow-x-auto pb-2">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            onClick={() => onChange(tab.id)}
            className={`whitespace-nowrap rounded-full px-3 py-1.5 text-sm transition-colors ${
              active === tab.id
                ? "bg-gold-400 text-stone-950 font-medium"
                : "text-white/60 hover:text-white hover:bg-white/5"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </nav>
    </header>
  );
}
