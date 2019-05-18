<?php

/**
 * @file
 * These are the hooks that are invoked by Monster Menus.
 *
 * This file is here for documentation purposes only. It does not get included
 * in the running code.
 */

use Drupal\Core\Config\Config;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Plugin\Block\MMTreeBlock;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * @addtogroup mm_hooks
 * @{
 */

/**
 * Define settings which are inherited by lower levels of the MM tree. Data
 * passed to mm_content_insert_or_update() is saved using this definition.
 *
 * @see mm_content_get_cascaded_settings()
 *
 * @return array
 *   An associative array containing the settings. Each key is a unique ID. Each
 *   value is an associative array with one or more of these elements, which
 *   describe the stored data:
 *   - data_type:   'int' (integer) or 'string'
 *   - multiple:    TRUE if multiple values are accepted
 *   - not_empty:   TRUE if only !empty() values should be stored (FALSE)
 *   - user_access: user must have $account->hasPermission() for this value in
 *                  order to set the setting
 *   - use_keys:    TRUE if 'multiple' and data is in an associative array
 *     (TRUE)
 */
function hook_mm_cascaded_settings() {
  return array(
    'share_default' => array(
      'data_type' => 'int',
      'multiple' => TRUE,
      'use_keys' => TRUE,
    )
  );
}

/**
 * Determine whether the current user may perform the given operation on the
 * specified node.
 *
 * @param string $op
 *   The operation to be performed on the node. Possible values are:
 *   - "view"
 *   - "update"
 *   - "delete"
 *   - "create"
 * @param NodeInterface $node
 *   The node object (or node array) on which the operation is to be performed,
 *   or node type (e.g. 'forum') for "create" operation.
 * @param User $account
 *   Optional, a user object representing the user for whom the operation is to
 *   be performed. Determines access for a user other than the current user.
 * @return bool|void
 *   TRUE if the operation may be performed.
 */
function hook_mm_node_access($op, NodeInterface $node, User $account) {
  if ($node->id() && _mm_workflow_access_node_has_workflow($node)) {
    if ($op == 'view') $mode = Constants::MM_PERMS_READ;
    elseif ($op == 'update') $mode = Constants::MM_PERMS_WRITE;
    elseif ($op == 'delete') $mode = 'd';
    else return;

    if (!isset($node->_workflow)) $node->_workflow = workflow_node_current_state($node);
    return _mm_workflow_access_get_user_perm($node, $mode, $account);
  }
}

/**
 * Alter the per-user list of allowed regions for content reordering.
 *
 * @param array $allowed
 *   An array of theme regions in which the user can place content.
 * @param AccountInterface $account
 *   The user object for whom we are evaluating access.
 * @param string $type
 *   If set, the list is further limited to only those regions that are allowed
 *   for a specific content type.
 */
function hook_mm_allowed_regions_for_user_alter(&$allowed, AccountInterface $account, $type) {
  // If the user does not have the Administer Blocks permission, regardless of
  // MM page permissions, restrict them to the "Content" region (if it exists).
  if (!$account->hasPermission('administer blocks')) {
    $allowed = array_intersect($allowed, array('content'));
  }
}

/**
 * Alter nodes just prior to creation during mm_content_copy().
 *
 * @param NodeInterface $node
 *   The node object to be saved
 * @param array $old_catlist
 *   The original value of the mm_catlist element of the node object in the
 *   source of the copy
 */
function hook_mm_copy_tree_node_alter(NodeInterface &$node, $old_catlist) {
  if ($node->getType() == 'mm_calendar') {
    // When copying a calendar node, update the list of subscribed pages to
    // point to the new node's location
    $old_catlist = array_keys($old_catlist);
    foreach ($node->field_mm_pages as $pos => $mm_page)
      if (in_array($mm_page['value'], $old_catlist)) {
        $new_cat = array_keys($node->mm_catlist);
        $node->field_mm_pages[$pos]['value'] = $new_cat[0];
      }
  }
}

/**
 * Alter tree entries just prior to creation during mm_content_copy().
 *
 * @param object $new
 *   The MM Tree entry about to be created
 * @param int $dest_mmtid
 *   The destination MM Tree entry
 */
function hook_mm_copy_tree_tree_alter(&$new, $dest_mmtid) {
  // This is a silly example, which appends "(copy)" to the item's name.
  $new->name .= ' ' . t('(copy)');
}

/**
 * Alter the processing of tree entries during mm_content_copy(). If the
 * function returns -1, the entry and any children will be skipped; if it
 * returns 1, just the current entry is skipped; if it returns 0, all further
 * processing is canceled; any other return value leads to no change. The
 * function can also alter the item passed to it.
 *
 * @param object $item
 *   The source MM Tree entry
 * @param array $options
 *   A temporary copy of the $options parameter passed to mm_content_copy().
 *   This can be altered to affect the behavior of the rest of the copy
 *   operation for just the current item.
 */
function hook_mm_copy_tree_iterate_alter(&$item, &$options) {
}

/**
 * This hook is called by mm_content_delete() when MM is permanently deleting a
 * tree entry. It allows modules to clean up database entries associated with
 * the entry and, if specified, the nodes appearing there.
 *
 * @param array $mmtids
 *   Array of tree IDs, describing the entries being deleted
 * @param array $nids
 *   Array of node IDs, describing the nodes being deleted. This aspect can also
 *   be handled in hook_delete(), but can be faster if performed in this hook,
 *   because more than one node can be processed at once.
 */
function hook_mm_delete($mmtids, $nids) {
  $db = Database::getConnection();
  $db->delete('mm_workflow_access')
    ->condition('gid', (array) $mmtids, 'IN')
    ->execute();
}

/**
 * This hook is called by mm_content_set_perms() whenever a page's permissions
 * are about to be modified.
 *
 * @param int $mmtid
 *   The MM tree ID of the entry being changed
 * @param array $perms
 *   An array of arrays ['r', 'w', 'a', 'u']['groups', 'users']. All are
 *   optional. For 'groups', an array of gids; for 'users' an array of uids.
 * @param bool $is_group
 *   TRUE if the item is a group
 * @param bool $clear_old
 *   TRUE if any existing permissions will be removed. If the calling function
 *   has only just created the entry, it passes FALSE to avoid the overhead of
 *   trying to delete permissions that aren't even set.
 */
function hook_mm_content_set_perms($mmtid, $perms, $is_group, $clear_old) {
}

/**
 * This hook is called by mm_content_update_parents() after a tree entry's list
 * of parent nodes has been updated.
 *
 * @param int $mmtid
 *   ID of the entry to update, or NULL to update all entries
 * @param array $parents
 *   Array of parent IDs
 * @param bool $is_new
 *   TRUE if the entry doesn't already have parents
 */
function hook_mm_content_update_parents($mmtid, $parents, $is_new) {
}

/**
 * Add one or more alias to human-readable name expansions to the list used by
 * mm_content_expand_name().
 *
 * @return array
 *   An associative array. It may contain a simple replacement, where the key is
 *   the original name and the expanded name is the value.
 *
 *   Alternately, it may contain an associative array with these keys:
 *   - callback: A function to be called with the original name as a parameter
 *   - name:     A string containing the default name, used if the callback is
 *               missing or returns an empty value
 *   In this format, either "name" or "callback" is required.
 */
function hook_mm_item_name() {
  return array(
    // A simple replacement
    '.calendar' => t('[Calendar default]'),
    // A callback
    '.Users' => array(
      'callback' => 'my_name_function',
    ),
    // A callback with a static name if the callback does not return anything
    '.Groups' => array(
      'callback' => 'my_name_function',
      'name' => t('Groups'),
    ),
  );
}

/**
 * Define one or more tree ID to human-readable name expansions, as used by
 * mm_content_get_name().
 *
 * @return array
 *   An associative array. It may contain a simple replacement, where the key is
 *   the tree ID and the name is the value.
 *
 *   Alternately, it may contain an associative array with these keys:
 *   - callback: A function to be called with the tree object as a parameter
 *   - name:     A string containing the default name, used if the callback is
 *               missing or returns an empty value
 *   In this format, either "name" or "callback" is required.
 */
function hook_mm_mmtid_name() {
  return array(
    // A simple replacement
    7 => t('Home Page'),
    // A callback
    123 => array(
      'callback' => 'my_name_function',
    ),
    // A callback with a static name if the callback does not return anything
    456 => array(
      'callback' => 'my_name_function',
      'name' => t('Groups'),
    ),
  );
}

/**
 * Add one or more MM tree names to the list that is hidden from non-admin
 * users.
 *
 * @return array
 *   An array of names. Each name usually starts with '.'.
 */
function hook_mm_hidden_user_names() {
  return array('.Calendar');
}

/**
 * Alter MM's behavior according to the node type being rendered. The results of
 * this hook can be queried using mm_get_node_info().
 *
 * @param int $mmtid
 *   ID of the Tree object for which node info is being requested. May be NULL
 *   if no specific page is being requested.
 * @return array
 *   An associative array whose key is the name of a content type, and whose
 *   value is an associative array containing one or more flags. The currently
 *   defined flags are:
 *   - Constants::MM_NODE_INFO_NO_RENDER:   Don't render this node type
 *   - Constants::MM_NODE_INFO_ADD_HIDDEN:  Hide the Add node link for this type
 */
function hook_mm_node_info($mmtid) {
  return array(
    // mm_event is a CCK content type, but we need to set flags to prevent
    // MM from rendering and allowing users to create this node type.
    'mm_event' => array(
      Constants::MM_NODE_INFO_NO_RENDER => TRUE,
      Constants::MM_NODE_INFO_ADD_HIDDEN => TRUE,
    )
  );
}

/**
 * Alter the values set by hook_mm_node_info().
 *
 * @param array $list
 *   Array where the key is the flags type (MM_NODE_INFO_*) and the value is an
 *   array of node types for which the flag is set.
 * @param object $tree
 *   Tree object for which node info is being requested. May be NULL if no
 *   specific page is being requested.
 */
function hook_mm_node_info_alter($list, $tree) {
}

/**
 * Define one or more functions to be called whenever MM renders nodes at a
 * particular part of the tree. Output can be appended to, pre-pended to, or
 * completely replace the normal list of nodes.
 *
 * The hook should return an associative array where the key is the page's path,
 * and the value is an associative array containing one or more values that
 * define how the callback should be used.
 *
 * The format and meaning of the array is similar to Drupal's hook_menu(). The
 * path can be a direct match, such as "foo/bar/baz", or the "%" and "*"
 * wildcards can be included any number of times, as in "foo/%/%".  The "%"
 * wildcard matches one or more characters up to (and excluding) a slash "/".
 * The "*" wildcard matches zero or more characters up to a slash, and is
 * particularly useful for 'partial path' routes (described below). Other types
 * of wildcard, such as "%node", are not currently supported. Path matching is
 * performed on the entire URL, regardless of whether or not some of the later
 * path elements actually exist in the MM tree.
 *
 * It is also important to note that, unlike hook_menu() callbacks, all matching
 * hook_mm_showpage_routing() callbacks are called--not just the "best" match.
 * For example, if you have the paths "foo/bar" and "foo/%", and the URL
 * "foo/bar" is requested, then both callbacks will be used. It may be necessary
 * to add an access callback to prevent the "foo/%" callback from being used.
 *
 * Information returned by each module's implementation of this hook is cached.
 * Whenever you have made a change to your code, you need to either rebuild the
 * menu tree or call _mm_showpage_router(TRUE) in order for the change to take
 * effect.
 *
 * These options are supported:
 * - 'block id'
 *   If specified, the callback will only be used if the output is part of a
 *   specific block within the page template.
 * - 'file'
 *   If set, the specified file will be loaded with require_once() prior to
 *   calling any callbacks. The file's path should be relative to the module
 *   containing this hook.
 * - 'partial path'
 *   If TRUE, the URL supplied by the user will match if it begins with the
 *   portion of the path in the array key; otherwise, an exact match is
 *   required. This option is FALSE, by default.
 * - 'access callback'
 *   The callback to use when determining whether or not to call the page
 *   callback. If this function exists and returns FALSE, the page callback is
 *   not called. If, instead of a function name, one of the booleans TRUE or
 *   FALSE is given as the callback, that value controls the calling of the page
 *   callback.
 *
 *   Note that, in contrast to hook_menu(), access callbacks in this hook do not
 *   inherit access from their parents: each level is handled separately. Also,
 *   attempting to access a page that is not allowed simply results in no output
 *   being appended to the page, rather than a "403" or "access denied" message.
 *
 *   If this callback is left at its default value, the equivalent of
 *   "mm_content_user_can('_mmtid_', MM_PERMS_READ)" is performed.
 * - 'access arguments'
 *   Arguments passed to the access callback. This is an array representing the
 *   order and numeric position of the values to pass to the callback. Integer
 *   values are taken to mean "add the Nth path element"; the constant "_mmtid_"
 *   means "pass the MM tree ID of the lowest, existing page"; the constant
 *   "_block_id_" means "pass the block ID"; the constant "_oargs_" means "pass
 *   any path elements after the last matching MM page; the constant "_all_"
 *   means "pass all the arguments"; any other value is passed unmodified.
 * - 'page callback'
 *   The callback which produces the output to insert into the page. If the
 *   callback returns a non-empty string, that value is appended to the page,
 *   below any other nodes. It is also possible to return an array containing
 *   these keys:
 *   - 'output_pre'
 *     Content which is pre-pended to the normal node list
 *   - 'output_post'
 *     Content which is appended to the normal node list
 *   - 'no_nodes'
 *     If TRUE, completely suppress the normal node list
 *
 *   // Simple append
 *   return '<p>This is my output</p>';
 *
 *   // Prepend
 *   return array('output_pre' => '<p>This is my output</p>');
 *
 *   // Suppress nodes
 *   return array(
 *     'output_pre' => '<p>This is my output</p>',
 *     'no_nodes' => TRUE);
 *
 *   Alternately, if the array key 'by_region' is used, then the sub-array is
 *   indexed by the machine name of one or more regions into which output will
 *   be placed. The array value can follow any of the conventions listed above.
 *
 *   // Add content to more than one region at the same time
 *   return array(
 *     'by_region' => array(
 *       'content' => '<p>This is appended to the main content region</p>',
 *       'footer' => array(
 *         'output_pre' => '<p>This replaces the footer</p>',
 *         'no_nodes' => TRUE,
 *       ),
 *     ),
 *   );
 *
 * - 'page arguments'
 *   Arguments passed to the page callback. This is an array representing the
 *   order and numeric position of the values to pass to the callback. Integer
 *   values are taken to mean "add the Nth path element"; the constant "_mmtid_"
 *   means "pass the MM tree ID of the lowest, existing page"; the constant
 *   "_all_" means "pass all the arguments"; any other value is passed
 *   unmodified.
 *
 * @return array
 *   An associative array where the key is the page's path, and the value is an
 *   associative array containing one or more of the values defined above.
 */
function hook_mm_showpage_routing() {
  $items = array();
  $items['myamherst'] = array(
    'page callback' => 'amhp_myamherst_getcontent',
    'page arguments' => array('_mmtid_', 1),
    'partial path' => TRUE,
  );
  $items['alumni/classpages/%/classmates'] = array(
    'file' => 'amherstprofile_search.inc',
    'page callback' => 'amhp_search_classmates_reunion',
    'page arguments' => array(2),
    'access callback' => '_amhp_search_classmates_reunion_access',
    'access arguments' => array('_mmtid_', 2),
  );
  $items['people/students/%/classmates'] = array(
    'file' => 'amherstprofile_search.inc',
    'page callback' => 'amhp_search_classmates_grad_year',
    'page arguments' => array(2, TRUE, 3),
    'access callback' => '_amhp_search_classmates_people_students_access',
    'access arguments' => array('_mmtid_', 2),
  );

  return $items;
}

/**
 * Define one or more flags in the UI for a page's settings
 *
 * @return array
 *   An associative array where the key is the flag's name, and each value is an
 *   associative array containing the Forms API element to describe the UI to
 *   set that flag. Currently, the only supported types ("#type") are checkbox
 *   and textfield.
 *
 *   Additionally, these special keys can be used:
 *   - #flag_inherit: By default, no flags are inherited by newly created
 *     sub-pages/groups. Set this key's value to TRUE to have the flag, when
 *     present, copied to new sub-pages.
 *   - #flag_copy: By default, flags are always copied by mm_content_copy(). To
 *     change this behavior, set this key's value to FALSE.
 */
function hook_mm_tree_flags() {
  return array(
    'limit_write' => array('#type' => 'checkbox', '#description' => t('MM: Prevents non-admin users from changing "Delete or change settings"')),
    'no_breadcrumb' => array('#type' => 'checkbox', '#description' => t('MM: Prevents the page breadcrumb from showing at this level')),
  );
}

/**
 * This hook is called within Monster Menus' implementation of
 * hook_url_outbound_alter().
 *
 * @param $mmtid
 *   Tree ID of the entry referred to by $path, or NULL if it does not refer to
 *   part of the MM tree
 * @param $path
 *   The alias of the $original_path as defined in the database. If there is no
 *   match in the database it will be the same as $original_path.
 * @param $options
 *   An array of link attributes such as query and fragment. See url().
 * @param $original_path
 *   The unaliased Drupal path that is being linked to.
 */
function hook_mm_url_rewrite_outbound($mmtid, &$path, &$options, $original_path) {
  // Add a query string for one specific case
  $mm_calendar = \Drupal::request()->query->get('mm_calendar', '');
  if (empty($options['query']) && !empty($mm_calendar)) {
    $options['query'] = http_build_query(array('destination' => "mm/$mmtid?mm_calendar=" . $mm_calendar));
  }
}

/**
 * This hook is called within Monster Menus' menu route rewriting code, at a
 * point before checks are done to ensure that no parts of the tree contain
 * aliases which collide with menu paths. This allows the offending menu paths
 * to be renamed.
 *
 * @param RouteCollection $collection
 *   Menu routing collection to modify
 */
function hook_mm_routing_alter(RouteCollection $collection) {
}

/**
 * Define queries which test the relationships between keys in a module's
 * database tables. This hook is usually implemented in an .install file. It is
 * only called when a user visits admin/mm/integrity.
 *
 * @return array
 *   An associative array where the key is the human-readable module name, and
 *   the value is an associative array. This inner array's keys are human-
 *   readable descriptions of the test being performed, and the values are a
 *   query segment, without "SELECT * FROM".
 */
function hook_mm_verify_integrity() {
  return array('MM Workflow Access' => array(
    (string) t('mm_workflow_access.sid refers to missing workflow_states.sid') =>
      '{mm_workflow_access} x LEFT JOIN {workflow_states} s ON s.sid=x.sid WHERE s.sid IS NULL',
    (string) t('mm_workflow_access.gid refers to missing mm_tree.mmtid') =>
      '{mm_workflow_access} x LEFT JOIN {mm_tree} t ON t.mmtid=x.gid WHERE x.gid>0 AND t.mmtid IS NULL',

    (string) t('mm_workflow_author.nid refers to missing node.nid') =>
      '{mm_workflow_author} x LEFT JOIN {node} n ON n.nid=x.nid WHERE n.nid IS NULL',
    (string) t('mm_workflow_author.uid refers to missing users.uid') =>
      '{mm_workflow_author} x LEFT JOIN {users} u ON u.uid=x.uid WHERE u.uid IS NULL AND x.uid>0',
  ));
}

/**
 * Alter the query used to determine the members of an MM group.
 *
 * @param int $mmtids
 *   An array of MM tree IDs of the group(s) being queried
 * @param string $query
 *   The regular query to be modified
 * @param string $countquery
 *   The query which returns the row count
 */
function hook_mm_get_users_in_group_alter($mmtids, &$query, &$countquery) {
}

/**
 * This hook is called by MM during hook_user_delete(). If the return is TRUE,
 * MM does not do any processing during its hook_user_delete(). The function is
 * free to modify $account here, as it can during its own hook_user_delete().
 *
 * @param AccountInterface $account
 *   The user being deleted.
 * @return bool|void
 */
function hook_mm_user_delete(AccountInterface $account) {
}

/**
 * This hook is called by MM during hook_user_insert(). If the return is TRUE,
 * MM does not do any processing during its hook_user_insert(). The function is
 * free to modify $account here, as it can during its own hook_user_insert().
 *
 * @param AccountInterface $account
 *   The user being inserted.
 * @return bool|void
 */
function hook_mm_user_insert(AccountInterface $account) {
}

/**
 * This hook is called by MM during hook_user_update(). If the return is TRUE,
 * MM does not do any processing during its hook_user_update(). The function is
 * free to modify $account here, as it can during its own hook_user_update().
 *
 * @param AccountInterface $account
 *   The user being updated.
 * @return bool|void
 */
function hook_mm_user_update(AccountInterface $account) {
}

/**
 * This hook is called by MM during hook_user_load(). If the return is TRUE, MM
 * does not do any processing during its hook_user_load(). The function is free
 * to modify $users here, as it can during its own hook_user_load().
 *
 * @param array $users
 *   The users being loaded.
 * @return bool|void
 */
function hook_mm_user_load(&$users) {
}

/**
 * Alter the HTML code which appears when a page is empty
 *
 * @param object $entry
 *   The MM tree entry of the page
 * @param array $list
 *   An array of HTML code segments, which are concatenated to produce the final
 *   page. Upon entry, $list[0] contains the default message supplied by MM.
 *   This entry can be modified, or the list appended to.
 * @param array $links
 *   An array of links that will become an unordered list at the bottom of the
 *   page. It may be appended to, in order to give the viewer more options.
 */
function hook_mm_empty_page_alter($entry, &$list, &$links) {
}

/**
 * Alter the HTML code which appears when no homepage exists for a user who can
 * have one.
 *
 * @param AccountInterface $account
 *   The user object of the user without a homepage
 * @param array $list
 *   An array of HTML code segments, which are concatenated to produce the final
 *   page. Upon entry, $list[0] contains the default message supplied by MM.
 *   This entry can be modified, or the list appended to.
 * @param array $links
 *   An array of links that will become an unordered list at the bottom of the
 *   page. It may be appended to, in order to give the viewer more options.
 */
function hook_mm_missing_homepage_alter(AccountInterface $account, &$list, &$links) {
  $list[] = t('<p>This is an additional message.</p>');
  $links[] = array(
    'title' => t('View profile'),
    'href' => 'profile/' . $account->id(),
  );
}

/**
 * Alter the data used in mm_content_add_user() to create new user home pages.
 *
 * @param AccountInterface $account
 *   User object to create a home page for
 * @param int $dest_mmtid
 *   Tree ID of the parent entry, under which the new home page is created
 * @param string $full_name
 *   The menu name of the user's home page
 * @return bool|void
 *   If FALSE is returned, no home page is created.
 */
function hook_mm_add_user_alter(AccountInterface $account, &$dest_mmtid, &$full_name) {
}

/**
 * Called after mm_content_add_user() has successfully created a user's home
 * page.
 *
 * @param AccountInterface $account
 *   User object for whom the home page was created
 * @param int $new_mmtid
 *   Tree ID of the user's new home page
 * @param int $dest_mmtid
 *   Tree ID of the parent entry, under which the new home page was created
 */
function hook_mm_add_user_post(AccountInterface $account, $new_mmtid, $dest_mmtid) {
}

/**
 * Alter the data returned by mm_content_uid2name().
 *
 * @param AccountInterface $usr
 *   User object; these fields can be modified, in order to change what is
 *   eventually returned by mm_content_uid2name():
 *   - pref_fml: The user's long name, in "first, middle, last" format
 *   - pref_lfm: The user's long name, in "last, first, middle" format
 *   - last:     The user's surname
 *   - first:    The user's first name
 *   - name:     The user's Drupal username
 *   - middle:   The user's middle name
 *   - hover:    Optional text to appear when the mouse is hovering over a link
 * @param bool $disabled
 *   If TRUE upon entry, the user is known to be inactive (status == 0); can be
 *   modified
 */
function hook_mm_uid2name_alter(AccountInterface $usr, &$disabled) {
  if (!$disabled && $usr->getAccountName() === 'disabled-user') {
    $disabled = TRUE;
  }
}

/**
 * Alter the query used by mm_regenerate_vgroup() to pre-calculate the top N
 * users in a virtual group. This allows modules to impose their own sorting on
 * the list.
 *
 * @param string $query
 *   The query
 */
function hook_mm_regenerate_vgroup_preview_alter(&$query) {
}

/**
 * Generate a query for DefaultController::autocomplete() to use when getting a
 * list of users that match an autocomplete request.
 *
 * @param string $string
 *   The autocomplete string typed by the user
 * @param int $limit
 *   The maximum number of results to return
 * @param int $min_string
 *   The minimum number of characters the user needs to type
 * @param mixed $misc
 *   A miscellaneous value that is passed to DefaultController::autocomplete()
 *   in the $misc parameter; DefaultController::autocomplete(), itself, ignores
 *   this value.
 * @return StatementInterface|null
 *   A StatementInterface object already executed, or NULL if the user needs to
 *   type more characters
 */
function hook_mm_autocomplete_alter($string, $limit, $min_string, $misc) {
}

/**
 * Alter the configuration page, found at admin/settings/mm.
 *
 * When creating form elements that refer to config settings containing
 * sub-elements (with dots), change the dots to dashes. For instance:
 *   $form['foo-bar'] = [
 *     '#type' => 'text',
 *     '#default_value' => $settings->get('foo.bar')
 *   ]
 *
 * @param array $form
 *   The Forms API array, which can be altered
 * @param Config $settings
 *   MM's settings object, which can be used to get initial values
 */
function hook_mm_config_alter(&$form, Config $settings) {
}

/**
 * Suppress the display of content within one or more blocks, during
 * monster_menus_block('view', ...)
 *
 * @param int $this_mmtid
 *   The MM tree ID of the outer page containing the block
 * @param MMTreeBlock $block
 *   The block being rendered
 * @return bool|void
 *   TRUE if the block should be rendered. If any implementation of this hook
 *   returns FALSE, the block is not rendered.
 */
function hook_mm_menus_block_shown($this_mmtid, MMTreeBlock $block) {
}

/**
 * Add one or more buttons or jQuery.ui selectmenu lists to the navigation bar
 * in the tree browser.
 *
 * @param string $mode
 *   One of the BROWSER_MODE_* constants
 * @param int $top_mmtid
 *   The MM tree ID of the topmost, displayed page in the tree
 * @return string
 *   A string containing a <select> or <button> element. A <select> must have
 *   the CSS classes ui-widget and ui-corner-all. A <button> must include these
 *   and ui-button.
 */
function hook_mm_browser_navigation($mode, $top_mmtid) {
}

/**
 * Alter the buttons appearing in the right hand pane of the tree browser.
 *
 * @param string $mode
 *   One of the BROWSER_MODE_* constants
 * @param object $item
 *   The MM tree record for the item being displayed
 * @param array $actions
 *   Array of named buttons. The order and #weight dictate the order of the
 *   rendered buttons in the final HTML.
 * @param array $dialogs
 *   Array of modal dialog settings.
 */
function hook_mm_browser_buttons_alter($mode, $item, &$actions, &$dialogs) {
}

/**
 * Provide a list of column headers for group management
 *
 * @return array
 *   Returns the headers for the mm_group editing datatable.
 */
function hook_mm_large_group_header() {
}

/**
 * Returns users that appear on the group editing pages
 *
 * @param int $mmtid
 *   The group id for the group that is being edited
 * @param array $element
 *   Used to identify the datatable for redrawing
 * @return array
 *   User data that can be parsed by Datatables. The number of rows should match
 *   the headers defined by hook_mm_large_group_header().
 */
function hook_mm_large_group_get_users($mmtid, $element) {
}

/**
 * Alter the MM tree parameters resulting from editing a page's settings
 *
 * @param bool $add
 *   TRUE if the entry is new
 * @param int $mmtid
 *   Tree ID of the new entry's parent ($add=TRUE), or the ID of the entry to
 *   replace
 * @param array $parameters
 *   @see mm_content_insert_or_update()
 */
function hook_mm_content_edit_submit_alter($add, $mmtid, &$parameters) {
}

/**
 * Receive notifications when a node or tree entry is created/updated/deleted.
 *
 * @param string $type
 *   A string representing the type of change that occurred:
 *   - 'clear_cascaded':
 *     All cascaded settings have been cleared for the tree entries.
 *   - 'clear_flags':
 *     All flags have been cleared for the tree entries.
 *   - 'delete_node':
 *     The nodes with $nids, described by $data, have been permanently deleted.
 *   - 'delete_page':
 *     The tree entries with $mmtids have been permanently deleted.
 *   - 'insert_cascaded':
 *     One or more cascaded settings were added to the tree entries.
 *   - 'insert_flags':
 *     One or more flags were added to the tree entries.
 *   - 'insert_node':
 *     A node has been created. $data describes it.
 *   - 'insert_page':
 *     A tree entry has been created. $data describes it.
 *   - 'move_node':
 *     The nodes described by $nids have moved from $data['old_mmtid'] to
 *     $data['new_mmtid'].
 *   - 'move_page':
 *     The tree entries at $mmtids have moved from $data['old_parent'] to
 *     $data['new_parent'].
 *   - 'update_node':
 *     A node has been updated. $data describes the entire new state.
 *   - 'update_node_perms':
 *     The nodes' permissions have been modified to match $data.
 *   - 'update_page':
 *     A tree entry has been updated. $data describes the entire new state.
 *   - 'update_page_quick':
 *     A portion of the tree entry's settings have changed, according to $data.
 * @param array $nids
 *   An array of IDs that were affected
 * @param array $mmtids
 *   An array of tree IDs that were affected
 * @param mixed $data
 *   A $type-specific description of the change
 */
function hook_mm_notify_change($type, $nids, $mmtids, $data) {
}

/**
 * Add fields to the data altered by the URL search and replace feature,
 * mm_admin_fix_node_urls_batch(), the user-facing page for which is at
 * admin/mm/fix-nodes.
 *
 * URL replacement follows these steps:
 *
 * 1. A SQL query is built, which retrieves the data in various fields that
 *    might contain URLs. By default, every node's body and summary are
 *    searched.
 *
 * 2. Each field is evaluated using a regular expression match, to see if the
 *    given node is likely to have to be altered. This step produces the
 *    statistic about how many nodes contain matches, and the preview for what
 *    is to be changed.
 *
 * 3. If the user has chosen to write changes, an attempt is made to modify each
 *    field. This process starts with a node object that is loaded using
 *    node_load(), not the version read directly from the database in the first
 *    step. If any field has actually changed, the entire node gets re-saved. A
 *    revision is created, so that changes can be reverted if a problem is
 *    discovered.
 *
 * @return array
 *   An array containing one or more sub-arrays, keyed on the name of the node
 *   object field that is affected by the search and replace. Each sub-array
 *   must contain these elements:
 *   - 'table':
 *     The name of the SQL table to join with
 *   - 'join on':
 *     The JOIN ON clause. Refer to the table whose name is in the 'table'
 *     element with the %alias token. The base node table can be referenced with
 *     the name 'node'.
 *   - 'table field':
 *     The name of the field to be tested within 'table'
 *   - 'get':
 *     A function to get the value of the field from within the node object. It
 *     accepts these parameters:
 *     - $node:
 *       The node object being modified
 *     - $field_name:
 *       The name of the field (taken from the key of the info() array.)
 *     - $language:
 *       The human language ID of the node
 *
 *     The function must return the value of the node field being referenced.
 *
 *     Note that the value for this field must be the name of a global function.
 *     Anonymous functions (closures) will not work, due to a limitation in
 *     Drupal. If this value is empty, mm_admin_fix_node_urls_default_get() will
 *     be used.
 *   - 'set':
 *     A function to set the value of the field in the node object. It accepts
 *     these parameters:
 *     - $value:
 *       The value to be set in $node
 *     - $node:
 *       The node object being modified
 *     - $field_name:
 *       The name of the field (taken from the key of the info() array.)
 *     - $language:
 *       The human language ID of the node
 *
 *     Note that the value for this field must be the name of a global function.
 *     Anonymous functions (closures) will not work, due to a limitation in
 *     Drupal. If this value is empty, mm_admin_fix_node_urls_default_set() will
 *     be used.
 */
function hook_mm_fix_node_urls_info() {
  return array(
    // We're joining the mm_node_redir table, so that the "url" field can be
    // searched. In a loaded node object, this is the "redir_url" field.
    'redir_url' => array(
      'table' => 'mm_node_redir',
      // Here, %alias gets replaced with the table alias assigned by the code.
      'join on' => '%alias.vid = node.vid',
      'table field' => 'url',
      'get' => '_mm_node_redir_mm_fix_node_urls_get',
      'set' => '_mm_node_redir_mm_fix_node_urls_set',
    ),
  );
}

/**
 * @} End of "addtogroup mm_hooks".
 */
