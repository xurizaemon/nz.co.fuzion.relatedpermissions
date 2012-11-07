<?php

require_once 'relatedpermissions.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function relatedpermissions_civicrm_config(&$config) {
  _relatedpermissions_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function relatedpermissions_civicrm_xmlMenu(&$files) {
  _relatedpermissions_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function relatedpermissions_civicrm_install() {
  return _relatedpermissions_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function relatedpermissions_civicrm_uninstall() {
  return _relatedpermissions_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function relatedpermissions_civicrm_enable() {
  return _relatedpermissions_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function relatedpermissions_civicrm_disable() {
  return _relatedpermissions_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function relatedpermissions_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _relatedpermissions_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function relatedpermissions_civicrm_managed(&$entities) {
  return _relatedpermissions_civix_civicrm_managed($entities);
}
/*
 * Implement WHERE Clause - we find the contacts for whom this contact has permission and
 * specifically give permission to them
 */
function relatedpermissions_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
  if (!$contactID) {
    return;
  }
  $tmpTableName = _relatedpermissions_get_permissionedtable($contactID);

  $tables ['$tmpTableName'] = $whereTables ['$tmpTableName'] =
    " LEFT JOIN $tmpTableName permrelationships
     ON (contact_a.id = permrelationships.contact_id)";
  if(empty($where)){
    $where = " permrelationships.contact_id IS NOT NULL ";
  }
  else{
    $where .= " AND permrelationships.contact_id IS NOT NULL ";
  }
}
/*
 * Create temporary table of all permissioned contacts
 */
function _relatedpermissions_get_permissionedtable($contactID) {
  $tmpTableName = 'myrelationships' . rand(10000, 100000);
  $now = date('Y-m-d');

  $sql = "CREATE TEMPORARY TABLE $tmpTableName
    (
    `contact_id` INT(10) NULL,
    INDEX `contact_id` (`contact_id`)
    )
  ENGINE=HEAP";
  CRM_Core_DAO::executeQuery($sql);
  $sql = "INSERT INTO $tmpTableName
    SELECT contact_id_a FROM civicrm_relationship
    WHERE contact_id_b = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date > NOW() )
    AND (end_date IS NULL OR end_date < NOW())
    AND is_permission_b_a = 1
  ";
  CRM_Core_DAO::executeQuery($sql);
  $sql = "INSERT INTO $tmpTableName
    SELECT contact_id_b FROM civicrm_relationship
    WHERE contact_id_a = $contactID
    AND is_active = 1
    AND (start_date IS NULL OR start_date > NOW() )
    AND (end_date IS NULL OR end_date < NOW())
    AND is_permission_a_b = 1
  ";
  CRM_Core_DAO::executeQuery($sql);

  return $tmpTableName;
}