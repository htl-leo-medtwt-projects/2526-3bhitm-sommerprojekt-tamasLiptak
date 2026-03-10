# Projektantrag: Athena-Log

## 1. Basis-Informationen

-   **Projektname:** Athena-Log
-   **Lead Developer:** Tamás Lipták
-   **Technologies:** HTML, CSS, JavaScript, PHP,
    MySQL, Docker

------------------------------------------------------------------------

## 2. USP -- Alleinstellungsmerkmal

Athena-Log ist keine rein statische Informationsseite. Es ist ein
**interaktives Strategie-Tool**, das exklusive Skin-Sammlungen mit einer
**dynamischen Counter-Matrix** verbindet.

Der Fokus liegt auf der **Personalisierung**: Nutzer verwalten ihre
eigenen "Mains" und erhalten **maßgeschneiderte taktische Tipps** für
aktuelle Map-Pools.

------------------------------------------------------------------------

## 3. UI & UX -- Projekt aus Sicht des Users

### Aufbau & Oberfläche

-   **Konsistentes Design-System:** Verwendung der Overwatch-Farbpalette
    und Fonts für hohen Wiedererkennungswert.
-   **Responsive Design:** Optimiert für Desktop und Mobile
    (Barrierefreiheit), um es während des Spielens zu nutzen.
-   **Landing Page:** Fokus auf Branding und schnellen Einstieg in die
    Hero-Suche.

### Funktionen & Interaktion

-   **Personalisiertes Dashboard:** Nach dem Login sieht der User
    Fortschritte seiner "Mains" und favorisierte Maps.
-   **Skin-Showcase:** Filterbare Galerie (Besitzstatus, Seltenheit,
    Event) mit Microinteractions beim Hovern.

### The Tactical Lab (Eigene Abteilung)

-   Interaktive **Counter-Matrix** zur Team-Analyse.
-   **Map-Pool-Explorer** mit klassenspezifischen Hero-Rankings pro Map.
-   **Eigene Strategie-Notizen**, die dauerhaft im User-Profil
    gespeichert werden.

------------------------------------------------------------------------

## 4. Coder Plan -- Projekt aus Sicht des Entwicklers

### Technische Umsetzung

-   **PHP Server:** Dynamic Content Generation mit einer
    sauberen, logischen Filestruktur und Includes.
-   **Datenmanagement:** Nutzung einer MySQL-Datenbank.
-   **Features:** Integration eines **User-Systems (Login/Session)** und
    spezieller Funktionen wie **Datei-Uploads für Profilbilder**.

### Datenbankstruktur (3NF)


| Tabelle          | Beschreibung                                                     |
|------------------|------------------------------------------------------------------|
| users            | User-ID, Credentials, Level, Main-Hero-ID                       |
| heroes           | Basisdaten, Klasse, Bio-Links                                   |
| skins            | Skin-Details, Hero-ID, Rarity, Pick-Count (Community-Stats)     |
| user_inventory   | Verknüpfung User ↔ Skins (Besitzstatus)                          |
| map_meta         | Map-ID, Hero-ID, Tier-Rating (Strategie-Daten)                   |
| user_notes       | Individuelle Taktik-Notizen pro User und Hero                    |

## 5. Roadmap & Sprints

### Sprint 1

-   Setup der Datenbankstruktur (3NF)
-   Erstellung des statischen Content-Grundgerüsts

### Sprint 2

-   Implementierung des dynamischen Contents (Skins, Helden, Maps)\
-   PHP-Datenbankzugriff
-   Erste Such- und Filterlogik

### Sprint 3

-   Entwicklung des **User-Systems (Registrierung, Login)**\
-   Persönliche **Inventar-Verwaltung**

### Sprint 4

-   Finalisierung des **Tactical Lab**
-   UI/UX Feinschliff (Microinteractions)
