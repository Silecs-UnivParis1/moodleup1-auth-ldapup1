<?php
/**
 * @package    auth_ldapup1
 * @copyright  2012-2025 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap 2012-2020
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2025052200;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2024100700;        // Requires this Moodle version (4.5)
$plugin->component = 'auth_ldapup1';       // Full name of the plugin (used for diagnostics)

$plugin->dependencies = ['auth_ldap' => 2024100100];
