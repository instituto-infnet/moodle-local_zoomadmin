<?php
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Web services para gerenciamento do Zoom.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
	'local_zoomadmin_insert_recording_participant' => array(
		'classname' => 'local_zoomadmin_external',
		'methodname' => 'insert_recording_participant',
		'classpath' => 'local/zoomadmin/externallib.php',
		'description' => 'Insere um registro na tabela local_zoomadmin_participants, referente ao acesso à gravação de uma sessão do Zoom.',
		'type' => 'write',
		'ajax' => true
	)
);

$services = array(
	'Zoom Admin' => array(
		'functions' => array('local_zoomadmin_insert_recording_participant'),
		'restrictedusers' => 0,
		'enabled' => 1,
		'shortname' => 'zoomadmin'
	)
);
