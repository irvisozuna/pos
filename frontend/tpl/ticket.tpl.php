<?php



if (false === (@include '../../../main.inc.php')) { // From htdocs directory
    require '../../../../main.inc.php'; // From "custom" directory
}
dol_include_once('/pos/backend/class/ticket.class.php');
dol_include_once('/pos/backend/class/cash.class.php');
dol_include_once('/pos/backend/class/place.class.php');
if(is_file(DOL_DOCUMENT_ROOT.'/pos/lib/numeros_a_letras.php')){ //from htdocs
    require_once(DOL_DOCUMENT_ROOT.'/pos/lib/numeros_a_letras.php');
} else {
    require_once(DOL_DOCUMENT_ROOT.'/custom/pos/lib/numeros_a_letras.php'); //from custom
}
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
global $langs;

$langs->load("main");
$langs->load("pos@pos");
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
?>
<html>
<head>
<title>Print ticket</title>

<style type="text/css">

	body {
		font-family: "Arial", Arial, serif;
		/*font-size: 0.5em;*/
		position: relative;
	}

	.entete {
/* 		position: relative; */
	}

		.adresse {
/* 			float: left; */
			font-size: 10px;
		}

		.date_heure {
			position: absolute;
			top: 0;
			right: 0;
			font-size: 10px;
		}

		.infos {
			position: relative;
		}


	.liste_articles {
		width: 100%;
		border-bottom: 1px solid #000;
		text-align: center;
		font-size:12px;
	}

		.liste_articles tr.titres th {
			border-bottom: 1px solid #000;
		}

		.liste_articles td.total {
			text-align: right;
		}

	.totaux {
		margin-top: 16px;
		width: 30%;
		float: right;
		font-size:10px;
		text-align: right;
	}

	.lien {
		position: absolute;
		top: 0;
		left: 0;
		display: none;
	}

	@media print {

		.lien {
			display: none;
		}

	}
	
	#fb-addr {
		width: 100%;
		float: left;
		line-height: 1.5em;
		margin-top:30px; left: 50px; font-size: 12px;
		border-top:1px dotted;
		border-bottom:1px dotted;
	}

</style>

</head>

<body>
	<?php
		
			// Variables
		
			$object=new Ticket($db);
			$result=$object->fetch($id,$ref);


			$userstatic=new User($db);
			$userstatic->fetch($object->user_close);


			?><br><br>
			<?php if(!empty($object->fk_place))
			{
				$place = new Place($db);
				$place->fetch($object->fk_place);
				print $langs->trans("Place").': '.$place->name."</p>";
			}
			
			// Recuperation et affichage de la date et de l'heure
			$now = dol_now();
			$label=$object->ref;
			$facture = new Facture($db);
			if($object->fk_facture){
				$facture->fetch($object->fk_facture);
				$label=$facture->ref;
			}
			
			
		?>
<div class="entete">
	<div class="logo">
	<?php 
	print '<p class="date_heure" align="right">'.$label."<br>".dol_print_date($object->date_closed,'dayhourtext').'</p><br>';
	//print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small).'">';
	
	?>
	</div>
    <?php
   // print_r($conf->global); die();
    ?>
	<div class="infos">
		<p class="adresse"><?php echo $mysoc->name; ?><br>
		<?php echo $mysoc->address; ?><br>
		<?php echo 'CP '.$mysoc->zip.' '.$mysoc->town.' , SONORA.'; ?><br>
		<?php print 'Teléfono '.$conf->global->TICK_TEL_SHOP; ?><br />
		<?php print 'R.F.C. '.$conf->global->MAIN_INFO_SIREN; ?><br /><br />
		
		<i><?php print $langs->trans("Vendor").': '.$userstatic->firstname." ".$userstatic->lastname; ?></i><br />

		<p>
		
	</div>
</div>

<table class="liste_articles">
        <?php
            if(!empty($object->remise_percent)) echo ('<tr class="titres" ><th colspan="4"><center>Dto. Gral. '.$object->remise_percent.'%<center></th></tr>');
        ?>
	<tr class="titres"><th>Descripción</th><th><?php print $langs->trans("Qty")."/".$langs->trans("Price"); ?></th><th><?php print $langs->trans("DiscountLineal"); ?></th><th>Importe</th></tr>

	<?php
		
		if ($result)
		{
			$object->getLinesArray();
			if (! empty($object->lines))
			{
				$subtotal=0;
				foreach ($object->lines as $line)
				{
					$totalline= $line->qty*$line->subprice;
					echo ('<tr class="articulos"><td align="left">'.$line->libelle.'</td><td align="right">'.$line->qty." * ".number_format($line->price,2).'</td><td align="right">'.$line->remise_percent.'%</td><td class="total">'.number_format($totalline,2).' '.$conf->global->MAIN_MONNAIE.'</td></tr>'."\n"); //$langs->trans(currency_name($conf->currency))
					if(isset($line->note_public)) echo('<tr class="articulos"><td align="left" style="font-size:smaller">'.$line->note.'</td><td colspan="3"></td>');
                                        $subtotal+=$totalline;
				}
			}
			else 
			{
				//echo ('<p>'. print $langs->trans("ErrNoArticles").'</p>'."\n");
				echo '<p>'.$langs->trans("ErrNoArticles").'</p>'."\n";
			}
					
		}
		

	?>
</table>

<table class="totaux" align="right">
	<?php
		/*if($object->remise_percent>0)
		{
			echo '<tr><th nowrap="nowrap">'.$langs->trans("Subtotal").'</th><td nowrap="nowrap">'.number_format($subtotal,2)."</td></tr>\n";
			echo '<tr><th nowrap="nowrap">'.$langs->trans("DiscountGlobal").'</th><td nowrap="nowrap">'.$object->remise_percent."%</td></tr>\n";
		}*/
		//echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalHT").'</th><td nowrap="nowrap">'.number_format($object->total_ht,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
		//echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalVAT").'</th><td nowrap="nowrap">'.number_format($object->total_tva,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
		if($object->total_localtax1!=0){
			echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalLT1ES").'</th><td nowrap="nowrap">'.number_format($object->total_localtax1,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
		}
		if($object->total_localtax2!=0){
			echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalLT2ES").'</th><td nowrap="nowrap">'.number_format($object->total_localtax2,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
		}
		echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalTTC").'</th><td nowrap="nowrap">'.number_format($object->total_ttc,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
		echo '<tr><td colspan="2">('.numtoletras($object->total_ttc).')</td></tr>';
		echo '<tr><td></td></tr>';

		$terminal = new Cash($db);
		$terminal->fetch($object->fk_cash);
		
		if ($object->type==0)
		{
			$sqlv="SELECT b.fk_paiement,a.amount,c.code,c.id
			FROM ".MAIN_DB_PREFIX."pos_paiement_ticket a, ".MAIN_DB_PREFIX."paiement b, ".MAIN_DB_PREFIX."c_paiement c
			WHERE fk_ticket=".$id." AND a.fk_paiement=b.rowid 
			AND b.fk_paiement=c.id";
			$rqsv=$db->query($sqlv);
			$nrsv=$db->num_rows($rqsv);
			if($nrsv>1){
				$pay2=0;
				while($rslv=$db->fetch_object($rqsv)){
					$pay2=$pay2+$rslv->amount;
					echo '<tr><th nowrap="nowrap">'.$langs->trans("Payment").'</th><td nowrap="nowrap">'.$terminal->select_Paymentname($rslv->id)."</td></tr>\n";
					echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerPay").'</th><td nowrap="nowrap">'.number_format($rslv->amount,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
				}
				$difpayment=$object->total_ttc - $pay2;
				if($difpayment<0)
					echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerRet").'</th><td nowrap="nowrap">'.number_format(abs($difpayment),2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
				else
					echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerDeb").'</th><td nowrap="nowrap">'.number_format(abs($difpayment),2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
			}else{
				echo '<tr><th nowrap="nowrap">'.$langs->trans("Payment").'</th><td nowrap="nowrap">'.$terminal->select_Paymentname($object->mode_reglement_id)."</td></tr>\n";
			/* if($object->mode_reglement_id==$terminal->fk_modepaycash)
			{ */
				echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerPay").'</th><td nowrap="nowrap">'.number_format($object->customer_pay,2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
				$difpayment=$object->total_ttc - $object->customer_pay;
				if($difpayment<0)
					echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerRet").'</th><td nowrap="nowrap">'.number_format(abs($difpayment),2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
				else
					echo '<tr><th nowrap="nowrap">'.$langs->trans("CustomerDeb").'</th><td nowrap="nowrap">'.number_format(abs($difpayment),2)." ".$conf->global->MAIN_MONNAIE."</td></tr>\n";
			//}
			}
		}
	?>
</table>


<script type="text/javascript">

	window.print();

</script>

<?php
if(!empty($object->note)){
echo '
<br><br><br><br><br>
<table class="liste_articles"><tr><td>&nbsp</td></tr></table>
<table class="liste_articles"><tr><td>Nota: '. $object->note. '</td></tr></table>´
';
}
?>


<a class="lien" href="#" onclick="javascript: window.close(); return(false);">Fermer cette fenetre</a>


<table id="fb-addr">
	<tr><td><i>**NO SE REALIZAN DEVOLUCIONES EN EFECTIVO.</b></i></td></tr>
	<tr><td><i>*LIMITE PARA HACER CAMBIOS 3 DÍAS.</b></i></td></tr>
	<tr><td><i>INICIA EL PRIMER DÍA POSTERIOR A LA COMPRA</b></i></td></tr>
	<tr><td><i>*MERCANCIA CON DESCUENTO O EN PROMOCIÓN NO TIENE CAMBIOS, NI DEVOLUCIONES</b></i></td></tr>
	<tr><td><i>*NO SE REALIZAN CAMBIOS EXTEMPORANEOS O SIN TICKET DE COMPRA</b></i></td></tr>
	<tr><td><i>*LA MERCANCIA DEBE PRESENTARSE CON SU ETIQUETA Y/O EMPAQUE/CAJA ORIGINAL</b></i></td></tr>
	<tr><td><i>¡AGRADECEMOS SU PREFERENCIA!</b></i></td></tr>
</table>

</body>