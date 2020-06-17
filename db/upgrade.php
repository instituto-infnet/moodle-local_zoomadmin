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
	} else if ($oldversion < 2018121200) {

		// Create table local_zoomadmin_log.
		$table = new xmldb_table('local_zoomadmin_log');

		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
		$table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
		$table->add_field('classfunction', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'timestamp');
		$table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'classfunction');

		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table, $continue=true, $feedback=true);
		}

		// zoomadmin savepoint reached.
		upgrade_plugin_savepoint(true, 2018121200, 'local', 'zoomadmin');
	} else if ($oldversion < 2019013100) {

		// Create table local_zoomadmin_participants.
		$table = new xmldb_table('local_zoomadmin_participants');

		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
		$table->add_field('meetingnumber', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'id');
		$table->add_field('meetinguuid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'meetingnumber');
		$table->add_field('useruuid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'meetinguuid');
		$table->add_field('userid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'useruuid');
		$table->add_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'useruuid');
		$table->add_field('useremail', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'username');
		$table->add_field('jointime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'useremail');
		$table->add_field('leavetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'jointime');
		$table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'leavetime');
		$table->add_field('attentiveness', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, null, 'duration');

		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table, $continue=true, $feedback=true);
		}

		// zoomadmin savepoint reached.
		upgrade_plugin_savepoint(true, 2019013100, 'local', 'zoomadmin');
	} else if ($oldversion < 2019030700) {

		// Define field recording to be added to local_zoomadmin_participants.
		$table = new xmldb_table('local_zoomadmin_participants');
		$field = new xmldb_field('recording', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'attentiveness');

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Changing nullability of field duration on table local_zoomadmin_participants to null.
		$field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'leavetime');
        // Launch change of nullability for field duration.
        $dbman->change_field_notnull($table, $field);

		// Changing nullability of field attentiveness on table local_zoomadmin_participants to null.
		$field = new xmldb_field('attentiveness', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'duration');
        // Launch change of nullability for field attentiveness.
        $dbman->change_field_notnull($table, $field);

		// zoomadmin savepoint reached.
		upgrade_plugin_savepoint(true, 2019030700, 'local', 'zoomadmin');
	} else if ($oldversion < 2019032800) {

        // Changing nullability of field userid on table local_zoomadmin_participants to null.
        $table = new xmldb_table('local_zoomadmin_participants');
        $field = new xmldb_field('userid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'useruuid');

        // Launch change of nullability for field userid.
        $dbman->change_field_notnull($table, $field);

		/*
		// Define index uq_meetinguuid_jointime_userid (unique) to be added to local_zoomadmin_participants.
		$index = new xmldb_index('uq_meetinguuid_jointime_userid', XMLDB_INDEX_UNIQUE, ['meetinguuid', 'jointime', 'userid']);

		// Conditionally launch add index uq_meetinguuid_jointime_userid.
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}
		//*/

        // Zoomadmin savepoint reached.
        upgrade_plugin_savepoint(true, 2019032800, 'local', 'zoomadmin');
    }





	return true;
}
