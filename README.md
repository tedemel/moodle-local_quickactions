# local_quickactions — Bulk-Aktionen für Moodle-Trainer

Schwebender Quick-Action-Button (FAB) im Kurs-Bearbeitungsmodus mit Multi-Select
und vier produktivitätssteigernden Aktionen.

## Features

- **Floating Action Button** unten rechts/links/Mitte (konfigurierbar)
- **Multi-Select** über Checkboxen (vor jeder Aktivität & Sektion) oder Lasso-Drag (Shift+Maus)
- **4 Aktionen** für ausgewählte Aktivitäten und Sektionen:
  - **Sichtbarkeit umschalten:** Anzeigen / Verstecken / Stealth
  - **Termine verschieben:** Zieldatum + Uhrzeit wählen, alle Termine werden relativ zum frühesten Datum aligned
  - **Sektion duplizieren:** mit allen Aktivitäten
  - **In Sektion verschieben:** Aktivitäten zwischen Sektionen umziehen
- **Vorschau** vor jeder Aktion (Vorher/Nachher-Tabelle)
- **Bestätigungsdialog** bei vielen betroffenen Elementen (default ab 10 Items, konfigurierbar)
- **Inkludierte User-Tour** (5 Schritte) — auto-startet beim ersten FAB-Klick pro Session, wiederholbar via ❓-Icon
- **Panel bleibt offen** nach Anwenden (Page-Reload + Auto-Reopen via sessionStorage)

## Anforderungen

- Moodle 5.0, 5.1 oder 5.2
- PHP 8.3 oder neuer (PHP 8.4 unterstützt)
- Boost- oder Boost-Union-Theme (FAB rendert via `before_standard_top_of_body_html_generation`)
- Kurs-Bearbeitungsmodus muss aktiv sein
- User braucht `local/quickactions:use` (default: editingteacher, manager)

## Installation

1. Plugin nach `<MOODLE_ROOT>/local/quickactions/` kopieren  
   (bei Moodle 5.2 mit `public/`: `<MOODLE_ROOT>/public/local/quickactions/`)
2. Site-Admin → Mitteilungen → Datenbank-Upgrade ausführen
3. Optional konfigurieren unter Site-Admin → Plugins → Lokale Plugins → Quick Actions:
   - Auswahl-Modus (Checkboxen / Lasso / Beides)
   - FAB-Position
   - Bestätigungs-Schwelle

## Capabilities

| Capability | Default-Roles | Bedeutung |
|---|---|---|
| `local/quickactions:use` | editingteacher, manager | Panel öffnen |
| `local/quickactions:bulkupdate` | editingteacher, manager | Sichtbarkeit, Termine, Verschieben |
| `local/quickactions:duplicatesection` | editingteacher, manager | Sektion duplizieren |

## Bedienung

1. Kurs öffnen, **Bearbeiten-Modus aktivieren**
2. Schwebenden „Quick Actions"-Button klicken → Panel öffnet, Tour startet (beim ersten Mal)
3. Aktivitäten und/oder Sektionen via **Checkbox** markieren — oder Shift+Drag für Lasso
4. **Aktion** wählen (Sichtbarkeit, Termine, Sektion duplizieren, In Sektion verschieben)
5. Parameter eingeben (z. B. Zieldatum, Ziel-Sektion)
6. **Vorschau** prüfen
7. **Anwenden** — Notification erscheint, Panel öffnet sich nach Reload erneut

## Datenschutz

Das Plugin speichert **keine eigenen Daten**. Alle Operationen wirken auf
bestehende Moodle-Daten (`course_modules`, `assign`, `quiz`, `course_sections`, …)
über offizielle Moodle-APIs. `null_provider` für Privacy.

## Bekannte Grenzen

- „Sektion duplizieren" arbeitet pro Sektion (nicht für mehrere gleichzeitig)
- Termin-Verschiebung wirkt nur auf Module mit bekannten Datumsfeldern (assign, quiz, forum,
  lesson, choice, workshop, feedback, data, scorm, chat). Andere Modul-Typen → No-Op
- Multi-Kurs-Termin-Operationen sind in das eigenständige Plugin **`tool_courseshift`** ausgelagert

## Technik

- Hook: `\core\hook\output\before_standard_top_of_body_html_generation`  
  (NICHT `before_standard_footer_html_generation` — der landet in Boost-Theme im versteckten Footer-Popover)
- AMD-Module: `main`, `fab`, `selection`, `actions` (Rollup-build via Moodle Grunt)
- Templates: Mustache (`fab_panel`, `preview_table`, vier `dialog_*`)
- WebServices: `local_quickactions_get_context`, `_preview`, `_execute`
- User-Tour via `tool_usertours` (programmatisch getriggert, nicht via Pathmatch)

## Lizenz

GPL v3 oder höher — wie Moodle.

## Repository

https://github.com/tedemel/moodle-local_quickactions

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md).
