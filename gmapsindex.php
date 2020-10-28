<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       gmaps/gmapsindex.php
 *    \ingroup    gmaps
 *    \brief      Home page of gmaps top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/gmaps/class/gmaps_import.class.php');

// Load translation files required by the page
$langs->loadLangs(array("gmaps@gmaps"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "position_name";

$upload_dir = $conf->gmaps->multidir_output[$conf->entity];

// Security check
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

if ($action=='editfile') {
	$action='';
	$import = new Gmaps_import($db);
	$result = $import->importFile($upload_dir.'/'.GETPOST("urlfile"), $user);
	if ($result < 0) {
		setEventMessages($import->error,$import->errors,'errors');
	} else {
		setEventMessage('ImportOK');
	}
	header("Location: ".$_SERVER['PHP_SELF']);
	exit;
}

// Action submit/delete file/link
include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("GmapsArea"));

print load_fiche_titre($langs->trans("GmapsArea"), '', 'gmaps.png@gmaps');

if ($action == 'delete')
{
	$langs->load("companies"); // Need for string DeleteFile+ConfirmDeleteFiles
	print $form->formconfirm(
		$_SERVER["PHP_SELF"].'?&urlfile='.urlencode(GETPOST("urlfile")),
		$langs->trans('DeleteFile'),
		$langs->trans('ConfirmDeleteFile'),
		'confirm_deletefile',
		'',
		0,
		1
	);
}

print '<div class="fichecenter">';


$formfile->form_attach_new_file(
	$_SERVER["PHP_SELF"].(empty($moreparam) ? '' : $moreparam),
	'',
	0,
	0,
	1,
	$conf->browser->layout == 'phone' ? 40 : 60,
	null,
	'',
	1,
	0,
	0
);


$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);

// List of document
$formfile->list_of_documents(
	$filearray,
	null,
	'gmaps',
	'',
	0,
	'', // relative path with no file. For example "0/1"
	1,
	0,
	'',
	0,
	'',
	'',
	0,
	1,
	$upload_dir,
	$sortfield,
	$sortorder,
	0
);


print '</div>';


// End of page
llxFooter();
$db->close();
