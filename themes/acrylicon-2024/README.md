# Acrylicon WordPress Theme

WordPress tema med Tailwind CSS for Acrylicon.

## 🚀 Komme i gang

### Forutsetninger
- Node.js og npm installert
- MAMP eller lignende lokalt utviklingsmiljø for WordPress

### Installasjon

1. Naviger til tema-mappen:
```bash
cd /Applications/MAMP/htdocs/acrylicon/wp-content/themes/acrylicon-2024
```

2. Installer npm-avhengigheter:
```bash
npm install
```

3. Bygg Tailwind CSS:
```bash
npm run build:css
```

## 📝 Tilgjengelige Scripts

- `npm run build:css` - Bygger og minifiserer Tailwind CSS for produksjon
- `npm run watch:css` - Overvåker endringer og kompilerer automatisk
- `npm run dev` - Alias for watch:css

## 🎨 Tailwind Konfigurasjon

### Custom Farger
Temaet bruker egendefinerte farger i `tailwind.config.js`:
- `red` - #E2241C
- `dark-blue` - #253761
- `light-blue` - #D5EDF7
- `neutral-1/2/3` - Ulike nøytrale toner
- `gray-1/2/3` - Grå nyanser

### Breakpoints
- `md` - 640px
- `lg` - 960px

### Eksempel på bruk:
```html
<!-- Responsive klasser -->
<div class="text-2xl md:text-3xl lg:text-5xl">Overskrift</div>

<!-- Custom farger -->
<div class="bg-red text-white">Rød bakgrunn</div>

<!-- Spacing -->
<div class="px-4 py-8 md:px-6 lg:px-12">Innhold</div>
```

## 📁 Filstruktur

```
├── src/
│   └── tailwind.css          # Tailwind source fil med @tailwind direktiver
├── assets/
│   └── css/
│       └── tailwind.css      # Kompilert CSS (genereres automatisk)
├── tailwind.config.js        # Tailwind konfigurasjon
├── package.json              # npm dependencies og scripts
└── functions.php             # WordPress tema setup
```

## 🔧 Utvikling

### Watch Mode
For aktiv utvikling, kjør watch mode så Tailwind automatisk kompilerer ved endringer:
```bash
npm run dev
```

### Produksjon Build
Før deploy til produksjon, bygg optimalisert CSS:
```bash
npm run build:css
```

## 🎯 WordPress Spesifikke Stiler

Temaet bevarer WordPress-spesifikke stiler som:
- `.alignfull` og `.alignwide` for Gutenberg
- `.is-style-*` for block styles
- WordPress admin bar justeringer

Disse er definert i `src/tailwind.css` under `@layer components`.

## 📚 Dokumentasjon

- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [WordPress Theme Development](https://developer.wordpress.org/themes/)

## 👤 Forfatter

Andreas Harstad - Konsulent Harstad
