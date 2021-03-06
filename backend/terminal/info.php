<?php
/* Copyright (C) 2011 		Juanjo Menent <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       htdocs/pos/backend/terminal/info.php
 *      \ingroup    pos
 *		\brief      Page des informations d'une terminal
 *		\version    $Id: info.php,v 1.5 2011-08-16 15:36:15 jmenent Exp $
 */

if (false === (@include '../../../main.inc.php')) { // From htdocs directory
    require '../../../../main.inc.php'; // From "custom" directory
}
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
dol_include_once('/pos/backend/class/cash.class.php');
dol_include_once('/pos/backend/lib/cash.lib.php');

if (!$user->rights->pos->backend)	accessforbidden();

$langs->load("pos@pos");

// Security check
$socid=0;
$id = GETPOST('id');
if ($user->socid) $socid=$user->socid;
//$result=restrictedArea($user,'pos',$id,'');



/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('','',$helpurl);
if($conf->global->POS_HELP){
	dol_include_once('/pos/backend/class/utils.class.php');
}

$cash = new Cash($db);
$cash->fetch($id);
$cash->info($id);


$head = cash_prepare_head($cash);
dol_fiche_head($head, 'info', $langs->trans("Cash"), 0, 'cashdesk');


print '<table width="100%"><tr><td>';
dol_print_object_info($cash);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
?>