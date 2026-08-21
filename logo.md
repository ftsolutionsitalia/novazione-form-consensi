# Logo — Oro alla Tocca

## Concetto

Il marchio rappresenta i due elementi fisici della prova alla tocca:

1. **La pietra di paragone** (basanite nera) con lo **striscio dorato** lasciato dal metallo, leggermente ruotata per dare dinamismo e riferimento all'atto dello "strofinare".
2. **La goccia di reagente acido**, in oro chiaro, sospesa sopra la pietra — richiama il secondo passaggio della prova (la conferma con l'acido).

Il risultato è un'icona semplice, riconoscibile anche a dimensioni ridotte (favicon 16–32px), che comunica immediatamente "test/verifica dell'oro" senza ricorrere a cliché come lingotti o monete.

## Anteprima

Il file sorgente è in `app/src/assets/logo.svg` ed è usato sia come componente React (`app/src/components/Logo.tsx`) sia come favicon (`app/public/favicon.svg`).

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" role="img" aria-labelledby="oatLogoTitle">
  <title id="oatLogoTitle">Oro alla Tocca</title>
  <defs>
    <linearGradient id="goldStreak" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#e2a730" />
      <stop offset="50%" stop-color="#f5db8d" />
      <stop offset="100%" stop-color="#ab6f20" />
    </linearGradient>
    <linearGradient id="dropGradient" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#f5db8d" />
      <stop offset="100%" stop-color="#cf9027" />
    </linearGradient>
  </defs>

  <rect x="0" y="0" width="120" height="120" rx="20" fill="#100e0a" />

  <g transform="rotate(-8 60 66)">
    <rect x="14" y="52" width="92" height="28" rx="5" fill="#221d15" stroke="#3a3122" stroke-width="1.5" />
    <rect x="24" y="62" width="66" height="7" rx="3.5" fill="url(#goldStreak)" />
  </g>

  <path
    d="M85 24c0 8-9 13-9 22 0 5.5 4 9.5 9 9.5s9-4 9-9.5c0-9-9-14-9-22z"
    fill="url(#dropGradient)"
  />
  <circle cx="85" cy="46" r="9.5" fill="none" stroke="#100e0a" stroke-width="1" opacity="0.15" />
</svg>
```

## Palette

| Ruolo | Colore | Hex |
|---|---|---|
| Sfondo / pietra di paragone | Nero caldo | `#100e0a` / `#221d15` |
| Striscio dorato — chiaro | Oro chiaro | `#f5db8d` |
| Striscio dorato — medio | Oro | `#e2a730` |
| Striscio dorato — scuro | Oro scuro/bronzo | `#ab6f20` |
| Goccia reagente — accento | Oro cipria → oro ambrato | `#f5db8d` → `#cf9027` |
| Testo su sfondo scuro | Oro chiaro (wordmark) | `#f5db8d` (`text-gold-200` nell'app) |

La palette completa dei toni oro usata nell'interfaccia (`gold-50` → `gold-900`) è definita in `app/src/index.css`.

## Tipografia

- **Wordmark / titoli**: font serif (`Georgia`, `Iowan Old Style`, serif) — richiama la tradizione orafa e i documenti di stima.
- **Interfaccia / testo funzionale**: font di sistema sans-serif, per leggibilità su schermo in un contesto operativo (banco vendita).

## Varianti e utilizzo

- **Icona sola** (senza wordmark): usata come favicon e come avatar/badge nell'header dell'app, dimensione minima consigliata 24×24px.
- **Icona + wordmark**: usata nell'header dell'app (`Header.tsx`), affiancata al claim "Prova & valutazione dell'oro".
- Il logo è disegnato per **sfondo scuro** (il quadrato di fondo `#100e0a` fa parte del marchio); su sfondo chiaro va mantenuto il riquadro di fondo scuro anziché rimuoverlo, per non perdere il contrasto dello striscio dorato.

### Da evitare

- Non alterare la rotazione della pietra di paragone (l'inclinazione a -8° è parte del riconoscimento del segno).
- Non separare la goccia dal blocco pietra+striscio: sono un'unica composizione.
- Non sostituire i toni oro con oro "flat" a tinta unica: la sfumatura richiama la variazione di colore reale dello striscio metallico.
