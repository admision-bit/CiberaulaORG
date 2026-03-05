<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_classic
 * @copyright  2018 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_classic\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * Note: This class is required to avoid inheriting Boost's core_renderer,
 *       which removes the edit button required by Classic.
 *
 * @package    theme_classic
 * @copyright  2018 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \core_renderer {

    /**
     * CIBERAULA: título SEO personalizado para páginas de categoría.
     * Formato: "Nombre de la categoría - Ciberaula"
     * En el resto de páginas, comportamiento estándar de Moodle.
     *
     * @return string el título para el tag <title>
     */
    public function page_title(): string {
        // Solo intervenimos en páginas de categoría
        if ($this->page->pagetype === 'course-index-category') {
            $heading = $this->page->heading;
            if (!empty($heading)) {
                // Limpiamos emojis, HTML y espacios extra
                $clean = html_entity_decode(strip_tags($heading), ENT_QUOTES, 'UTF-8');
                $clean = preg_replace('/[\x{1F000}-\x{1FFFF}]|[\x{2600}-\x{27BF}]/u', '', $clean);
                $clean = trim($clean);
                if (!empty($clean)) {
                    return $clean . ' - Ciberaula';
                }
            }
        }
        // Comportamiento estándar para el resto de páginas
        return parent::page_title();
    }

}
