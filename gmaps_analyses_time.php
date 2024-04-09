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
 *    \file       gmaps/gmaps_analyses_time.php
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once __DIR__.'/class/gmapsactivity.class.php';
$object = new GmapsActivity($db);

// Load translation files required by the page
$langs->loadLangs(array("companies","gmaps@gmaps"));

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


$month_start = ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
if (GETPOST("year", 'int')) $year_start = GETPOST("year", 'int');
else {
	$year_start = dol_print_date(dol_now(), '%Y');
	if (dol_print_date(dol_now(), '%m') < $month_start) $year_start--; // If current month is lower that starting fiscal month, we start last year
}
$year_end = $year_start + 1;
$month_end = $month_start - 1;
if ($month_end < 1)
{
	$month_end = 12;
	$year_end--;
}
$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
$search_date_end = dol_get_last_day($year_end, $month_end);
$year_current = $year_start;


/*
 * Actions
 */


/*
 * View
 */

llxHeader("", $langs->trans("GmapsAreaTemps"));

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current - 1).'">'.img_previous().'</a>';
$textnextyear = '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current + 1).'">'.img_next().'</a>';

print load_fiche_titre($langs->trans("GmapsAreaTemps")." ".$textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, '', 'title_accountancy');

print_barre_liste($langs->trans("GmapsAreaTemps"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td width="200">'.$langs->trans("ThirdParty").'</td>';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1) - 1;
	if ($j > 12) $j -= 12;
	print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad($j, 2, '0', STR_PAD_LEFT)).'</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

$sql = "SELECT ";
$sql .=$db->ifsql('ga.fk_soc IS NULL', "'tobind'", 'ga.fk_soc'). ' AS fk_soc , s.nom as name,';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1) - 1;
	if ($j > 12) $j -= 12;
	$sql .= "  SUM(".$db->ifsql('MONTH(ga.duration_start)='.$j, 'TIMESTAMPDIFF(MINUTE,ga.duration_start,ga.duration_end)', '0').") AS month".str_pad($j, 2, '0', STR_PAD_LEFT).",";
}
$sql .= "  SUM(TIMESTAMPDIFF(MINUTE,ga.duration_start,ga.duration_end)) as difftime";
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as ga";
$sql .= "  LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = ga.fk_soc";
$sql .= " WHERE ga.duration_start >= '".$db->idate($search_date_start)."'";
$sql .= "  AND ga.duration_start <= '".$db->idate($search_date_end)."'";
//$sql .= " AND ga.entity IN (".getEntity('gmapsActivity', 0).")"; // We don't share object for accountancy
$sql .= " AND ga.status =".GmapsActivity::STATUS_VALIDATED;
$sql .= " GROUP BY ga.fk_soc";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$total_known=array();
	$grand_total_known=0;
	while ($row = $db->fetch_row($resql)) {

		print '<tr class="oddeven"><td>';
		if ($row[0] == 'tobind')
		{
			print $langs->trans("Unknown");
		} else print $row[1];
		print '</td>';

		for ($i = 2; $i <= 13; $i++) {
			print '<td class="nowrap right">'.$row[$i].'</td>';
			if ($row[0] != 'tobind')
			{
				$total_known[$i]+=$row[$i];
			}
		}
		if ($row[0] != 'tobind')
		{
			$grand_total_known+=$row[14];
		}

		print '<td class="nowrap right"><b>'.$row[14].'</b></td>';
		print '</tr>';
	}
	if ($num>0) {
		print '<tr class="liste_total"><td class="left">Total</td>';
		for ($i = 2; $i <= 13; $i++) {
			print '<td class="nowrap right">'.$total_known[$i]	.'</td>';
		}
		print '<td class="nowrap right"><b>'.$grand_total_known.'</b></td>';
		print '</tr>';
	}
	$db->free($resql);
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';



// End of page
llxFooter();
$db->close();
