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
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap
 */

$string['pluginname'] = 'LDAP sync - UP1 variant';
$string['pluginnotenabled'] = 'Plugin not enabled!';

$string['auth_ldapup1_ad_create_req'] = 'Cannot create the new account in Active Directory. Make sure you meet all the requirements for this to work (LDAPS connection, bind user with adequate rights, etc.)';
$string['auth_ldapup1_attrcreators'] = 'List of groups or contexts whose members are allowed to create attributes. Separate multiple groups with \';\'. Usually something like \'cn=teachers,ou=staff,o=myorg\'';
$string['auth_ldapup1_attrcreators_key'] = 'Attribute creators';
$string['auth_ldapup1_auth_user_create_key'] = 'Create users externally';
$string['auth_ldapup1_bind_dn'] = 'If you want to use bind-user to search users, specify it here. Something like \'cn=ldapuser,ou=public,o=org\'';
$string['auth_ldapup1_bind_dn_key'] = 'Distinguished name';
$string['auth_ldapup1_bind_pw'] = 'Password for bind-user.';
$string['auth_ldapup1_bind_pw_key'] = 'Password';
$string['auth_ldapup1_bind_settings'] = 'Bind settings';
$string['auth_ldapup1_contexts'] = 'List of contexts where users are located. Separate different contexts with \';\'. For example: \'ou=users,o=org; ou=others,o=org\'';
$string['auth_ldapup1_contexts_key'] = 'Contexts';
$string['auth_ldapup1_create_error'] = 'Error creating user in LDAP.';
$string['auth_ldapup1description'] = 'This method specific to Paris 1 provides a simplified LDAP connection,
        allowing only to sync users. No authentication, no user update.';
$string['auth_ldapup1_extrafields'] = 'These fields are optional.  You can choose to pre-fill some Moodle user fields with information from the <b>LDAP fields</b> that you specify here. <p>If you leave these fields blank, then nothing will be transferred from LDAP and Moodle defaults will be used instead.</p><p>In either case, the user will be able to edit all of these fields after they log in.</p>';

$string['auth_ldapup1_groupecreators'] = 'List of groups or contexts whose members are allowed to create groups. Separate multiple groups with \';\'. Usually something like \'cn=teachers,ou=staff,o=myorg\'';
$string['auth_ldapup1_groupecreators_key'] = 'Group creators';
$string['auth_ldapup1_host_url'] = 'Specify LDAP host in URL-form like \'ldap://ldap.myorg.com/\' or \'ldaps://ldap.myorg.com/\' Separate multipleservers with \';\' to get failover support.';
$string['auth_ldapup1_host_url_key'] = 'Host URL';
$string['auth_ldapup1_changepasswordurl_key'] = 'Password-change URL';
$string['auth_ldapup1_ldap_encoding'] = 'Specify encoding used by LDAP server. Most probably utf-8, MS AD v2 uses default platform encoding such as cp1252, cp1250, etc.';
$string['auth_ldapup1_ldap_encoding_key'] = 'LDAP encoding';
$string['auth_ldapup1_login_settings'] = 'Login settings';
$string['auth_ldapup1_memberattribute'] = 'Optional: Overrides user member attribute, when users belongs to a group. Usually \'member\'';
$string['auth_ldapup1_memberattribute_isdn'] = 'Optional: Overrides handling of member attribute values, either 0 or 1';
$string['auth_ldapup1_memberattribute_isdn_key'] = 'Member attribute uses dn';
$string['auth_ldapup1_memberattribute_key'] = 'Member attribute';
$string['auth_ldapup1_noconnect'] = 'LDAP-module cannot connect to server: {$a}';
$string['auth_ldapup1_noconnect_all'] = 'LDAP-module cannot connect to any servers: {$a}';
$string['auth_ldapup1_noextension'] = '<em>The PHP LDAP module does not seem to be present. Please ensure it is installed and enabled if you want to use this authentication plugin.</em>';
$string['auth_ldapup1_no_mbstring'] = 'You need the mbstring extension to create users in Active Directory.';
$string['auth_ldapup1notinstalled'] = 'Cannot use LDAP authentication. The PHP LDAP module is not installed.';
$string['auth_ldapup1_objectclass'] = 'Optional: Overrides objectClass used to name/search users on ldap_user_type. Usually you dont need to chage this.';
$string['auth_ldapup1_objectclass_key'] = 'Object class';
$string['auth_ldapup1_opt_deref'] = 'Determines how aliases are handled during search. Select one of the following values: "No" (LDAP_DEREF_NEVER) or "Yes" (LDAP_DEREF_ALWAYS)';
$string['auth_ldapup1_opt_deref_key'] = 'Dereference aliases';

$string['auth_ldapup1_preventpassindb'] = 'Select yes to prevent passwords from being stored in Moodle\'s DB.';
$string['auth_ldapup1_preventpassindb_key'] = 'Hide passwords';
$string['auth_ldapup1_search_sub'] = 'Search users from subcontexts.';
$string['auth_ldapup1_search_sub_key'] = 'Search subcontexts';
$string['auth_ldapup1_server_settings'] = 'LDAP server settings';
$string['auth_ldapup1_unsupportedusertype'] = 'auth: ldap user_create() does not support selected usertype: {$a}';
$string['auth_ldapup1_update_userinfo'] = 'Update user information (firstname, lastname, address..) from LDAP to Moodle.  Specify "Data mapping" settings as you need.';
$string['auth_ldapup1_user_attribute'] = 'Optional: Overrides the attribute used to name/search users. Usually \'cn\'.';
$string['auth_ldapup1_user_attribute_key'] = 'User attribute';
$string['auth_ldapup1_user_exists'] = 'LDAP username already exists.';
$string['auth_ldapup1_user_settings'] = 'User lookup settings';
$string['auth_ldapup1_user_type'] = 'Select how users are stored in LDAP. This setting also specifies how login expiration, grace logins and user creation will work.';
$string['auth_ldapup1_user_type_key'] = 'User type';
$string['auth_ldapup1_usertypeundefined'] = 'config.user_type not defined or function ldap_expirationtime2unix does not support selected type!';
$string['auth_ldapup1_usertypeundefined2'] = 'config.user_type not defined or function ldap_unixi2expirationtime does not support selected type!';
$string['auth_ldapup1_version'] = 'The version of the LDAP protocol your server is using.';
$string['auth_ldapup1_version_key'] = 'Version';

$string['connectingldap'] = "Connecting to LDAP server...\n";
$string['creatingtemptable'] = "Creating temporary table {\$a}\n";
$string['didntfindexpiretime'] = 'password_expire() didn\'t find expiration time.';
$string['didntgetusersfromldap'] = "Did not get any users from LDAP -- error? -- exiting\n";
$string['gotcountrecordsfromldap'] = "Got {\$a} records from LDAP\n";
$string['morethanoneuser'] = 'Strange! More than one user record found in ldap. Only using the first one.';
$string['needbcmath'] = 'You need the BCMath extension to use grace logins with Active Directory';
$string['needmbstring'] = 'You need the mbstring extension to change passwords in Active Directory';
$string['nodnforusername'] = 'Error in user_update_password(). No DN for: {$a->username}';
$string['noemail'] = 'Tried to send you an email but failed!';
$string['notcalledfromserver'] = 'Should not be called from the web server!';
$string['noupdatestobedone'] = "No updates to be done\n";
$string['nouserentriestoremove'] = "No user entries to be removed\n";
$string['nouserentriestorevive'] = "No user entries to be revived\n";
$string['nouserstobeadded'] = "No users to be added\n";

$string['renamingnotallowed'] = 'User renaming not allowed in LDAP';
$string['rootdseerror'] = 'Error querying rootDSE for Active Directory';
$string['updateremfail'] = 'Error updating LDAP record. Error code: {$a->errno}; Error string: {$a->errstring}<br/>Key ({$a->key}) - old moodle value: \'{$a->ouvalue}\' new value: \'{$a->nuvalue}\'';
$string['updateremfailamb'] = 'Failed to update LDAP with ambiguous field {$a->key}; old moodle value: \'{$a->ouvalue}\', new value: \'{$a->nuvalue}\'';
$string['updatepasserror'] = 'Error in user_update_password(). Error code: {$a->errno}; Error string: {$a->errstring}';
$string['updatepasserrorexpire'] = 'Error in user_update_password() when reading password expiration time. Error code: {$a->errno}; Error string: {$a->errstring}';
$string['updatepasserrorexpiregrace'] = 'Error in user_update_password() when modifying expirationtime and/or gracelogins. Error code: {$a->errno}; Error string: {$a->errstring}';
$string['updateusernotfound'] = 'Could not find user while updating externally. Details follow: search base: \'{$a->userdn}\'; search filter: \'(objectClass=*)\'; search attributes: {$a->attribs}';
$string['user_activatenotsupportusertype'] = 'auth: ldap user_activate() does not support selected usertype: {$a}';
$string['user_disablenotsupportusertype'] = 'auth: ldap user_disable() does not support selected usertype: {$a}';
$string['userentriestoadd'] = "User entries to be added: {\$a}\n";
$string['userentriestoremove'] = "User entries to be removed: {\$a}\n";
$string['userentriestorevive'] = "User entries to be revived: {\$a}\n";
$string['userentriestoupdate'] = "User entries to be updated: {\$a}\n";
$string['usernotfound'] = 'User not found in LDAP';
$string['useracctctrlerror'] = 'Error getting userAccountControl for {$a}';

$string['sync_condition'] = 'Optional: eg. "modifyTimestamp>[%lastcron%]"';
$string['sync_condition_key'] = 'Sync condition';