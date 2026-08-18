# Healthcare Master Import — analiza i mapping

**Datum:** 2026-08-18  
**Workbook:** `docs/baza/WizMedik_HEALTHCARE_MASTER_BiH_2026-08-18_v8_KS_HNK_DEEP_ROSTERS.xlsx`  
**Opseg:** samo `klinike` + `doktori` (+ pivoti specijalnosti).  
**Ne dira se:** `banje`, `laboratorije`, `domovi_njega`, `apoteke_*`, `lijekovi`, `usluge`.

Importer **ne mijenja** kolone na `klinike` / `doktori`. Provenance i Excel ID-jevi idu u `import_entity_mappings` i audit tabele.

## Sigurnosna pravila

- Ne kreira User naloge i ne šalje emailove.
- Javni profil kao admin create: `aktivan=true`, `verifikovan=true`, `user_id=null`.
- `prihvata_online=false` (nema lažnog online naručivanja).
- Claim vlasnika = `user_id` popunjen. Takav zapis se ne pregazi.
- Dry-run ne piše **ništa** u bazu (ni audit tabele). Samo JSON/CSV report na disk.
- Produkcioni import se ne pokreće dok se eksplicitno ne odobri.
- Postojeći unclaimed profil se ne dira bez `--update-existing` (ni prazna polja, ni specijalnosti, ni radno vrijeme).
- Claimed profil (`user_id` popunjen) se ne pregazi; novi doktori se ne vežu na claimed kliniku.
- Doktor **ne mora** imati kliniku (`klinika_id` nullable). Samostalni doktor je validan javni profil.
- Pravi import ide u **jednoj** DB transakciji: ili sve klinike/doktori iz tog run-a, ili rollback.

## Excel Tip → akcija

| Excel `Tip` | Akcija | Target |
|-------------|--------|--------|
| `dental_practice`, `dental_clinic`, `specialist_practice`, `polyclinic`, `specialist_clinic`, `medical_center`, `private_hospital`, `hospital`, `general_practice`, `health_center`, `specialist_hospital`, `clinical_center`, `fertility_clinic`, `ophthalmology_clinic` | CREATE/UPDATE/SKIP/REVIEW | `klinike` |
| `laboratory`, `diagnostic_center`, `rehabilitation_center` | SKIP | kasniji poseban import |
| ostalo | REVIEW | — |

`health_center` (dom zdravlja) ide u `klinike`, ne u `domovi_njega`.

## Mapping tabela

| Excel sheet / kolona | Laravel model | DB tabela | DB kolona | Transformacija | Obavezno | Fallback | Pravilo |
|----------------------|---------------|-----------|-----------|----------------|----------|----------|---------|
| `01_INSTITUTIONS.Institution ID` | ImportEntityMapping | `import_entity_mappings` | `external_id` | source + entity_type=institution | da | — | create mapping |
| `01_INSTITUTIONS.Naziv` | Klinika | `klinike` | `naziv` | trim | da | — | create; skip ako postoji match bez `--update-existing` |
| `01_INSTITUTIONS.Tip` | — | audit JSON | — | čuva se u payloadu | ne | — | unsupported na `klinike` |
| `01_INSTITUTIONS.Vlasništvo` | — | audit JSON | — | čuva se | ne | — | unsupported |
| `01_INSTITUTIONS.Grad` | Klinika | `klinike` | `grad` | CityMatcher alias | da | raw string + REVIEW | ne kreira `gradovi` |
| `01_INSTITUTIONS.Adresa` | Klinika | `klinike` | `adresa` | trim | da | `02_LOCATIONS` primary | failed ako prazno |
| `01_INSTITUTIONS.Telefon` / `Telefon normalizovan` | Klinika | `klinike` | `telefon` | PhoneNormalizer | da | `08_CONTACTS` primary phone | failed ako prazno |
| `01_INSTITUTIONS.Email` | Klinika | `klinike` | `email` | lowercase | ne | contacts | fill empty |
| `01_INSTITUTIONS.Official website` | Klinika | `klinike` | `website` | UrlNormalizer | ne | contacts | invalid URL → REVIEW, ne upis |
| `01_INSTITUTIONS.Google Place ID` | — | mapping payload | — | čuva se | ne | `google_maps_link` = place URL | unsupported kolona |
| `01_INSTITUTIONS.Confidence=low` | — | — | — | — | — | — | SKIP/REVIEW, bez inserta |
| `02_LOCATIONS.Adresa/Grad` (Primary=YES) | Klinika | `klinike` | `adresa`,`grad` | isto | ne | 01 | fill empty / update-existing |
| `02_LOCATIONS` druga lokacija | — | audit | — | — | — | — | REVIEW (nema multi-location) |
| `03_DOCTORS.Doctor ID` | ImportEntityMapping | mappings | `external_id` | entity_type=doctor | da | — | create mapping |
| `03_DOCTORS.Ime i prezime / javna titula` | Doktor | `doktori` | `ime`,`prezime` | DoctorNameNormalizer | da | — | failed ako se ne može splitati |
| `03_DOCTORS.Primarna specijalnost` | Doktor | `doktori` | `specijalnost` | trim | da | affiliation specialty | string uvijek; FK samo uz siguran slug |
| `05 Canonical candidate` | Specijalnost | `doktor_specijalnost` / `specijalnost_id` | — | SpecialtyMatcher | ne | alias mapa | exact/alias CREATE veze; candidate/fuzzy REVIEW; nikad novi slug |
| `04 Doctor ID + Institution ID` | Doktor | `doktori` | `klinika_id` | prva affiliation | ne | samostalni doktor | `klinika_id` je nullable; bez veze → samostalni profil ako ima grad/lokacija/telefon; 2+ klinike → REVIEW |
| doktor telefon/grad/lokacija | Doktor | `doktori` | `telefon`,`grad`,`lokacija` | s klinike ili sa 03 kolona / izvora | da | ZZO TK → Tuzla + `nije javno` | Excel 03 nema kontakt; admin i DB zahtijevaju ova polja |
| `doktori.mjesto/opstina` | Doktor | `doktori` | `mjesto`,`opstina` | — | ne | — | Excel nema → prazno |
| `09 Working hours raw` | Klinika | `klinike` | `radno_vrijeme` | WorkingHours parser | ne | — | parse OK → fill empty; inače REVIEW |
| doktor radno vrijeme | Doktor | `doktori` | `radno_vrijeme` | — | ne | — | Excel nema → prazno |
| `07_INST_SERVICES` | — | audit | — | — | — | — | unsupported (`usluge` su na doktoru) |
| `08_CONTACTS` entity=institution | Klinika | `telefon`/`email`/`website` | — | primary first | ne | — | extra phone unsupported |
| sheetovi 00, 10–20, 99 | — | — | — | — | — | — | ne u poslovne tabele |

## Deduplikacija

**Klinika:** mapping (`INST-*`) → Place ID u mapping payloadu → normalizovan naziv+grad → telefon+grad → website domen+grad. Više kandidata = REVIEW. Samo sličan naziv ≠ merge.

**Doktor:** mapping (`DOC-*`) → ime+klinika → ime+specijalnost+grad → ime+profile URL. Više kandidata = REVIEW. Samo ime ≠ merge.

## Javnost / SEO / claim

Admin create već postavlja `aktivan=true` + `verifikovan=true` bez `user_id`. Listing, `SeoController` i sitemap zahtijevaju oba flaga. Importer radi isto. Admin kasnije dodijeli vlasnika preko postojeće pozivnice.

`--update-existing` ažurira samo prazna polja dok je `user_id` null. Claimed profil = REVIEW.

## Dry-run komanda (nema upisa u bazu)

Dry-run samo čita `gradovi` / `specijalnosti` / postojeće `klinike`/`doktori` radi matchinga i piše JSON/CSV report na disk. Ne kreira audit redove.

```bash
cd backend
php artisan wizmedik:import-healthcare-master "../docs/baza/WizMedik_HEALTHCARE_MASTER_BiH_2026-08-18_v8_KS_HNK_DEEP_ROSTERS.xlsx" --dry-run --report=storage/app/import-reports
```

Pravi import zahtijeva `--force` (ili interaktivnu potvrdu) i prethodno pokretanje **samo** novih audit migracija:

```bash
php artisan migrate --path=database/migrations/2026_08_18_230000_create_healthcare_import_batches_table.php
php artisan migrate --path=database/migrations/2026_08_18_230001_create_healthcare_import_rows_table.php
php artisan migrate --path=database/migrations/2026_08_18_230002_create_import_entity_mappings_table.php
```

Te migracije **ne diraju** `klinike`, `doktori`, `apoteke_*` ni `lijekovi`.

Očekivano u dry-run: stotine REVIEW (candidate specijalnosti, samostalni ZZO doktori bez adrese, raw hours). Broj `klinike`/`doktori`/`apoteke_*` ostaje isti.

Lokalni dry-run 2026-08-19 nakon samostalnih doktora (baza `wizmedik` ostala nepromijenjena: 5 klinika, 11 doktora, 0 apoteka, 0 lijekova):

| Sheet | Rows | Create | Skip | Review | Failed |
|-------|------|--------|------|--------|--------|
| 01_INSTITUTIONS | 696 | 579 | 56 | 9 | 57 missing_required |
| 03_DOCTORS | 1372 | 469 | 0 | 870 | 33 name_unparsed |
| 05_DOCTOR_SPECIALITIES | 1384 | — | 181 | 504 specialty_unmapped | 0 |
| 07_INST_SERVICES | 71 | — | — | 71 unsupported | 0 |

Od 870 REVIEW na doktorima: 521 samostalni (`standalone_address_from_city`, `klinika_id` null), 183 ustanova nije uvezena, 166 nepoznata specijalnost. `missing_affiliation` više nije razlog za blokadu. Dry-run nije upisao ni audit tabele.

Produkcijski import (bez `--dry-run`) **ne pokretati** dok se ne odobri.
