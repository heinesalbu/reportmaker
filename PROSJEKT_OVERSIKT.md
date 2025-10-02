# Report Maker - Prosjektoversikt

## 📋 Generell informasjon
- **Prosjekttype**: Laravel 12 applikasjon for rapportgenerering
- **Formål**: System for å lage og administrere sikkerhetsrapporter basert på maler og blokker
- **Domene**: https://reportmaker.magitek.no
- **Database**: MySQL (prod) / SQLite (lokal)
- **PHP versjon**: 8.2+

## 🏗️ Arkitektur og kjernekonsepter

### Hovedentiteter
1. **Customer** - Kunder som rapporter lages for
2. **Project** - Individuelle prosjekter/oppdrag
3. **Template** - Maler som definerer rapportstruktur  
4. **Section** - Seksjoner i rapporten (f.eks "Sårbarheter", "Anbefalinger")
5. **Block** - Individuelle blokker med innhold (f.eks spesifikke sårbarheter)
6. **ProjectBlock** - Kobling mellom prosjekt og blokker (pivot med override-tekst)

### Dataflyt
```
Template → definerer → Sections → inneholder → Blocks
                                      ↓
Project → aktiverer Template → velger Blocks → genererer Rapport (HTML/PDF)
```

## 📁 Katalogstruktur

### Controllers (`app/Http/Controllers/`)
- `ProjectController.php` - Hovedcontroller for prosjekter og rapportgenerering
- `TemplateController.php` - Administrering av maler  
- `CustomerController.php` - Kundeadministrering
- `BlockController.php` - Blokk-administrering
- `SectionController.php` - Seksjonsadministrering
- `SettingsController.php` - Systeminnstillinger (firma, logo)

### Models (`app/Models/`)
- `Project.php` - Prosjekt med tilhørende kunde og blokker
- `Template.php` - Maldefinisjoner
- `Customer.php` - Kundeinformasjon
- `Block.php` - Innholdsblokker
- `Section.php` - Seksjonsdefinisjoner
- `ProjectBlock.php` - Pivot-modell (many-to-many mellom Project og Block)
- `TemplateBlock.php` - Pivot for Template-Block forhold
- `TemplateSection.php` - Pivot for Template-Section forhold

### Services (`app/Services/`)
- `PdfRenderer.php` - PDF-generering via ekstern WeasyPrint service

## 🔑 Viktigste metoder og funksjonalitet

### ProjectController
```php
// Hovedmetoder
index()              // Liste alle prosjekter
create/store()       // Opprett nytt prosjekt  
edit/update()        // Rediger prosjekt
findings()           // Velg blokker for rapport
saveFindings()       // Lagre valgte blokker
reportPreview()      // Forhåndsvis rapport (HTML)
reportPdf()          // Generer PDF-rapport
applyTemplate()      // Aktiver mal på prosjekt
```

### TemplateController  
```php
// Hovedmetoder
index()              // Liste maler
create/store()       // Opprett mal
edit()               // Rediger mal (grunninfo + struktur)
sync()               // Lagre seksjoner/blokker i mal
saveStructure()      // Lagre malens struktur og overrides
```

### Rapportgenerering
1. **Velg blokker** (`findings()`) - Bruker velger hvilke blokker som skal inkluderes
2. **Forhåndsvisning** (`reportPreview()`) - Viser rapport i HTML
3. **PDF-generering** (`reportPdf()`) - Sender HTML til WeasyPrint service for PDF

## 🗄️ Database-struktur

### Sentrale tabeller
```sql
-- Kunder
customers: id, name, org_no, domains[], contact_name, contact_email, notes

-- Prosjekter  
projects: id, customer_id, title, template_id, status, tags[], description

-- Maler
templates: id, key, name, description

-- Seksjoner (f.eks "Sårbarheter")
sections: id, key, title, description, order

-- Blokker (f.eks "SQL Injection")
blocks: id, section_id, key, label, icon, severity, default_text, tips[], tags[]

-- Prosjekt ↔ Blokk (many-to-many)
project_blocks: project_id, block_id, selected, override_text

-- Template ↔ Section/Block
template_sections: template_id, section_id, include, order_override, title_override
template_blocks: template_id, block_id, include, text_override
```

### Relasjoner
- Project belongsTo Customer  
- Project belongsTo Template (via key)
- Project belongsToMany Block (via ProjectBlock)
- Section hasMany Block
- Template belongsToMany Section/Block (via pivot tables)

## 🔧 Tekniske detaljer

### Environment
```bash
# Produksjon (.env)
APP_URL=https://reportmaker.magitek.no
DB_CONNECTION=mysql
WEASY_URL=http://127.0.0.1:8000

# Lokal (.env.local)  
APP_URL=http://localhost
DB_CONNECTION=sqlite
```

### Eksterne avhengigheter
- **WeasyPrint service** (port 8000) - PDF-generering
- **Apache/PHP-FPM** - Webserver  
- **MySQL** - Database (prod)

### PDF-generering
```php
// Via PdfRenderer service
$pdf = app(PdfRenderer::class);
$bytes = $pdf->renderBytes($html);
// Returnerer PDF som download
```

## 🎨 Frontend/Views

### Hovedsider
- `/resources/views/projects/` - Prosjektadministrering
  - `index.blade.php` - Prosjektliste
  - `form.blade.php` - Opprett/rediger prosjekt  
  - `findings.blade.php` - Velg blokker for rapport
- `/resources/views/templates/` - Maladministrering
  - `index.blade.php` - Malliste
  - `form.blade.php` - Opprett/rediger mal
- `/resources/views/reports/` - Rapporter
  - `preview.blade.php` - HTML-forhåndsvisning
  - `pdf.blade.php` - PDF-template

### Styling
- Enkel CSS (ingen framework)
- Print-optimalisert for PDF-generering
- Responsiv layout

## 🚀 Routing (`routes/web.php`)

### Hovedruter
```php
// Prosjekter
Route::resource('projects', ProjectController::class);
Route::get('/projects/{project}/findings', 'ProjectController@findings');
Route::post('/projects/{project}/findings', 'ProjectController@saveFindings');
Route::get('/projects/{project}/report/preview', 'ProjectController@reportPreview');

// Maler  
Route::resource('templates', TemplateController::class);
Route::post('/templates/{template}/sync', 'TemplateController@sync');

// Kunder
Route::resource('customers', CustomerController::class);
```

## 🔐 Autentisering
- Laravel Breeze (enkel auth)
- Session-basert autentisering
- Middleware: `auth` for beskyttede sider

## 📊 Arbeidsflyt

### Typisk brukerreise
1. **Opprett kunde** - Registrer ny kunde i systemet
2. **Opprett prosjekt** - Knytt prosjekt til kunde
3. **Aktiver mal** - Velg og aktiver en mal på prosjektet  
4. **Velg blokker** - Gå gjennom tilgjengelige blokker og velg relevante
5. **Tilpass tekst** - Override standardtekst om nødvendig
6. **Generer rapport** - Forhåndsvis og generer PDF

### Mal-administrering
1. **Opprett mal** - Definer navn og nøkkel
2. **Velg seksjoner** - Bestem hvilke seksjoner som skal inkluderes
3. **Velg blokker** - Bestem standardblokker og eventuelle overrides
4. **Aktiver på prosjekter** - Bruk malen som utgangspunkt

## 🛠️ Utvikling og deployment

### Lokal utvikling
```bash
composer install
cp .env.local .env
php artisan migrate
php artisan serve
```

### Produksjon
- Apache VirtualHost på port 80
- PHP-FPM 8.3  
- MySQL database
- WeasyPrint service for PDF

### Viktige kommandoer
```bash
php artisan migrate        # Kjør migrasjoner
php artisan tinker         # Database-konsoll
php artisan route:list     # Vis alle ruter
```

## 📝 Notater for videre utvikling

### Potensielle forbedringer
- **Versjonering av maler** - Spor endringer i maler over tid
- **Samarbeidsfunksjoner** - Flere brukere på samme prosjekt  
- **Batch-operasjoner** - Velg flere prosjekter samtidig
- **Export/Import** - Backup og restore av data
- **API** - REST API for integrering

### Kjente begrensninger
- Ingen versjonskontroll av rapporter
- Begrenset multi-user support
- PDF-generering avhengig av ekstern service

## 🔍 Debugging og feilsøking

### Viktige loggfiler
- `storage/logs/laravel.log` - Applikasjonslogger
- Apache access/error logs
- WeasyPrint service logs

### Vanlige problemer
- **PDF ikke generert** - Sjekk WEASY_URL i .env
- **Bilder mangler i PDF** - Sjekk storage-permissions
- **Database-feil** - Sjekk connection i .env

## 📧 Kontakt og support
- **Utvikler**: [Ikke spesifisert]
- **Domene**: reportmaker.magitek.no  
- **Server**: Apache + PHP-FPM + MySQL

---
*Dokumentasjon oppdatert: 2. oktober 2025*