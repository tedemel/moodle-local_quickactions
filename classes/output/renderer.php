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
 * Renderer for local_quickactions.
 *
 * @package    local_quickactions
 * @copyright  2026 Tessa Demel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quickactions\output;

/**
 * Class renderer.
 */
class renderer extends \plugin_renderer_base {
    /**
     * render_fab_and_panel.
     */
    public function render_fab_and_panel(int $courseid): string {
        $context = \context_course::instance($courseid);
        $actions = \local_quickactions\action\registry::get_metadata_for_context($context);

        $data = [
            'courseid' => $courseid,
            'fabAria' => get_string('fab_aria', 'local_quickactions'),
            'fabLabel' => get_string('fab_label', 'local_quickactions'),
            'panelTitle' => get_string('panel_title', 'local_quickactions'),
            'subtitleNone' => get_string('panel_subtitle_none', 'local_quickactions'),
            'closeStr' => get_string('panel_close', 'local_quickactions'),
            'clearStr' => get_string('panel_clear', 'local_quickactions'),
            'selectAllStr' => get_string('panel_selectall', 'local_quickactions'),
            'lassoHint' => get_string('panel_lasso_hint', 'local_quickactions'),
            'actions' => $actions,
        ];

        return $this->render_from_template('local_quickactions/fab_panel', $data);
    }
}
