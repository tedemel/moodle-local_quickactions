<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Deutsche Sprachstrings für local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quick Actions';

$string['setting_enabled'] = 'Quick Actions aktivieren';
$string['setting_enabled_desc'] = 'Zeigt den schwebenden Quick-Actions-Button im Kurs-Bearbeitungsmodus.';
$string['setting_defaultselection'] = 'Standard-Auswahlmodus';
$string['setting_defaultselection_desc'] = 'Wie sollen Aktivitäten für Bulk-Aktionen ausgewählt werden? "Checkboxen" fügt neben jeder Aktivität eine Checkbox hinzu. "Lasso" erlaubt das Aufziehen eines Auswahlrechtecks. "Beides" zeigt Checkboxen und ermöglicht Lasso parallel.';
$string['selection_checkboxes'] = 'Nur Checkboxen';
$string['selection_lasso'] = 'Nur Lasso (Rechteck ziehen)';
$string['selection_both'] = 'Checkboxen und Lasso';
$string['setting_fabposition'] = 'Position des schwebenden Buttons';
$string['setting_fabposition_desc'] = 'Wo der schwebende Quick-Actions-Button erscheint.';
$string['fab_bottomright'] = 'Unten rechts';
$string['fab_bottomleft'] = 'Unten links';
$string['fab_bottomcenter'] = 'Unten Mitte';
$string['setting_confirmthreshold'] = 'Bestätigungs-Schwelle';
$string['setting_confirmthreshold_desc'] = 'Wenn eine Aktion mehr als so viele Aktivitäten betrifft, wird eine zusätzliche Bestätigung verlangt.';

$string['fab_label'] = 'Quick Actions';
$string['fab_aria'] = 'Quick-Actions-Panel öffnen';
$string['panel_title'] = 'Quick Actions';
$string['panel_subtitle_none'] = 'Aktivitäten auswählen, auf die die Aktion angewendet werden soll';
$string['panel_subtitle_count'] = 'Ausgewählt: {$a}';
$string['panel_close'] = 'Schließen';
$string['panel_clear'] = 'Auswahl aufheben';
$string['panel_selectall'] = 'Alle auswählen';
$string['panel_lasso_hint'] = 'Tipp: Shift gedrückt halten und ziehen für Lasso-Auswahl';

$string['action_visibility'] = 'Sichtbarkeit umschalten';
$string['action_visibility_desc'] = 'Ausgewählte Aktivitäten anzeigen oder verstecken.';
$string['action_visibility_show'] = 'Anzeigen';
$string['action_visibility_hide'] = 'Verstecken';
$string['action_visibility_stealth'] = 'Stealth (verfügbar, aber im Kurs ausgeblendet)';

$string['action_dateshift'] = 'Termine verschieben';
$string['action_dateshift_desc'] = 'Verschiebt alle relevanten Termine ausgewählter Aktivitäten um einen bestimmten Zeitraum.';
$string['action_dateshift_amount'] = 'Verschieben um';
$string['action_dateshift_unit'] = 'Einheit';
$string['action_dateshift_unit_days'] = 'Tage';
$string['action_dateshift_unit_weeks'] = 'Wochen';
$string['action_dateshift_unit_months'] = 'Monate';
$string['action_dateshift_direction'] = 'Richtung';
$string['action_dateshift_forward'] = 'Vorwärts (später)';
$string['action_dateshift_backward'] = 'Rückwärts (früher)';

$string['action_section_duplicate'] = 'Sektion duplizieren mit Inhalten';
$string['action_section_duplicate_desc'] = 'Erstellt eine Kopie der gewählten Sektion inkl. aller Aktivitäten.';
$string['action_section_duplicate_position'] = 'Neue Sektion einfügen';
$string['action_section_duplicate_after'] = 'Direkt nach Original';
$string['action_section_duplicate_end'] = 'Am Ende des Kurses';

$string['action_move'] = 'In Sektion verschieben';
$string['action_move_desc'] = 'Verschiebt ausgewählte Aktivitäten in eine andere Sektion dieses Kurses.';
$string['action_move_target'] = 'Ziel-Sektion';

$string['action_completion_template'] = 'Abschluss aus Vorlage übertragen';
$string['action_completion_template_desc'] = 'Eine markierte Aktivität als Vorlage wählen — ihre Abschluss-Bedingungen werden auf alle anderen markierten übertragen.';
$string['action_completion_template_pick'] = 'Vorlage wählen';
$string['error_completion_needs_two'] = 'Mindestens zwei Aktivitäten markieren (eine Vorlage + Ziele).';

$string['dialog_preview'] = 'Vorschau';
$string['dialog_preview_intro'] = 'Folgende Änderungen werden angewendet:';
$string['dialog_apply'] = 'Anwenden';
$string['dialog_cancel'] = 'Abbrechen';
$string['dialog_confirm_title'] = 'Aktion bestätigen';
$string['dialog_confirm_text'] = 'Dies wirkt sich auf {$a} Elemente aus. Sind Sie sicher?';
$string['dialog_done'] = 'Fertig';
$string['dialog_running'] = 'Wird angewendet...';

$string['result_success'] = 'Aktion erfolgreich auf {$a} Elementen ausgeführt.';
$string['result_partial'] = 'Aktion teilweise erfolgreich: {$a->success} von {$a->total} Elementen.';
$string['result_failure'] = 'Aktion fehlgeschlagen.';

$string['error_noselection'] = 'Keine Aktivitäten ausgewählt.';
$string['error_invalidcourse'] = 'Ungültiger Kurskontext.';
$string['error_permissiondenied'] = 'Sie haben keine Berechtigung für diese Aktion.';
$string['error_invalidcm'] = 'Ein oder mehrere ausgewählte Elemente sind ungültig.';
$string['error_invalidsection'] = 'Ungültige Sektion.';
$string['error_dateshift_zero'] = 'Verschiebungsbetrag muss mindestens 1 sein.';
$string['error_actionnotfound'] = 'Unbekannte Aktion.';

$string['quickactions:use'] = 'Quick-Actions-Panel verwenden';
$string['quickactions:bulkupdate'] = 'Bulk-Aktionen auf Kurs-Module anwenden';
$string['quickactions:duplicatesection'] = 'Kurs-Sektionen via Quick Actions duplizieren';

$string['privacy:metadata'] = 'Das Plugin Quick Actions speichert keine personenbezogenen Daten. Alle Operationen werden in Echtzeit auf bestehenden Moodle-Daten ausgeführt, über bestehende Moodle-APIs.';

$string['event_actionexecuted'] = 'Quick Action ausgeführt';

$string['task_cleanup_undo'] = 'Abgelaufene Undo-Snapshots löschen';
$string['undo_button'] = 'Rückgängig';
$string['undo_success'] = 'Aktion zurückgesetzt: {$a} Elemente wiederhergestellt.';
$string['undo_failed'] = 'Rückgängig machen fehlgeschlagen.';
$string['undo_expired'] = 'Dieser Undo-Eintrag ist abgelaufen.';

// User tour.
$string['tour_name'] = 'Quick Actions Einführung';
$string['tour_desc'] = 'Lernt in wenigen Schritten, wie das Quick-Actions-Panel funktioniert.';
$string['tour_step1_title'] = 'Quick Actions öffnen';
$string['tour_step1_content'] = 'Mit diesem schwebenden Button öffnest du das Quick-Actions-Panel. Es bündelt häufige Bulk-Aktionen für deinen Kurs.';
$string['tour_step2_title'] = 'Auswahl & Aktion';
$string['tour_step2_content'] = 'Wenn das Panel offen ist, erscheinen Checkboxen vor jeder Aktivität und jedem Abschnitt. Markiere die Elemente, auf die die Aktion wirken soll, dann wähle eine der vier Aktionen.';
$string['tour_step3_title'] = 'Aktivitäten markieren';
$string['tour_step3_content'] = 'Setze ein Häkchen vor einer Aktivität, um sie auszuwählen. Mehrere können gleichzeitig markiert werden.';
$string['tour_step4_title'] = 'Abschnitte markieren';
$string['tour_step4_content'] = 'Auch ganze Abschnitte können markiert werden — Sichtbarkeit und Termine wirken dann auf alle enthaltenen Aktivitäten.';
$string['tour_step5_title'] = 'Fünf Aktionen';
$string['tour_step5_content'] = '<ul><li><b>Sichtbarkeit:</b> Anzeigen, Verstecken oder Stealth</li><li><b>Termine verschieben:</b> Zieldatum + Uhrzeit wählen</li><li><b>Sektion duplizieren:</b> mit allen Inhalten</li><li><b>In Sektion verschieben:</b> Aktivitäten zwischen Abschnitten umziehen</li><li><b>Abschluss aus Vorlage:</b> Abschluss-Bedingungen einer markierten Aktivität auf alle anderen übertragen</li></ul>Vor dem Anwenden bekommst du immer eine Vorschau.';
