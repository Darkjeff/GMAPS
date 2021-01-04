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
 *    \file       gmaps/gmaps_prepare_dnf.php
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once __DIR__ . '/class/gmaps_activity.class.php';
$object = new Gmaps_activity($db);
$form = new Form($db);

// Load translation files required by the page
$langs->loadLangs(array("gmaps@gmaps"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

$now = dol_now();

if ((empty($date_start) && empty($date_end))) // We define date_start and date_end, only if we did not submit the form
{
	$date_start = dol_get_first_day(dol_print_date($now, '%Y'), dol_print_date($now, '%m'), false);
	$date_end = dol_get_last_day(dol_print_date($now, '%Y'), dol_print_date($now, '%m'), false);
}

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
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
$error = 0;

/*
 * View
 */
$title = $langs->trans("GmapsPrepareNDF");
llxHeader("", $title);

$head = array();
$h = 0;
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $title;
$head[$h][2] = 'gmapspreparendf';

print load_fiche_titre($langs->trans("GmapsAreaKM"), '', 'title_accountancy');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formAction" id="formAction">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" id="action" value="">';

print dol_get_fiche_head($head, 'gmapspreparendf');

print '<table width="100%" class="border">';

// Ligne de titre
print '<tr>';
print '<td width="110">' . $langs->trans("Name") . '</td>';
print '<td colspan="3">';
print $title;
print '</td>';
print '</tr>';

// Ligne de la periode d'analyse du rapport
print '<tr>';
print '<td>' . $langs->trans("ReportPeriod") . '</td>';
print '<td>';
print $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input type="button" class="button" name="refresh" value="' . $langs->trans("Refresh") . '">';
if (!empty($user->rights->expensereport->creer)) print '<input type="button" class="button" name="createndf" value="' . $langs->trans("CreateNDF") . '">';
print '</div>';

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
		// Add code to auto check the box when we select an Thridparty
		$(document).ready(function() {
			$(".button").click(function() {
			    console.log($(this)[0].name);
				$("#action").val($(this)[0].name);
				$("#formAction").submit();
			});
		});
</script>';

print '</form>';

$sql = "SELECT ";
$sql .= 'ga.fk_soc AS fk_soc , s.nom as name, ga.duration_start, ga.distance';
$sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as ga";
$sql .= "  INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = ga.fk_soc";
$sql .= " WHERE ga.duration_start >= '" . $db->idate($date_start) . "'";
$sql .= "  AND ga.duration_start <= '" . $db->idate($date_end) . "'";
//$sql .= " AND ga.entity IN (".getEntity('gmaps_activity', 0).")"; // We don't share object for accountancy
$sql .= " AND ga.status =" . Gmaps_activity::STATUS_VALIDATED;
$sql .= " ORDER BY s.nom, ga.duration_start";

if ($action == 'refresh' && !empty($date_start) && !empty($date_end)) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td width="200">' . $langs->trans("Thirdparty") . '</td>';
	print '<td width="200">' . $langs->trans("Date") . '</td>';
	print '<td width="200">' . $langs->trans("Distance") . '</td>';
	print '</tr>';

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		while ($obj = $db->fetch_object($resql)) {
			print '<tr class="oddeven">';
			print '<td>';
			print $obj->name;
			print '</td>';
			print '<td>';
			print dol_print_date($obj->duration_start);
			print '</td>';
			print '<td>';
			print round($obj->distance / 1000);
			print '</td>';
			print '</tr>';
		}
		$db->free($resql);
	} else {
		print $db->lasterror(); // Show last sql error
	}
	print "</table>\n";
	print '</div>';
}

/*
 * Actions
 */

if ($action == 'createndf' && !empty($user->rights->expensereport->creer) && !empty($date_start) && !empty($date_end)) {

	$langs->load('trips');

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$ndfCreated = array();
		if ($num > 0) {
			// New NDF
			$db->begin();

			dol_include_once('/expensereport/class/expensereport.class.php');
			$ndf = new ExpenseReport($db);
			$ndf->date_debut = $date_start;
			$ndf->date_fin = $date_end;
			$ndf->fk_user_author = $user->id;
			$ndf->status = ExpenseReport::STATUS_DRAFT;

			//find validator
			$ndf_static = new ExpenseReport($db);
			$include_users = $ndf_static->fetch_users_approver_expensereport();
			if (!empty($include_users)) {
				$defaultselectuser = (empty($user->fk_user_expense_validator) ? $user->fk_user : $user->fk_user_expense_validator); // Will work only if supervisor has permission to approve so is inside include_users
				if (!empty($conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR)) $defaultselectuser = $conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR; // Can force default approver
				if (!empty($defaultselectuser)) $ndf->fk_user_validator = $defaultselectuser;
			}

			if (empty($conf->global->EXPENSEREPORT_ALLOW_OVERLAPPING_PERIODS) && $ndf->periode_existe($user->id, $ndf->date_debut, $ndf->date_fin)) {
				$error++;
				setEventMessages($langs->trans("ErrorDoubleDeclaration"), null, 'errors');
				$action = 'refresh';
			}
			if (empty($error)) {
				$id = $ndf->create($user);
				if ($id <= 0) {
					$error++;
				} else {
					setEventMessages($ndf->error, $ndf->errors, 'errors');
				}
			}
			while ($obj = $db->fetch_object($resql)) {
				if (!$error) {
					$ret = $ndf->fetch($ndf->id); // Reload to get new records
					$vatrate = "0.000";

					$value_unit_ht = $conf->global->GMAPS_COEFF1;

					$fk_c_exp_tax_cat = null;
					$fk_project = null;
					$fk_ecm_files = null;
					$comments = $obj->name;
					$qty = round($obj->distance / 1000);
					if (empty($qty)) $qty = 1;

					$fk_c_type_fees = dol_getIdFromCode($db, $conf->global->GMAPS_TYPETRANSPORTNDF, 'c_type_fees', 'code', 'id');
					$date = $obj->duration_start;

					$type = 0; // TODO What if service ? We should take the type product/service from the type of expense report llx_c_type_fees

					// Insert line
					$result = $ndf->addline($qty, $value_unit_ht, $fk_c_type_fees, $vatrate, $date, $comments, $fk_project, $fk_c_exp_tax_cat, $type, $fk_ecm_files);
					if ($result > 0) {
						$ret = $ndf->fetch($ndf->id); // Reload to get new records

						if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
							$ndf->generateDocument($ndf->model_pdf, $langs);
						}

						unset($qty);
						unset($value_unit_ht);
						unset($vatrate);
						unset($comments);
						unset($fk_c_type_fees);
						unset($fk_project);

						unset($date);

					} else {
						setEventMessages($ndf->error, $ndf->errors, 'errors');
					}
				}

			}

			if (empty($error)) {
				$db->commit();
				setEventMessages('', $ndf->getNomUrl());
			} else {
				setEventMessages($ndf->error, $ndf->errors, 'errors');
				$db->rollback();
			}
		}
	} else {
		print $db->lasterror(); // Show last sql error
	}
}

// End of page
llxFooter();
$db->close();
