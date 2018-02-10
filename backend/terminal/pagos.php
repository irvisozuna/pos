<?php
$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
dol_include_once('/pos/backend/class/cash.class.php');
dol_include_once('/pos/backend/lib/cash.lib.php');
if ($conf->banque->enabled) require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

if ($conf->stock->enabled) 
{
	require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php');
	require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
}
global $langs;
$langs->load("pos@pos");
$langs->load('bills');

$action=GETPOST("action");

// Security check

$id=GETPOST('id','int');
$ref=GETPOST('ref','string');

if($action=='add'){
	$metodopago=GETPOST('mpago');
	$cuenta=GETPOST('cuenta');
	$sql="INSERT INTO llx_pos_terminal_mpagos (entity,fk_terminal,fk_modepay,fk_bankacount)
		  VALUES (".$conf->entity.",".$id.",".$metodopago.",".$cuenta.")";
	$rq=$db->query($sql);
	print "<script>window.location.href='pagos.php?id=".$id."'</script>";
}

if($action=='del'){
	$idp=GETPOST('idp');
	$sql="DELETE FROM llx_pos_terminal_mpagos WHERE rowid=".$idp." AND entity=".$conf->entity;
	$rq=$db->query($sql);
	print "<script>window.location.href='pagos.php?id=".$id."'</script>";
}

if ($user->socid) $socid=$user->socid;
//$result=restrictedArea($user,'pos',$id,'pos_cash','','','rowid');

$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('','',$helpurl);


$cash = new Cash($db);

$cash->fetch($id, $ref);

$head=cash_prepare_head($cash);
dol_fiche_head($head, 'pago', $langs->trans("Modos de pago"),0,'cashdesk');
print '<table class="border" width="100%">';
print "<tr><td width='30%'>";
print "Terminal: ";
print "</td>";
print "<td>";
print "<strong>".$cash->name."</strong>";
print "</td></tr>";
print "</table>";	

$sql="SELECT fk_modepaycash, fk_modepaybank
FROM ".MAIN_DB_PREFIX."pos_cash
WHERE rowid =".$id;
$rq=$db->query($sql);
$rs=$db->fetch_object($rq);
print "<br><br>";
print '<table class="border" width="100%">';
print "<tr class='liste_titre'><td colspan='4' align='center'>";
print "Agregar modos de pago";
print "</td></tr>";
$sql="SELECT id,libelle,code FROM ".MAIN_DB_PREFIX."c_paiement WHERE id>0 AND active>0 
		AND (id!=".$rs->fk_modepaycash." AND id!=".$rs->fk_modepaybank."
		AND id NOT IN (SELECT fk_modepay FROM ".MAIN_DB_PREFIX."pos_terminal_mpagos WHERE fk_terminal=".$id."))";
//print $sql;
$rq=$db->query($sql);
print "<form method='POST' action='pagos.php?id=".$id."'>";
print "<tr><td width='25%'>Modo de Pago:</td>";
print "<td width='25%'><select name='mpago'>";
while($rs=$db->fetch_object($rq)){
	print "<option value='".$rs->id."'>".$langs->trans("PaymentTypeShort".$rs->code)."</option>";
}
print "</select></td>";
print "<td width='25%'>Cuenta: </td>";
$sql="SELECT rowid, ref FROM ".MAIN_DB_PREFIX."bank_account WHERE entity=".$conf->entity;
$rq2=$db->query($sql);
print "<td width='25%'><select name='cuenta'>";
while($rs2=$db->fetch_object($rq2)){
	print "<option value='".$rs2->rowid."'>".$rs2->ref."</option>";
}
print "</select></td></tr>";
print "<tr><td colspan='4' align='center'>";
print "<input type='hidden' name='action' value='add'>";
print "<input type='submit' value='Agregar'>";
print "</form>";
print "</td></tr>";
print "</table>";

$sql="SELECT a.rowid as idpospag,code,ref
FROM ".MAIN_DB_PREFIX."pos_terminal_mpagos a, ".MAIN_DB_PREFIX."c_paiement b, ".MAIN_DB_PREFIX."bank_account c
WHERE fk_terminal=".$id." AND fk_modepay=b.id AND fk_bankacount=c.rowid";
$req=$db->query($sql);
$nur=$db->num_rows($req);
if($nur>0){
	print '<table class="border" width="100%">';
	print "<tr class='liste_titre'><td colspan='5' align='center'>";
	print "Modos de pago Agregados";
	print "</td></tr>";
	while($rsl=$db->fetch_object($req)){
		print "<tr><td width='25%'>Modo de Pago:</td>";
		print "<td width='25%'>".$langs->trans("PaymentTypeShort".$rsl->code)."</td>";
		print "<td width='25%'>Cuenta: </td>";
		print "<td width='20%'>".$rsl->ref."</td>";
		print "<td width='5%'><a href='pagos.php?id=".$id."&action=del&idp=".$rsl->idpospag."'>".img_delete()."</a></td>";
		print "</tr>";
	}
	print "</table>";
}


llxFooter();

$db->close();
?>