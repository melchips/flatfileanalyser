<html>
<head>
<style type="text/css">
body {font-family:verdana, sans-serif;font-size:10px;}
body div.background {position:absolute;left:0px;top:2px;width:873;height:557;background-image:url(img/masque_application.png);}
span.depart {position:absolute;left:20px;top:30px;}
span.depart input {color:#0000ff;font-weight:bold;}
span.position {position:absolute;left:192px;top:30px;}
span.position input {font-weight:bold;color:#aaa;}
span.date_position {position:absolute;left:63px;top:52px;}
span.trafic {position:absolute;left:344px;top:53px;}
span.nom_destinataire {position:absolute;left:283px;top:94px;}
span.adresse1_destinataire {position:absolute;left:283px;top:114px;}
span.adresse2_destinataire {position:absolute;left:283px;top:134px;}
span.adresse3_destinataire {position:absolute;left:283px;top:154px;}
span.pays_destinataire {position:absolute;left:283px;top:177px;}
span.code_dpt_destinataire {position:absolute;left:310px;top:177px;}
span.dpt_destinataire {position:absolute;left:344px;top:177px;}
span.commune_destinataire {position:absolute;left:284px;top:201px;}
span.reference_emetteur {position:absolute;left:678px;top:210px;}
span.reference_destinataire {position:absolute;left:678px;top:230px;}
span.nb_colis {position:absolute;left:68px;top:302px;}
span.nb_colis_sur_pal {position:absolute;left:174px;top:302px;}
span.nb_colis_isoles {position:absolute;left:280px;top:302px;}
span.poids {position:absolute;left:42px;top:330px;}
span.nb_palettes {position:absolute;left:174px;top:330px;}
span.nb_um {position:absolute;left:280px;top:330px;}
span.volume {position:absolute;left:42px;top:360px;}
span.plancher {position:absolute;left:142px;top:360px;}
span.commentaires_recep {position:absolute;left:135px;top:434px;}
span.dil {position:absolute;left:135px;top:458px;}
span.ref_commande {position:absolute;left:330px;top:482px;}
span.type_port {position:absolute;left:527px;top:434px;}
span.telephone {position:absolute;left:283px;top:225px;}
span.email {position:absolute;left:283;top:249px;}
span.matricule_chargeur {position:absolute;left:38;top:94px;}
span.code_agence {position:absolute;left:795px;top:31px;}
span.code_incoterm {position:absolute;left:535px;top:205px;}
span.ville_incoterm {position:absolute;left:535px;top:226px;}
input{height:19px;margin:3px 0px 0px 2px;padding:0px;font-size:11px;white-space: nowrap;overflow:hidden;border:none;background-color:transparent;}
<?php
//echo (get_magic_quotes_gpc())?stripslashes(urldecode($_GET["P0"])):urldecode($_GET["P0"]);
?>
</style>
</head>
<body>
<div class="background"></div>
<span class="depart"><input type="text" size="6" value="DEPART" /></span>
<span class="position"><input type="text" size="8" value="<?php echo $_GET["P0_NUMERO_RECEPISSE"]; ?>" /></span>
<span class="date_position"><input type="text" size="8" value="<?php echo $_GET["P0_DATE_EXPEDITION"]; ?>" /></span>
<span class="trafic"><input type="text" size="2" value="<?php echo $_GET["P0_PRESTATION"]; ?>" /></span>
<span class="nom_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_NOM_DESTINATAIRE"]." ".$_GET["P0_COMPLEMENT_NOM"]; ?>" /></span>
<span class="adresse1_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_ADRESSE_1"]; ?>" /></span>
<span class="adresse2_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_ADRESSE_2"]; ?>" /></span>
<span class="adresse3_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_COMPLEMENT_ADRESSE"]; ?>" /></span>
<span class="pays_destinataire"><input type="text" size="2" value="<?php echo $_GET["I0_PAYS_DESTINATAIRE"]; ?>" /></span>
<span class="code_dpt_destinataire"><input type="text" size="2" value="<?php echo substr(substr('000000'.$_GET["P0_CODE_POSTAL"],-6),1,2); ?>" /></span>
<span class="dpt_destinataire"><input type="text" size="5" value="<?php echo $_GET["P0_CODE_POSTAL"]; ?>" /></span>
<span class="commune_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_VILLE"]; ?>" /></span>
<span class="reference_emetteur"><input type="text" size="29" value="<?php echo $_GET["P0_REFERENCE_EMETTEUR"]; ?>" /></span>
<span class="reference_destinataire"><input type="text" size="29" value="<?php echo $_GET["P0_REFERENCE_DESTINATAIRE"]; ?>" /></span>
<span class="nb_colis"><input type="text" size="6" value="<?php echo intval($_GET["P0_NOMBRE_COLIS"]) ?>" /></span>
<span class="nb_colis_sur_pal"><input type="text" size="6" value="<?php

$nb_total_palettes = (intval($_GET["L0_NOMBRE_PALETTES_EUROPE"])==0)?intval($_GET["P0_NOMBRE_PALETTES_EUROPE"])+intval($_GET["L0_NOMBRE_PALETTES_PERDUE"])+intval($_GET["L0_NOMBRE_PALETTES_DIVERSE"]):intval($_GET["L0_NOMBRE_PALETTES_EUROPE"])+intval($_GET["L0_NOMBRE_PALETTES_PERDUE"])+intval($_GET["L0_NOMBRE_PALETTES_DIVERSE"]);
echo intval($_GET["P0_NOMBRE_COLIS"]) - ( intval($_GET["P0_UNITES_MANUTENTION"] - $nb_total_palettes)  )

?>" /></span>
<span class="nb_colis_isoles"><input type="text" size="6" value="<?php echo intval($_GET["P0_UNITES_MANUTENTION"]) - $nb_total_palettes; ?>" /></span>
<span class="poids"><input type="text" size="11" value="<?php echo floatval($_GET["P0_POIDS"]) ?>" /></span>
<span class="nb_palettes"><input type="text" size="3" value="<?php echo $nb_total_palettes; ?>" /></span>
<span class="nb_um"><input type="text" size="6" value="<?php echo intval($_GET["P0_UNITES_MANUTENTION"]); ?>" /></span>
<span class="volume"><input type="text" size="6" value="<?php echo number_format($_GET["P0_VOLUME"]/100,3); ?>" /></span>
<span class="plancher"><input type="text" size="6" value="<?php echo floatval($_GET["P0_LONGUEUR_PLANCHER"]) ?>" /></span>
<span class="commentaires_recep"><input type="text" size="60" value="<?php echo $_GET["P0_INSTRUCTIONS_LIVRAISON"].$_GET["P0_INSTRUCTIONS_LIVRAISON_SUITE"]; ?>" /></span>
<span class="dil"><input type="text" size="8" value="<?php echo $_GET["P0_DATE_IMPERATIVE_LIVRAISON"]; ?>" /></span>
<span class="ref_commande"><input type="text" size="22" value="<?php echo $_GET["P0_NUMERO_COMMANDE"]; ?>" /></span>
<span class="type_port"><input type="text" size="10" value="
<?php
if ($_GET["P0_PORT"]=="P")
	echo "P:Port payé";
else if ($_GET["P0_PORT"]=="C")
	echo "C:Port dû";
else if ($_GET["P0_PORT"]=="F")
	echo "F:En service";
?>
" />
</span>
<span class="telephone"><input type="text" size="15" value="<?php echo $_GET["P0_TELEPHONE"]; ?>" /></span>
<span class="email"><input type="text" size="33" value="<?php echo $_GET["P0_EMAIL"]; ?>" /></span>
<span class="matricule_chargeur"><input type="text" size="6" value="<?php echo $_GET["P0_MATRICULE_CHARGEUR"]; ?>" /></span>
<span class="code_agence"><input type="text" size="6" value="<?php echo $_GET["P0_CODE_AGENCE"]; ?>" /></span>
<span class="code_incoterm"><input type="text" size="3" value="<?php echo $_GET["I0_CODE_INCOTERM"]; ?>" /></span>
<span class="ville_incoterm"><input type="text" size="15" value="<?php echo $_GET["I0_VILLE_INCOTERM"]; ?>" /></span>
</body>
</html>

