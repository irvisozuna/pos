<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       	htdocs/core/lib/report.lib.php
 *  \brief      	Set of functions for reporting
 */


/**
 *	Show header of a VAT report
 *
 *	@param	string				$nom            Name of report
 *	@param 	string				$variante       Link for alternate report
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	string				$description    Description
 *	@param 	timestamp|integer	$builddate      Date generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array				$moreparam		Array with list of params to add into form
 *	@param	string				$calcmode		Calculation mode
 *   @param  string              $varlink        Add a variable into the address of the page
 *	@return	void
 */
function report_header_mod($nom,$variante,$period,$periodlink,$description,$builddate,$exportlink='',$moreparam=array(),$calcmode='', $varlink='')
{
    global $langs;

    if (empty($hselected)) $hselected='report';

    print "\n\n<!-- debut cartouche rapport -->\n";

    if(! empty($varlink)) $varlink = '?'.$varlink;

    $h=0;
    $head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
    $head[$h][1] = $langs->trans("Report");
    $head[$h][2] = 'report';

    dol_fiche_head($head, 'report');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].$varlink.'">';

    print '<table width="100%" class="border">';

    // Ligne de titre
    print '<tr>';
    print '<td width="110">'.$langs->trans("ReportName").'</td>';
    if (! $variantexxx) print '<td colspan="3">';
    else print '<td>';
    print $nom;
    if ($variantexxx) print '</td><td colspan="2">'.$variantexxx;
    print '</td>';
    print '</tr>';
    foreach($moreparam as $key => $value)
    {

        print '<tr>';
        print '<td width="110">'.$langs->trans("ReportName").'</td>';
        print '<td>';

        print $value;
        print '</td>';
        print '</tr>';
    }
    // Calculation mode
    if ($calcmode)
    {
        print '<tr>';
        print '<td width="110">'.$langs->trans("CalculationMode").'</td>';
        if (! $variante) print '<td colspan="3">';
        else print '<td>';
        print $calcmode;
        if ($variante) print '</td><td colspan="2">'.$variante;
        print '</td>';
        print '</tr>';
    }

    // Ligne de la periode d'analyse du rapport
    print '<tr>';
    print '<td>'.$langs->trans("ReportPeriod").'</td>';
    if (! $periodlink) print '<td colspan="3">';
    else print '<td>';
    if ($period) print $period;
    if ($periodlink) print '</td><td colspan="2">'.$periodlink;
    print '</td>';
    print '</tr>';

    print
    // Ligne de description
    print '<tr>';
    print '<td>'.$langs->trans("ReportDescription").'</td>';
    print '<td colspan="3">'.$description.'</td>';
    print '</tr>';

    // Ligne d'export
    print '<tr>';
    print '<td>'.$langs->trans("GeneratedOn").'</td>';
    if (! $exportlink) print '<td colspan="3">';
    else print '<td>';
    print dol_print_date($builddate);
    if ($exportlink) print '</td><td>'.$langs->trans("Export").'</td><td>'.$exportlink;
    print '</td></tr>';

    print '</table>';

    print '<br><div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';
    print '</form>';

    dol_fiche_end();

    print "\n<!-- fin cartouche rapport -->\n\n";
}

