<?php

/**
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// The Posix uid and gid of the 'nobody' account and 'nogroup' group.
if (!defined('AUTH_UID_NOBODY')) {
    define('AUTH_UID_NOBODY', -2);
}
if (!defined('AUTH_GID_NOGROUP')) {
    define('AUTH_GID_NOGROUP', -2);
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');
require_once('auth_trivial.php'); // all trivial methods
require_once($CFG->dirroot . '/local/cohortsyncup1/lib.php');

/**
 * LDAP authentication plugin.
 */
class auth_plugin_ldapup1 extends auth_plugin_trivial{

    protected $verbosity = 1;

    /**
     * Init plugin config from database settings depending on the plugin auth type.
     */
    function init_plugin($authtype) {
        $this->pluginconfig = 'auth/ldapup1';
        $this->config = get_config($this->pluginconfig);
        if (empty($this->config->ldapencoding)) {
            $this->config->ldapencoding = 'utf-8';
        }
        if (empty($this->config->user_type)) {
            $this->config->user_type = 'default';
        }

        $ldap_usertypes = ldap_supported_usertypes();
        $this->config->user_type_name = $ldap_usertypes[$this->config->user_type];
        unset($ldap_usertypes);

        $default = ldap_getdefaults();

        // Use defaults if values not given
        foreach ($default as $key => $value) {
            // watch out - 0, false are correct values too
            if (!isset($this->config->{$key}) or $this->config->{$key} == '') {
                $this->config->{$key} = $value[$this->config->user_type];
            }
        }

        // Hack prefix to objectclass
        $this->config->objectclass = ldap_normalise_objectclass($this->config->objectclass);
    }

    /**
     * Constructor with initialisation.
     */
    public function __construct() {
        $this->authtype = 'ldapup1';
        $this->roleauth = 'auth_ldapup1';
        $this->errorlogtag = '[AUTH LDAPUP1] ';
        $this->init_plugin($this->authtype);
        $this->config->{'field_map_emailstop'} = 'accountstatus';
    }

    /**
     * set verbosity
     * @param int $verbosity 0 to 3
     */
    public function set_verbosity($verbosity) {
        $this->verbosity = $verbosity;
    }

    /**
     * Reads user information from ldap and returns it in array()
     *
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honor syncronization flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();
        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extusername))) {
            return false;
        }

        $search_attribs = array();
        $attrmap = $this->ldap_attributes();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs)) {
            return false; // error!
        }

        $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
//echo "user_entry : \n"; var_dump($user_entry); die();

        if (empty($user_entry)) {
            return false; // entry not found
        }

        $result = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $ldapval = NULL;
            foreach ($values as $value) {
                $entry = array_change_key_case($user_entry[0], CASE_LOWER);
                if (($value == 'dn') || ($value == 'distinguishedname')) {
                    $result[$key] = $user_dn;
                    continue;
                }
                // mettre "emailstop" pour les personnes ayant accountStatus != active
                if ($key == 'emailstop' && $value == 'accountstatus') {
                    $result[$key] = @$entry[$value][0] != 'active';
                    continue;
                }
                if (!array_key_exists($value, $entry)) {
                    //echo "\n\nWRONG data mapping: $value $entry \n\n";
                    continue; // wrong data mapping!
                }
                if (is_array($entry[$value])) {
                    $newval = textlib::convert($entry[$value][0], $this->config->ldapencoding, 'utf-8');
                } else {
                    $newval = textlib::convert($entry[$value], $this->config->ldapencoding, 'utf-8');
                }
                if (!empty($newval)) { // favour ldap entries that are set
                    $ldapval = $newval;
                }
            }
            if (!is_null($ldapval)) {
                $result[$key] = $ldapval;
            }
        }

        $this->ldap_close();
        return $result;
    }

    /**
     * Reads user information from ldap and returns it in an object
     *
     * @param string $username username (with system magic quotes)
     * @return mixed object or false on error
     */
    function get_userinfo_asobj($username) {
        $user_array = $this->get_userinfo($username);
        if ($user_array == false) {
            return false; //error or not found
        }
        $user_array = truncate_userinfo($user_array);
        $user = new stdClass();
        foreach ($user_array as $key=>$value) {
            $user->{$key} = $value;
        }
        return $user;
    }

    /**
     * Returns all usernames from LDAP
     *
     * get_userlist returns all usernames from LDAP
     *
     * @return array
     */
    function get_userlist() {
        return $this->ldap_get_userlist("({$this->config->user_attribute}=*)");
    }

    /**
     * Checks if user exists on LDAP
     *
     * @param string $username
     */
    function user_exists($username) {
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        // Returns true if given username exists on ldap
        $users = $this->ldap_get_userlist('('.$this->config->user_attribute.'='.ldap_filter_addslashes($extusername).')');
        return count($users);
    }


    /**
     * Syncronizes user fron external LDAP server to moodle user table
     *
     * Sync is now using username attribute.
     *
     * Syncing users removes or suspends users that dont exists anymore in external LDAP.
     * Creates new users and updates coursecreator status of users.
     *
     * @param bool $do_updates will do pull in data updates from LDAP if relevant
     * @param (string|false) $since if set, only updates since this params (syntax LDAP ex. 20120731012345Z)
     * @param string $logoutput ('file' | 'stdout' | 'stderr')
     */
    function sync_users($do_updates=true, $since=false, $output='file') {
        global $CFG, $DB;

        print_string('connectingldap', 'auth_ldapup1');
        $ldaplogid = up1_cohortsync_addlog(null, 'ldap:sync', "since $since");
        $logmsg = '';
        $ldapconnection = $this->ldap_connect();

        $dbman = $DB->get_manager();

    /// Define table user to be created
        $table = new xmldb_table('tmp_extuser');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('accountstatus', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('username', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username'));

        print_string('creatingtemptable', 'auth_ldapup1', 'tmp_extuser');
        $dbman->create_temp_table($table);
        // var_dump($this->config);

        ////
        //// get user's list from ldap to sql in a scalable fashion
        ////
        // prepare some data we'll need
        // GA - $filterAff refers also to accountStatus due to bug M2156
        $filterAff = '(|(eduPersonAffiliation=teacher)(eduPersonAffiliation=student)(eduPersonAffiliation=staff)(accountStatus=disabled))';
        // $filterAff = '';
        // $filterAcc = '(accountStatus=active)';
        $filterAcc = '';
        $filterTime = '(modifyTimestamp>='. $since .')';

        if ( $since ) {
            $filter = '(&'. $filterAff . $filterAcc . $filterTime .')';
        } else {
            $filter = '(&'. $filterAff . $filterAcc .')';
        }

        $contexts = explode(';', $this->config->contexts);

        $fresult = array();
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }
            if ($this->config->search_sub) {
                //use ldap_search to find first user from subtree
                $ldap_result = ldap_search($ldapconnection, $context,
                                           $filter,
                                           array($this->config->user_attribute, 'accountStatus'));
            } else {
                //search only in this context
                $ldap_result = ldap_list($ldapconnection, $context,
                                         $filter,
                                         array($this->config->user_attribute, 'accountStatus'));
            }

            if(!$ldap_result) {
                continue;
            }

            if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                do {
                    $value = @ldap_get_values_len($ldapconnection, $entry, $this->config->user_attribute); // uid ou ...
                    $value = textlib::convert($value[0], $this->config->ldapencoding, 'utf-8');
                    $status = @ldap_get_values_len($ldapconnection, $entry, 'accountStatus'); // active ou disabled ou (non-défini)
                    $status = strtolower($status[0]);
                    $this->ldap_bulk_insert($value, $status);
                } while ($entry = ldap_next_entry($ldapconnection, $entry));
            }
            unset($ldap_result); // free mem
        }

        /// preserve our user database
        /// if the temp table is empty, it probably means that something went wrong, exit
        /// so as to avoid mass deletion of users; which is hard to undo
        $count = $DB->count_records_sql('SELECT COUNT(username) AS count, 1 FROM {tmp_extuser}');
        if ($count < 1) {
            //print_string('didntgetusersfromldap', 'auth_ldapup1');
       $dbman->drop_table($table);
            up1_cohortsync_addlog($ldaplogid, 'ldap:sync', 'temp table empty. Exit.');
            exit;
        } else {
            print_string('gotcountrecordsfromldap', 'auth_ldapup1', $count);
        }
        $countAct = $DB->count_records_sql("SELECT COUNT(username) AS count FROM {tmp_extuser} WHERE accountstatus='active'");
        $countDis = $DB->count_records_sql("SELECT COUNT(username) AS count FROM {tmp_extuser} WHERE accountstatus='disabled'");
        $countUnd = $DB->count_records_sql("SELECT COUNT(username) AS count FROM {tmp_extuser} WHERE accountstatus=''");
        echo "accountStatus : $countAct active.  $countDis disabled.  $countUnd undefined.\n";

/// User Updates - time-consuming (optional)
        if ($do_updates) {
            // Narrow down what fields we need to update
            $all_keys = array_keys(get_object_vars($this->config));
            $updatekeys = array();
            foreach ($all_keys as $key) {
                if (preg_match('/^field_map_(.+)$/', $key, $match)) {
                    // SILECS UP1 on force l'update sur tous les champs avec une correspondance LDAP (type onlogin, pas oncreate)
                    if ( ! empty($this->config->{'field_map_'.$match[1]}) ) {
                    array_push($updatekeys, $match[1]); // the actual key name
                    }
                }
            }
            unset($all_keys); unset($key);
        } else {
            $logmsg .= 'updates disabled.  ';
            print_string('noupdatestobedone', 'auth_ldapup1');
            echo "    (updates disabled)\n";
        }
        if ($do_updates and !empty($updatekeys)) { // run updates only if relevant
            $users = $DB->get_records_sql('SELECT u.username, u.id
                                             FROM {user} u
                                             JOIN {tmp_extuser} te ON (u.username = te.username AND u.mnethostid = te.mnethostid)
                                            WHERE u.deleted = 0 AND u.auth = ? ',
                                          array('shibboleth'));
            if (!empty($users)) {
                print_string('userentriestoupdate', 'auth_ldapup1', count($users));
                $logmsg .= count($users) . ' updated.  ';

                $transaction = $DB->start_delegated_transaction();
                $xcount = 0;
                $maxxcount = 100;

                foreach ($users as $user) {
                    $this->do_log($output, get_string('auth_dbupdatinguser', 'auth_db', array('name'=>$user->username, 'id' =>$user->id)) . "\n");
                    if ($this->update_user_record($user->username, $updatekeys)) {
                        //** @todo incorporer ceci à la table user
                        $usersync = $DB->get_record('user_sync', array('userid' => $user->id));
                        if ($usersync) {
                            $usersync->timemodified = time();
                            $DB->update_record('user_sync', $usersync);
                        } else {
                            // this is a catch-all fix because of users created by the shibboleth plugin - see M1945
                            $this->init_user_sync($user->id);
                        }
                    } else {
                        $this->do_log($output, '     - ' . get_string('skipped') . "\n");
                    }
                    if ($this->verbosity >= 1) echo '.';
                    $xcount++;
                }
                $transaction->allow_commit();
                unset($users); // free mem
            }
        } else { // end do updates
            $logmsg .= '0 updated.  ';
            print_string('noupdatestobedone', 'auth_ldapup1');
            echo "    (empty)\n";
        }
/// User Additions
        // Find users missing in DB that are in LDAP
        // and gives me a nifty object I don't want.
        // note: we do not care about deleted accounts anymore, this feature was replaced by suspending to nologin auth plugin
        $sql = 'SELECT e.id, e.username
                  FROM {tmp_extuser} e
                  LEFT JOIN {user} u ON (e.username = u.username AND u.mnethostid = e.mnethostid)
                 WHERE u.id IS NULL';
        $add_users = $DB->get_records_sql($sql);

        if (!empty($add_users)) {
            print_string('userentriestoadd', 'auth_ldapup1', count($add_users));
            $logmsg .= count($add_users) . ' added.  ';

            //** for up1 metadata; cf BEGIN UP1 SILECS
            $sql = "SELECT shortname, id FROM {custom_info_field} WHERE objectname='user' AND shortname like 'up1%'";
            $ciffieldid = $DB->get_records_sql_menu($sql);

            $transaction = $DB->start_delegated_transaction();
            foreach ($add_users as $user) {
                $user = $this->get_userinfo_asobj($user->username);

                // Prep a few params
                $user->timemodified   = time();
                $user->timecreated   = time();
                $user->confirmed  = 1;
                $user->auth       = 'shibboleth'; // up1 specific
                $user->mnethostid = $CFG->mnet_localhost_id;
                // get_userinfo_asobj() might have replaced $user->username with the value
                // from the LDAP server (which can be mixed-case). Make sure it's lowercase
                $user->username = trim(textlib::strtolower($user->username));
                if (empty($user->lang)) {
                    $user->lang = $CFG->lang;
                }

                $id = $DB->insert_record('user', $user);
                $this->do_log($output, get_string('auth_dbinsertuser', 'auth_db', array('name'=>$user->username, 'id'=>$id)) . "\n");
                $this->init_user_sync($id);

                // BEGIN UP1 SILECS custom user data from Ldap
                //** @todo faire une boucle sur toutes les propriétés qui commencent par up1 au lieu de ce code adhoc
                $userprops = get_object_vars($user);
                foreach ($userprops as $prop => $value) {
                    if ( preg_match('/^up1/', $prop) ) {
                        $cidrecord = new stdClass;
                        $cidrecord->fieldid = $ciffieldid[$prop];
                        $cidrecord->objectname = 'user';
                        $cidrecord->objectid = $id;
                        $cidrecord->data = $value;
                        $cidrecord->dataformat = 0;
                        if ($this->verbosity >=3) {echo "\n    " . $user->username ." -> ". $cidrecord->data;}
                        $DB->insert_record('custom_info_data', $cidrecord);
                    }
                }
                // END UP1 SILECS

            }
            $transaction->allow_commit();
            unset($add_users); // free mem
        } else {
            print_string('nouserstobeadded', 'auth_ldapup1');
            $logmsg .= '0 added.  ';
        }

/// User suspension (originally: user removal)
            $sql = "SELECT u.*
                      FROM {user} u
                      LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = ? AND u.deleted = 0 AND e.accountstatus = ?";
            $remove_users = $DB->get_records_sql($sql, array('shibboleth', 'disabled'));

            if (!empty($remove_users)) {
                print_string('userentriestoremove', 'auth_ldapup1', count($remove_users));
                $logmsg .= count($remove_users) . ' suspended/removed.  ';

                foreach ($remove_users as $user) {
                    // AUTH_REMOVEUSER_SUSPEND
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->auth = 'nologin';
                        $updateuser->suspended = 1;
                        $DB->update_record('user', $updateuser);
                        $this->do_log($output, get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)) . "\n");
                }
            } else {
                print_string('nouserentriestoremove', 'auth_ldapup1');
            }
            unset($remove_users); // free mem!

/// Revive suspended users
            $sql = "SELECT u.id, u.username
                      FROM {user} u
                      JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE (u.auth = 'nologin' OR u.suspended = 1) AND u.deleted = 0 AND e.accountstatus != 'disabled'";
            $revive_users = $DB->get_records_sql($sql);

            if (!empty($revive_users)) {
                print_string('userentriestorevive', 'auth_ldapup1', count($revive_users));
                $logmsg .= count($revive_users) . ' revived.  ';

                foreach ($revive_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->auth = 'shibboleth';
                    $updateuser->suspended = 0;
                    $DB->update_record('user', $updateuser);
                    $this->do_log($output, get_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)) . "\n");
                }
            } else {
                print_string('nouserentriestorevive', 'auth_ldapup1');
            }
            unset($revive_users);

        $dbman->drop_table($table);
        $this->ldap_close();
        up1_cohortsync_addlog($ldaplogid, 'ldap:sync', $logmsg);

        return true;
    }


    /**
     * Create a new record in the "user_sync table"
     * @param int $userid // same userid as user.id
     */
    function init_user_sync($userid) {
        global $DB;
        //** @todo incorporer ceci à la table user
        $usersync = new stdClass;
        $usersync->userid = $userid; 
        $usersync->ref_plugin = 'auth_ldapup1';
        $usersync->ref_param = '';
        $usersync->timemodified = time();
        $newid = $DB->insert_record('user_sync', $usersync);
        return $newid;
    }


    /**
     * Update a local user record from an external source.
     * This is a lighter version of the one in moodlelib -- won't do
     * expensive ops such as enrolment.
     *
     * @todo GA 20180910  rewrite this method using auth_plugin_base::update_user_record()
     *
     * @param string $username username
     * @param array $updatekeys fields to update, false updates all fields.
     * @param bool $triggerevent set false if user_updated event should not be triggered.
     *             This will not affect user_password_updated event triggering.
     * @param bool $suspenduser Should the user be suspended?
     * @return stdClass|bool updated user record or false if there is no new info to update.
     */
    protected function update_user_record($username, $updatekeys = false, $triggerevent = false, $suspenduser = false) {

        global $CFG, $DB;

        // Just in case check text case
        $username = trim(textlib::strtolower($username));

        // Get the current user record
        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id));
        if (empty($user)) { // trouble
            error_log($this->errorlogtag.get_string('auth_dbusernotexist', 'auth_db', '', $username));
            print_error('auth_dbusernotexist', 'auth_db', '', $username);
            die;
        }

        // Protect the userid from being overwritten
        $userid = $user->id;
        $updated = false;

        if ($newinfo = $this->get_userinfo($username)) {
            $newinfo = truncate_userinfo($newinfo);

            if (empty($updatekeys)) { // all keys? this does not support removing values
                $updatekeys = array_keys($newinfo);
            }

            foreach ($updatekeys as $key) {
                if (isset($newinfo[$key])) {
                    $value = $newinfo[$key];
                } else {
                    $value = '';
                }

                if (!empty($this->config->{'field_map_' . $key})) {
                    if ($user->{$key} != $value) { // only update if it's changed
                        $DB->set_field('user', $key, $value, array('id'=>$userid));
                        $updated = true;
                    }
                }
            }
            if ($updated) {
                $DB->set_field('user', 'timemodified', time(), array('id'=>$userid));
            }
            // UP1 SILECS : Add custom info from LDAP
            foreach ($newinfo as $attr => $value) {
                if ( substr($attr, 0, 3) == 'up1' ) {
                    $fieldid = $DB->get_field('custom_info_field', 'id', array('objectname'=>'user', 'shortname'=>$attr), MUST_EXIST);
                    $DB->set_field('custom_info_data', 'data', $value, array('objectid'=>$userid, 'fieldid'=>$fieldid) );
                    if ($this->verbosity >= 3) {
                        echo "    $username -> $value\n";
                    }
                }
            } // END UP1 SILECS

        } else {
            return false;
        }
        return $DB->get_record('user', array('id'=>$userid, 'deleted'=>0));
    }

    /**
     * Bulk insert in SQL's temp table
     */
    function ldap_bulk_insert($username, $status) {
        global $DB, $CFG;

        $username = textlib::strtolower($username); // usernames are __always__ lowercase.
        $DB->insert_record_raw('tmp_extuser', array('username' => $username,
                                                    'mnethostid' => $CFG->mnet_localhost_id,
                                                    'accountstatus' => $status), false, true);
        if ($this->verbosity >= 1) {
            echo '.';
        }
    }


    /**
     * Take expirationtime and return it as unix timestamp in seconds
     *
     * Takes expiration timestamp as read from LDAP and returns it as unix timestamp in seconds
     * Depends on $this->config->user_type variable
     *
     * @param mixed time   Time stamp read from LDAP as it is.
     * @param string $ldapconnection Only needed for Active Directory.
     * @param string $user_dn User distinguished name for the user we are checking password expiration (only needed for Active Directory).
     * @return timestamp
     */
    function ldap_expirationtime2unix ($time, $ldapconnection, $user_dn) {
        $result = false;
        switch ($this->config->user_type) {
            case 'edir':
                $yr=substr($time, 0, 4);
                $mo=substr($time, 4, 2);
                $dt=substr($time, 6, 2);
                $hr=substr($time, 8, 2);
                $min=substr($time, 10, 2);
                $sec=substr($time, 12, 2);
                $result = mktime($hr, $min, $sec, $mo, $dt, $yr);
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                $result = $time * DAYSECS; // The shadowExpire contains the number of DAYS between 01/01/1970 and the actual expiration date
                break;
            case 'ad':
                $result = $this->ldap_get_ad_pwdexpire($time, $ldapconnection, $user_dn);
                break;
            default:
                print_error('auth_ldapup1_usertypeundefined', 'auth_ldapup1');
        }
        return $result;
    }

    /**
     * Takes unix timestamp and returns it formated for storing in LDAP
     *
     * @param integer unix time stamp
     */
    function ldap_unix2expirationtime($time) {
        $result = false;
        switch ($this->config->user_type) {
            case 'edir':
                $result=date('YmdHis', $time).'Z';
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                $result = $time ; // Already in correct format
                break;
            default:
                print_error('auth_ldapup1_usertypeundefined2', 'auth_ldapup1');
        }
        return $result;

    }

    /**
     * Returns user attribute mappings between moodle and LDAP
     *
     * @return array
     */

    function ldap_attributes () {
        $moodleattributes = array();
        foreach ($this->userfields as $field) {
            if (!empty($this->config->{"field_map_$field"})) {
                $moodleattributes[$field] = textlib::strtolower(trim($this->config->{"field_map_$field"}));
                if (preg_match('/,/', $moodleattributes[$field])) {
                    $moodleattributes[$field] = explode(',', $moodleattributes[$field]); // split ?
                }
            }
        }
        $moodleattributes['username'] = textlib::strtolower(trim($this->config->user_attribute));

        // UP1 - SILECS addition
        $customattrs = array('eduPersonPrimaryAffiliation', 'supannEntiteAffectationPrincipale');
        //** @todo ? use custom user metadata description instead, where shortname begins with up1
        foreach ($customattrs as $attr) {
            $moodleattributes['up1' . strtolower($attr)] = strtolower($attr); // le second strtolower est bien nécessaire !
        }

        $moodleattributes['emailstop'] = 'accountstatus';
        // END addition

	$moodleattributes['emailstop'] = 'accountstatus';

        return $moodleattributes;
    }

    /**
     * Returns all usernames from LDAP
     *
     * @param $filter An LDAP search filter to select desired users
     * @return array of LDAP user names converted to UTF-8
     */
    function ldap_get_userlist($filter='*') {
        $fresult = array();

        $ldapconnection = $this->ldap_connect();

        if ($filter == '*') {
           $filter = '(&('.$this->config->user_attribute.'=*)'.$this->config->objectclass.')';
        }

        $contexts = explode(';', $this->config->contexts);

        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            if ($this->config->search_sub) {
                // Use ldap_search to find first user from subtree
                $ldap_result = ldap_search($ldapconnection, $context,
                                           $filter,
                                           array($this->config->user_attribute));
            } else {
                // Search only in this context
                $ldap_result = ldap_list($ldapconnection, $context,
                                         $filter,
                                         array($this->config->user_attribute));
            }

            if(!$ldap_result) {
                continue;
            }

            $users = ldap_get_entries_moodle($ldapconnection, $ldap_result);

            // Add found users to list
            for ($i = 0; $i < count($users); $i++) {
                $extuser = textlib::convert($users[$i][$this->config->user_attribute][0],
                                             $this->config->ldapencoding, 'utf-8');
                array_push($fresult, $extuser);
            }
        }

        $this->ldap_close();
        return $fresult;
    }


    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        global $CFG, $OUTPUT;

        if (!function_exists('ldap_connect')) { // Is php-ldap really there?
            echo $OUTPUT->notification(get_string('auth_ldapup1_noextension', 'auth_ldapup1'));
            return;
        }

        include($CFG->dirroot.'/auth/ldapup1/config.html'); //** @todo clean or not ?
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // Set to defaults if undefined
        if (!isset($config->host_url)) {
             $config->host_url = '';
        }
        if (empty($config->ldapencoding)) {
         $config->ldapencoding = 'utf-8';
        }
        if (!isset($config->contexts)) {
             $config->contexts = '';
        }
        if (!isset($config->user_type)) {
             $config->user_type = 'default';
        }
        if (!isset($config->user_attribute)) {
             $config->user_attribute = '';
        }
        if (!isset($config->search_sub)) {
             $config->search_sub = '';
        }
        if (!isset($config->opt_deref)) {
             $config->opt_deref = LDAP_DEREF_NEVER;
        }
        if (!isset($config->preventpassindb)) {
             $config->preventpassindb = 0;
        }
        if (!isset($config->bind_dn)) {
            $config->bind_dn = '';
        }
        if (!isset($config->bind_pw)) {
            $config->bind_pw = '';
        }
        if (!isset($config->ldap_version)) {
            $config->ldap_version = '3';
        }
        if (!isset($config->objectclass)) {
            $config->objectclass = '';
        }
        if (!isset($config->memberattribute)) {
            $config->memberattribute = '';
        }
        if (!isset($config->memberattribute_isdn)) {
            $config->memberattribute_isdn = '';
        }
        if (!isset($config->removeuser)) {
            $config->removeuser = AUTH_REMOVEUSER_KEEP;
        }
        if (!isset($config->sync_condition)) {
            $config->sync_condition = '';
        }

        // Try to remove duplicates before storing the contexts (to avoid problems in sync_users()).
        $config->contexts = explode(';', $config->contexts);
        $config->contexts = array_map(create_function('$x', 'return textlib::strtolower(trim($x));'),
                                      $config->contexts);
        $config->contexts = implode(';', array_unique($config->contexts));

        // Save settings
        set_config('host_url', trim($config->host_url), $this->pluginconfig);
        set_config('ldapencoding', trim($config->ldapencoding), $this->pluginconfig);
        set_config('contexts', $config->contexts, $this->pluginconfig);
        set_config('user_type', textlib::strtolower(trim($config->user_type)), $this->pluginconfig);
        set_config('user_attribute', textlib::strtolower(trim($config->user_attribute)), $this->pluginconfig);
        set_config('search_sub', $config->search_sub, $this->pluginconfig);
        set_config('opt_deref', $config->opt_deref, $this->pluginconfig);
        set_config('preventpassindb', $config->preventpassindb, $this->pluginconfig);
        set_config('bind_dn', trim($config->bind_dn), $this->pluginconfig);
        set_config('bind_pw', $config->bind_pw, $this->pluginconfig);
        set_config('ldap_version', $config->ldap_version, $this->pluginconfig);
        set_config('objectclass', trim($config->objectclass), $this->pluginconfig);
        set_config('memberattribute', textlib::strtolower(trim($config->memberattribute)), $this->pluginconfig);
        set_config('memberattribute_isdn', $config->memberattribute_isdn, $this->pluginconfig);
        set_config('removeuser', $config->removeuser, $this->pluginconfig);
        set_config('sync_condition', trim($config->sync_condition), $this->pluginconfig);

        return true;
    }


    /**
     * Connect to the LDAP server, using the plugin configured
     * settings. It's actually a wrapper around ldap_connect_moodle()
     *
     * @return resource A valid LDAP connection (or dies if it can't connect)
     */
    function ldap_connect() {
        // Cache ldap connections. They are expensive to set up
        // and can drain the TCP/IP ressources on the server if we
        // are syncing a lot of users (as we try to open a new connection
        // to get the user details). This is the least invasive way
        // to reuse existing connections without greater code surgery.
        if(!empty($this->ldapconnection)) {
            $this->ldapconns++;
            return $this->ldapconnection;
        }

        if($ldapconnection = ldap_connect_moodle($this->config->host_url, $this->config->ldap_version,
                                                 $this->config->user_type, $this->config->bind_dn,
                                                 $this->config->bind_pw, $this->config->opt_deref,
                                                 $debuginfo)) {
            $this->ldapconns = 1;
            $this->ldapconnection = $ldapconnection;
            return $ldapconnection;
        }

        print_error('auth_ldapup1_noconnect_all', 'auth_ldapup1', '', $debuginfo);
    }

    /**
     * Disconnects from a LDAP server
     *
     */
    function ldap_close() {
        $this->ldapconns--;
        if($this->ldapconns == 0) {
            @ldap_close($this->ldapconnection);
            unset($this->ldapconnection);
        }
    }

    /**
     * Search specified contexts for username and return the user dn
     * like: cn=username,ou=suborg,o=org. It's actually a wrapper
     * around ldap_find_userdn().
     *
     * @param resource $ldapconnection a valid LDAP connection
     * @param string $extusername the username to search (in external LDAP encoding, no db slashes)
     * @return mixed the user dn (external LDAP encoding) or false
     */
    function ldap_find_userdn($ldapconnection, $extusername) {
        $ldap_contexts = explode(';', $this->config->contexts);

        return ldap_find_userdn($ldapconnection, $extusername, $ldap_contexts, $this->config->objectclass,
                                $this->config->user_attribute, $this->config->search_sub);
    }


// NEW FUNCTIONS, introduced by Silecs
    
    /**
     * displays or logs modified users ; called by sync_users()
     * @param string $output  'stdout' or 'file' or 'stderr'
     * @param string $msg  message to log
     */
    function do_log($output, $msg) {
        if ($output == 'stdout') {
            echo $msg;
        }
        elseif ($output == 'file') {
            $logdate = date('Y-m-d H:i:s  ');
            error_log($logdate . $msg, 3, "sync_users.log");
        }
        elseif ($output == 'stderr') {
            error_log($msg, 4);
        }
    }

    /**
     * returns the last sync from the logs
     */
    function get_last_sync() {
        global $DB;

        $sql = "SELECT MAX(timebegin) AS begin, MAX(timeend) AS end FROM {up1_cohortsync_log} WHERE action=?";
        $record = $DB->get_record_sql($sql, ['ldap:sync']);
        // if not found, null -> 19700101010000Z : ok
        date_default_timezone_set('UTC');
        $res = array(
            'begin' => date("YmdHis", $record->begin) . 'Z',
            'end' => date("YmdHis", $record->end) . 'Z',
        );
        return $res;
    }

} // End of the class
