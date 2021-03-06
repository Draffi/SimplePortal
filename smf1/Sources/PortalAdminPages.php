<?php
/**********************************************************************************
* PortalAdminPages.php                                                            *
***********************************************************************************
* SimplePortal                                                                    *
* SMF Modification Project Founded by [SiNaN] (sinan@simplemachines.org)          *
* =============================================================================== *
* Software Version:           SimplePortal 2.3.5                                  *
* Software by:                SimplePortal Team (http://www.simpleportal.net)     *
* Copyright 2008-2009 by:     SimplePortal Team (http://www.simpleportal.net)     *
* Support, News, Updates at:  http://www.simpleportal.net                         *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	void sportal_admin_pages_main()
		// !!!

	void sportal_admin_page_list()
		// !!!

	void sportal_admin_page_edit()
		// !!!

	void sportal_admin_page_delete()
		// !!!

	void sportal_admin_page_status()
		// !!!
*/

function sportal_admin_pages_main()
{
	global $context, $txt, $scripturl, $sourcedir;

	if (!allowedTo('sp_admin'))
		isAllowedTo('sp_manage_pages');

	loadTemplate('PortalAdminPages');

	$subActions = array(
		'list' => 'sportal_admin_page_list',
		'add' => 'sportal_admin_page_edit',
		'edit' => 'sportal_admin_page_edit',
		'delete' => 'sportal_admin_page_delete',
		'status' => 'sportal_admin_page_status',
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	$context['sub_action'] = $_REQUEST['sa'];

	$context['admin_tabs'] = array(
		'title' => $txt['sp_admin_pages_title'],
		'help' => 'sp_PagesArea',
		'description' => $txt['sp_admin_pages_desc'],
		'tabs' => array(
			'list' => array(
				'title' => $txt['sp_admin_pages_list'],
				'description' => $txt['sp_admin_pages_desc'],
				'href' => $scripturl . '?action=manageportal;area=portalpages;sa=list',
				'is_selected' => $_REQUEST['sa'] == 'list' || $_REQUEST['sa'] == 'edit',
			),
			'add' => array(
				'title' => $txt['sp_admin_pages_add'],
				'description' => $txt['sp_admin_pages_desc'],
				'href' => $scripturl . '?action=manageportal;area=portalpages;sa=add',
				'is_selected' => $_REQUEST['sa'] == 'add',
			),
		),
	);

	$subActions[$_REQUEST['sa']]();
}

function sportal_admin_page_list()
{
	global $txt, $db_prefix, $context, $scripturl;

	if (!empty($_POST['remove_pages']) && !empty($_POST['remove']) && is_array($_POST['remove']))
	{
		checkSession();

		foreach ($_POST['remove'] as $index => $page_id)
			$_POST['remove'][(int) $index] = (int) $page_id;

		db_query("
			DELETE FROM {$db_prefix}sp_pages
			WHERE ID_PAGE IN (" . implode(', ', $_POST['remove']) . ")", __FILE__, __LINE__);
	}

	$sort_methods = array(
		'title' =>  array(
			'down' => 'title ASC',
			'up' => 'title DESC'
		),
		'namespace' =>  array(
			'down' => 'namespace ASC',
			'up' => 'namespace DESC'
		),
		'type' => array(
			'down' => 'type ASC',
			'up' => 'type DESC'
		),
		'views' => array(
			'down' => 'views ASC',
			'up' => 'views DESC'
		),
		'status' => array(
			'down' => 'state ASC',
			'up' => 'state DESC'
		),
	);

	$context['columns'] = array(
		'title' => array(
			'width' => '45%',
			'label' => $txt['sp_admin_pages_col_title'],
			'sortable' => true
		),
		'namespace' => array(
			'width' => '25%',
			'label' => $txt['sp_admin_pages_col_namespace'],
			'sortable' => true
		),
		'type' => array(
			'width' => '8%',
			'label' => $txt['sp_admin_pages_col_type'],
			'sortable' => true
		),
		'views' => array(
			'width' => '6%',
			'label' => $txt['sp_admin_pages_col_views'],
			'sortable' => true
		),
		'status' => array(
			'width' => '6%',
			'label' => $txt['sp_admin_pages_col_status'],
			'sortable' => true
		),
		'actions' => array(
			'width' => '10%',
			'label' => $txt['sp_admin_pages_col_actions'],
			'sortable' => false
		),
	);

	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		$_REQUEST['sort'] = 'title';

	foreach ($context['columns'] as $col => $dummy)
	{
		$context['columns'][$col]['selected'] = $col == $_REQUEST['sort'];
		$context['columns'][$col]['href'] = $scripturl . '?action=manageportal;area=portalpages;sa=list;sort=' . $col;

		if (!isset($_REQUEST['desc']) && $col == $_REQUEST['sort'])
			$context['columns'][$col]['href'] .= ';desc';

		$context['columns'][$col]['link'] = '<a href="' . $context['columns'][$col]['href'] . '">' . $context['columns'][$col]['label'] . '</a>';
	}

	$context['sort_by'] = $_REQUEST['sort'];
	$context['sort_direction'] = !isset($_REQUEST['desc']) ? 'down' : 'up';

	$request = db_query("
		SELECT COUNT(*)
		FROM {$db_prefix}sp_pages", __FILE__, __LINE__);
	list ($total_pages) =  mysql_fetch_row($request);
	mysql_free_result($request);

	$context['page_index'] = constructPageIndex($scripturl . '?action=manageportal;area=portalpages;sa=list;sort=' . $_REQUEST['sort'] . (isset($_REQUEST['desc']) ? ';desc' : ''), $_REQUEST['start'], $total_pages, 20);
	$context['start'] = $_REQUEST['start'];

	$request = db_query("
		SELECT ID_PAGE, namespace, title, type, views, status
		FROM {$db_prefix}sp_pages
		ORDER BY " . $sort_methods[$_REQUEST['sort']][$context['sort_direction']] . "
		LIMIT $context[start], 20", __FILE__, __LINE__);
	$context['pages'] = array();
	while ($row = mysql_fetch_assoc($request))
	{
		$context['pages'][$row['ID_PAGE']] = array(
			'id' => $row['ID_PAGE'],
			'page_id' => $row['namespace'],
			'title' => $row['title'],
			'href' => $scripturl . '?page=' . $row['namespace'],
			'link' => '<a href="' . $scripturl . '?page=' . $row['namespace'] . '">' . $row['title'] . '</a>',
			'type' => $row['type'],
			'type_text' => $txt['sp_pages_type_'. $row['type']],
			'views' => $row['views'],
			'status' => $row['status'],
			'status_image' => '<a href="' . $scripturl . '?action=manageportal;area=portalpages;sa=status;page_id=' . $row['ID_PAGE'] . ';sesc=' . $context['session_id'] . '">' . sp_embed_image(empty($row['status']) ? 'deactive' : 'active', $txt['sp_admin_pages_' . (empty($row['status']) ? 'de' : '') . 'activate']) . '</a>',
			'actions' => array(
				'edit' => '<a href="' . $scripturl . '?action=manageportal;area=portalpages;sa=edit;page_id=' . $row['ID_PAGE'] . ';sesc=' . $context['session_id'] . '">' . sp_embed_image('modify') . '</a>',
				'delete' => '<a href="' . $scripturl . '?action=manageportal;area=portalpages;sa=delete;page_id=' . $row['ID_PAGE'] . ';sesc=' . $context['session_id'] . '" onclick="return confirm(\'', $txt['sp_admin_pages_delete_confirm'], '\');">' . sp_embed_image('delete') . '</a>',
			)
		);
	}
	mysql_free_result($request);

	$context['sub_template'] = 'pages_list';
	$context['page_title'] = $txt['sp_admin_pages_list'];
}

function sportal_admin_page_edit()
{
	global $txt, $context, $modSettings, $db_prefix, $func, $sourcedir;

	require_once($sourcedir . '/Subs-Post.php');

	$context['SPortal']['is_new'] = empty($_REQUEST['page_id']);

	$context['sides'] = array(
		5 => $txt['sp-positionHeader'],
		1 => $txt['sp-positionLeft'],
		2 => $txt['sp-positionTop'],
		3 => $txt['sp-positionBottom'],
		4 => $txt['sp-positionRight'],
		6 => $txt['sp-positionFooter'],
	);

	$blocks = getBlockInfo();
	$context['page_blocks'] = array();

	foreach ($blocks as $block)
	{
		$shown = false;
		$tests = array('all', 'allpages', 'sforum');
		if (!$context['SPortal']['is_new'])
			$tests[] = 'p' . ((int) $_REQUEST['page_id']);

		foreach (array('display', 'display_custom') as $field)
		{
			if (substr($block[$field], 0, 4) === '$php')
				continue 2;

			$block[$field] = explode(',', $block[$field]);

			if (!$context['SPortal']['is_new'] && in_array('-p' . ((int) $_REQUEST['page_id']), $block[$field]))
				continue;

			foreach ($tests as $test)
			{
				if (in_array($test, $block[$field]))
				{
					$shown = true;
					break;
				}
			}
		}

		$context['page_blocks'][$block['column']][] = array(
			'id' => $block['id'],
			'label' => $block['label'],
			'shown' => $shown,
		);
	}

	if (!empty($_POST['submit']))
	{
		checkSession();

		if (!isset($_POST['title']) || $func['htmltrim'](addslashes($func['htmlspecialchars'](stripslashes($_POST['title']), ENT_QUOTES))) === '')
			fatal_lang_error('sp_error_page_name_empty', false);

		if (!isset($_POST['namespace']) || $func['htmltrim'](addslashes($func['htmlspecialchars'](stripslashes($_POST['namespace']), ENT_QUOTES))) === '')
			fatal_lang_error('sp_error_page_namespace_empty', false);

		$request = db_query("
			SELECT ID_PAGE
			FROM {$db_prefix}sp_pages
			WHERE namespace = '" . addslashes($func['htmlspecialchars'](stripslashes($_POST['namespace']), ENT_QUOTES)) . "'
				AND ID_PAGE != " . (int) $_POST['page_id'] . "
			LIMIT 1", __FILE__, __LINE__);
		list ($has_duplicate) = mysql_fetch_row($request);
		mysql_free_result($request);

		if (!empty($has_duplicate))
			fatal_lang_error('sp_error_page_namespace_duplicate', false);

		if (preg_match('~[^A-Za-z0-9_]+~', $_POST['namespace']) != 0)
			fatal_lang_error('sp_error_page_namespace_invalid_chars', false);

		if (preg_replace('~[0-9]+~', '', $_POST['namespace']) === '')
			fatal_lang_error('sp_error_page_namespace_numeric', false);

		if ($_POST['type'] == 'php' && !empty($_POST['content']) && empty($modSettings['sp_disable_php_validation']))
		{
			$error = sp_validate_php(stripslashes($_POST['content']));

			if ($error)
				fatal_lang_error('error_sp_php_' . $error, false);
		}

		$permission_set = 0;
		$groups_allowed = $groups_denied = '';

		if (!empty($_POST['permission_set']))
			$permission_set = (int) $_POST['permission_set'];
		elseif (!empty($_POST['membergroups']) && is_array($_POST['membergroups']))
		{
			$groups_allowed = $groups_denied = array();

			foreach ($_POST['membergroups'] as $id => $value)
			{
				if ($value == 1)
					$groups_allowed[] = (int) $id;
				elseif ($value == -1)
					$groups_denied[] = (int) $id;
			}

			$groups_allowed = implode(',', $groups_allowed);
			$groups_denied = implode(',', $groups_denied);
		}

		if (!empty($_POST['blocks']) && is_array($_POST['blocks']))
		{
			foreach ($_POST['blocks'] as $id => $block)
				$_POST['blocks'][$id] = (int) $block;
		}
		else
			$_POST['blocks'] = array();

		$fields = array(
			'namespace' => 'string',
			'title' => 'string',
			'body' => 'string',
			'type' => 'string',
			'permission_set' => 'int',
			'groups_allowed' => 'string',
			'groups_denied' => 'string',
			'style' => 'string',
			'status' => 'int',
		);

		$page_info = array(
			'id' => (int) $_POST['page_id'],
			'namespace' => addslashes($func['htmlspecialchars'](stripslashes($_POST['namespace']), ENT_QUOTES)),
			'title' => addslashes($func['htmlspecialchars'](stripslashes($_POST['title']), ENT_QUOTES)),
			'body' => addslashes($func['htmlspecialchars'](stripslashes($_POST['content']), ENT_QUOTES)),
			'type' => $_POST['type'],
			'permission_set' => $permission_set,
			'groups_allowed' => $groups_allowed,
			'groups_denied' => $groups_denied,
			'style' => sportal_parse_style('implode'),
			'status' => !empty($_POST['status']) ? 1 : 0,
		);

		if ($page_info['type'] == 'bbc')
			preparsecode($page_info['body']);

		if ($context['SPortal']['is_new'])
		{
			unset($page_info['id']);

			$insert = array();
			foreach ($page_info as $key => $info)
				$insert[$key] = "'" . $info . "'";

			db_query("
				INSERT INTO {$db_prefix}sp_pages
					(" . implode(', ', array_keys($insert)) . ")
				VALUES
					(" . implode(', ', $insert) . ")", __FILE__, __LINE__);
			$page_info['id'] = db_insert_id();
		}
		else
		{
			$update_fields = array();
			foreach ($fields as $name => $type)
				$update_fields[] = $name . ' = \'' . $page_info[$name] . '\'';

			db_query("
				UPDATE {$db_prefix}sp_pages
				SET " . implode(', ', $update_fields) . "
				WHERE ID_PAGE = $page_info[id]
				LIMIT 1", __FILE__, __LINE__);
		}

		$to_show = array();
		$not_to_show = array();
		$changes = array();

		foreach ($context['page_blocks'] as $page_blocks)
		{
			foreach ($page_blocks as $block)
			{
				if ($block['shown'] && !in_array($block['id'], $_POST['blocks']))
					$not_to_show[] = $block['id'];
				elseif (!$block['shown'] && in_array($block['id'], $_POST['blocks']))
					$to_show[] = $block['id'];
			}
		}

		foreach ($to_show as $id)
		{
			if ((empty($blocks[$id]['display']) && empty($blocks[$id]['display_custom'])) || $blocks[$id]['display'] == 'sportal')
			{
				$changes[$id] = array(
					'display' => 'portal,p' . $page_info['id'],
					'display_custom' => '',
				);
			}
			elseif (in_array($blocks[$id]['display'], array('allaction', 'allboard')))
			{
				$changes[$id] = array(
					'display' => '',
					'display_custom' => $blocks[$id]['display'] . ',p' . $page_info['id'],
				);
			}
			elseif (in_array('-p' . $page_info['id'], explode(',', $blocks[$id]['display_custom'])))
			{
				$changes[$id] = array(
					'display' => $blocks[$id]['display'],
					'display_custom' => implode(',', array_diff(explode(',', $blocks[$id]['display_custom']), array('-p' . $page_info['id']))),
				);
			}
			elseif (empty($blocks[$id]['display_custom']))
			{
				$changes[$id] = array(
					'display' => implode(',', array_merge(explode(',', $blocks[$id]['display']), array('p' . $page_info['id']))),
					'display_custom' => '',
				);
			}
			else
			{
				$changes[$id] = array(
					'display' => $blocks[$id]['display'],
					'display_custom' => implode(',', array_merge(explode(',', $blocks[$id]['display_custom']), array('p' . $page_info['id']))),
				);
			}
		}

		foreach ($not_to_show as $id)
		{
			if (count(array_intersect(array($blocks[$id]['display'], $blocks[$id]['display_custom']), array('sforum', 'allpages', 'all'))) > 0)
			{
				$changes[$id] = array(
					'display' => '',
					'display_custom' => $blocks[$id]['display'] . $blocks[$id]['display_custom'] . ',-p' . $page_info['id'],
				);
			}
			elseif (empty($blocks[$id]['display_custom']))
			{
				$changes[$id] = array(
					'display' => implode(',', array_diff(explode(',', $blocks[$id]['display']), array('p' . $page_info['id']))),
					'display_custom' => '',
				);
			}
			else
			{
				$changes[$id] = array(
					'display' => implode(',', array_diff(explode(',', $blocks[$id]['display']), array('p' . $page_info['id']))),
					'display_custom' => implode(',', array_diff(explode(',', $blocks[$id]['display_custom']), array('p' . $page_info['id']))),
				);
			}
		}

		foreach ($changes as $id => $data)
		{
			db_query("
				UPDATE {$db_prefix}sp_blocks
				SET
					display = '$data[display]',
					display_custom = '$data[display_custom]'
				WHERE ID_BLOCK = $id
				LIMIT 1", __FILE__, __LINE__);
		}

		redirectexit('action=manageportal;area=portalpages');
	}

	if (!empty($_POST['preview']))
	{
		$permission_set = 0;
		$groups_allowed = $groups_denied = array();

		if (!empty($_POST['permission_set']))
			$permission_set = (int) $_POST['permission_set'];
		elseif (!empty($_POST['membergroups']) && is_array($_POST['membergroups']))
		{
			foreach ($_POST['membergroups'] as $id => $value)
			{
				if ($value == 1)
					$groups_allowed[] = (int) $id;
				elseif ($value == -1)
					$groups_denied[] = (int) $id;
			}
		}

		$context['SPortal']['page'] = array(
			'id' => $_POST['page_id'],
			'page_id' => $_POST['namespace'],
			'title' => addslashes($func['htmlspecialchars'](stripslashes($_POST['title']), ENT_QUOTES)),
			'body' => addslashes($func['htmlspecialchars'](stripslashes($_POST['content']), ENT_QUOTES)),
			'type' => $_POST['type'],
			'permission_set' => $permission_set,
			'groups_allowed' => $groups_allowed,
			'groups_denied' => $groups_denied,
			'style' => sportal_parse_style('implode'),
			'status' => !empty($_POST['status']),
		);

		if ($context['SPortal']['page']['type'] == 'bbc')
			preparsecode($context['SPortal']['page']['body']);

		loadTemplate('PortalPages');
		$context['SPortal']['preview'] = true;
	}
	elseif ($context['SPortal']['is_new'])
	{
		$context['SPortal']['page'] = array(
			'id' => 0,
			'page_id' => 'page' . mt_rand(1, 5000),
			'title' => $txt['sp_pages_default_title'],
			'body' => '',
			'type' => 'bbc',
			'permission_set' => 3,
			'groups_allowed' => array(),
			'groups_denied' => array(),
			'style' => '',
			'status' => 1,
		);
	}
	else
	{
		$_REQUEST['page_id'] = (int) $_REQUEST['page_id'];
		$context['SPortal']['page'] = sportal_get_pages($_REQUEST['page_id']);
	}

	if ($context['SPortal']['page']['type'] == 'bbc')
		$context['SPortal']['page']['body'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), un_preparsecode($context['SPortal']['page']['body']));

	$context['SPortal']['page']['groups'] = sp_load_membergroups();
	$context['SPortal']['page']['style'] = sportal_parse_style('explode', $context['SPortal']['page']['style'], !empty($context['SPortal']['preview']));

	$context['post_box_name'] = 'content';
	$context['post_form'] = 'editpage';

	$context['page_title'] = $context['SPortal']['is_new'] ? $txt['sp_admin_pages_add'] : $txt['sp_admin_pages_edit'];
	$context['sub_template'] = 'pages_edit';
}

function sportal_admin_page_delete()
{
	global $db_prefix;

	checkSession('get');

	$page_id = !empty($_REQUEST['page_id']) ? (int) $_REQUEST['page_id'] : 0;

	db_query("
		DELETE FROM {$db_prefix}sp_pages
		WHERE ID_PAGE = $page_id
		LIMIT 1", __FILE__, __LINE__);

	redirectexit('action=manageportal;area=portalpages');
}

function sportal_admin_page_status()
{
	global $db_prefix;

	checkSession('get');

	$page_id = !empty($_REQUEST['page_id']) ? (int) $_REQUEST['page_id'] : 0;

	db_query("
		UPDATE {$db_prefix}sp_pages
		SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END
		WHERE ID_PAGE = $page_id
		LIMIT 1", __FILE__, __LINE__);

	redirectexit('action=manageportal;area=portalpages');
}

?>