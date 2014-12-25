<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

if (_request('var_mode')=='widgets'){
	define('_VAR_MODE','calcul');
	define('_VAR_NOCACHE',true);
}

/**
 * Generer affichage des widgets d'un groupe avec boutons d'admin si besoin
 *
 * #AFFICHE_WIDGETS_ASIDE
 * #AFFICHE_WIDGETS_EXTRA
 *
 * @param object $p
 * @return object
 */
function balise_AFFICHE_WIDGETS__dist($p) {
	$nom = $p->nom_champ;
	if ($nom === 'AFFICHE_WIDGETS_') {
		$msg = array('zbug_balise_sans_argument', array('balise' => ' AFFICHE_WIDGETS_'));
		erreur_squelette($msg, $p);
		$p->interdire_scripts = false;
		return $p;
	}

	$groupe = strtolower($nom);
	$groupe = substr($groupe,strlen('AFFICHE_WIDGETS_'));

	$code = "
	include_spip(\'inc/widgets\');
	echo widgets_affiche(\'$groupe\','.var_export(@\$Pile[0],true).');
	";

	// les boutons d'admin en supplement
	$code .= "
	if (isset(\$GLOBALS[\'visiteur_session\'][\'statut\'])
	  AND \$GLOBALS[\'visiteur_session\'][\'statut\']==\'0minirezo\'
		AND	include_spip(\'inc/autoriser\')
		AND autoriser(\'administrer\',\'widgets\')) {
			include_spip(\'inc/widgets\');
			echo \"<div class=\'boutons spip-admin actions administrerwidgets\'>\"
			. widgets_boutons_admin(\'$groupe\','.var_export(@\$Pile[0],true).')
			. \"</div>\";
		}";

	$p->code = "
'<'.'?php
$code
?'.'>'";

	$p->interdire_scripts = false;
	return $p;
}
