<?php
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Tarefas agendadas para gerenciamento do Zoom.
 *
 * @package    local_zoomadmin
 * @copyright  2017 Instituto Infnet {@link http://infnet.edu.br}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
	array(
		'classname' => 'local_zoomadmin\task\add_all_recordings_to_page',
		'blocking' => 0,
		'minute' => '0',
		'hour' => '*',
		'day' => '*',
		'dayofweek' => '*',
		'month' => '*'
	)
);
