<?php
/**
 * A scheduled task for auth_ldapup1 user sync.
 *
 * @package    auth_ldapup1
 * @copyright  2020 Silecs
 * derived from auth_ldap task by 2015 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_ldapup1\task;

class sync_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return "Synchronisation des utilisateurs LDAP (ldapup1)";
    }

    /**
     * Run users sync.
     */
    public function execute() {
        if (is_enabled_auth('ldapup1')) {
            $auth = get_auth_plugin('ldapup1');
            $auth->set_verbosity(1);
            $last = $auth->get_last_sync();
            $auth->sync_users(true, $last['begin'], 'stdout');
        }
    }

}
