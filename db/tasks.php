<?php
/**
 * Definition of auth_ldapup1 tasks.
 *
 * @package    auth_ldapup1
 * @category   task
 * @copyright  2020 Silecs
 * derived from auth_ldap task by 2015 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'auth_ldapup1\task\sync_task',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '1',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    ]
];
