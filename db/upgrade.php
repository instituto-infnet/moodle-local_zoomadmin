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
 * Script de atualização do banco de dados quando o plugin for atualizado.
 *
 * Ao atualizar o plugin, realiza as alterações necessárias na tabela do plugin.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/**
 * Atualiza a tabela local_zoomadmin_recordpages de acordo com a versão mais
 * atual do plugin.
 *
 * @param string $oldversion Versão do plugin antes de ser atualizado.
 * @return bool Verdadeiro quando a atualização for realizada sem erros.
 */
function xmldb_local_zoomadmin_upgrade($oldversion) {
	global $DB;

	$dbman = $DB->get_manager();

	if ($oldversion < 2018030600) {

		// Define field lastaddedtimestamp to be added to local_zoomadmin_recordpages.
		$table = new xmldb_table('local_zoomadmin_recordpages');
		$field = new xmldb_field('lastaddedtimestamp', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'zoommeetingnumber');

		// Conditionally launch add field lastaddedtimestamp.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// zoomadmin savepoint reached.
		upgrade_plugin_savepoint(true, 2018030600, 'local', 'zoomadmin');
	}

	return true;
}