<?php
/**
 * Fichier inc
 *
 * @plugin     Widgets
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Widgets\action
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


/**
 * Afficher les widgets pour le groupe concerne
 *
 * @param string $groupe
 * @param string|array $env
 * @return string
 */
function widgets_affiche($groupe,$env){

	$edit = (_request('var_mode')=='widgets'?true:false);
	if ($edit){
		include_spip('inc/autoriser');
		$edit = autoriser('administrer','widgets');
	}

	$idconfig = widgets_idconfig($groupe,$env);
	$blocs = widgets_actifs($groupe,$idconfig);
	$out = "";
	$contexte = array();

	// en edition, completer la liste avec celle des blocs dispos
	// les blocs ajoutes sont inactifs donc
	if ($edit){
		$all = widgets_dispos($groupe);
		$all = array_diff($all,array_keys($blocs));
		foreach($all as $a){
			$blocs[$a] = false;
		}
	}

	foreach($blocs as $bloc=>$actif){
		if ($edit OR $actif) {
			$texte = recuperer_fond("$groupe/widgets/$bloc",$contexte);
			if ($edit){
				$texte = widgets_edition($bloc,$actif,$texte,$groupe,$idconfig);
			}
			$out .= $texte;
		}
	}

	return $out;
}

/**
 * Ajouter les boutons d'edition du widget en mode edition
 *
 * @param string $bloc
 * @param bool $actif
 * @param string $texte
 * @param string $groupe
 * @param string $idconfig
 * @return string
 */
function widgets_edition($bloc,$actif,$texte,$groupe,$idconfig){
	$class = "widget-edition " . ($actif?"widget-on":"widget-off");
	$bouton_action = charger_filtre("bouton_action");

	$redirect = ancre_url(parametre_url(self(),'var_mode','widgets'),"widget-$bloc");
	$boutons = "";
	if ($actif) {
		$boutons .= $bouton_action(_T('widgets:bouton_widget_first'),
			generer_action_auteur('instituer_widget',"first/$bloc/$groupe/$idconfig",$redirect),'btn-small btn-first','',_T('widgets:bouton_widget_first_title'));
		$boutons .= $bouton_action(_T('widgets:bouton_widget_up'),
			generer_action_auteur('instituer_widget',"up/$bloc/$groupe/$idconfig",$redirect),'btn-small btn-up','',_T('widgets:bouton_widget_up_title'));
		$boutons .= $bouton_action(_T('widgets:bouton_widget_down'),
			generer_action_auteur('instituer_widget',"down/$bloc/$groupe/$idconfig",$redirect),'btn-small btn-down','',_T('widgets:bouton_widget_down_title'));
		$boutons .= $bouton_action(_T('widgets:bouton_widget_last'),
			generer_action_auteur('instituer_widget',"last/$bloc/$groupe/$idconfig",$redirect),'btn-small btn-last','',_T('widgets:bouton_widget_last_title'));
	}
	$boutons .= $bouton_action(
		$actif?_T('widgets:bouton_widgets_desactiver'):_T('widgets:bouton_widget_activer'),
			generer_action_auteur('instituer_widget',($actif?"off":"on")."/$bloc/$groupe/$idconfig",$redirect),'btn-small '.($actif?'btn-warning':'btn-info'));

	$boutons = "<div class='boutons'>$boutons</div>";

	$texte = "<div class='$class' id='widget-$bloc'>"
		. $texte
		. $boutons
		. "</div>";

	return $texte;
}

/**
 * Afficher les boutons d'admin du groupe
 *
 * @param string $groupe
 * @param string|array $env
 * @return string
 */
function widgets_boutons_admin($groupe,$env){
	$idconfig = widgets_idconfig($groupe,$env);
	$edit = (_request('var_mode')=='widgets'?true:false);
	if ($edit){
		include_spip('inc/autoriser');
		$edit = autoriser('administrer','widgets');
	}

	$bouton_action = charger_filtre("bouton_action");

	$boutons = "";
	if ($edit){
		$redirect = parametre_url(self(),'var_mode','widgets');
		$boutons .= $bouton_action(
			_T('widgets:bouton_widgets_reinit')." ($idconfig)",
			generer_action_auteur('instituer_widget',"raz//$groupe/$idconfig",$redirect),'btn-mini pull-left btn-danger',_T('widgets:label_confirm_reinit'));
	}

	$boutons .= $bouton_action(
		$edit?_T('widgets:bouton_widgets_fin_editer'):_T('widgets:bouton_widgets_editer'),
		$edit?self():parametre_url(self(),'var_mode','widgets'),'btn-small ');

	lire_fichier(find_in_path("css/widgets.css"),$css);
	$boutons .= "<style>$css</style>";

	return $boutons;
}

/**
 * Calculer l'id config auto des widgets : type de page, composition, secteur
 *
 * @param string $groupe
 * @param string|array $env
 * @return string
 */
function widgets_idconfig($groupe,$env){
	if (is_string($env))
		$env = unserialize($env);
	$idconfig = "defaut";
	if (isset($env['type-page'])){
		$idconfig = $env['type-page'];
		if (isset($env['composition']) AND in_array($env['composition'],array('campagne')))
			$idconfig .= "-" . $env['composition'];
		if (isset($env['id_secteur']))
			$idconfig .= "@" . $env['id_secteur'];
		#var_dump($env);
		#var_dump($idconfig);
	}

	return $idconfig;
}

/**
 * Recuperer les widgets actifs d'une config
 * avec fallback article => rubrique => sommaire
 *
 * @param string $groupe
 * @param string|array $config
 * @return array
 */
function widgets_actifs($groupe,$config) {
	if (!function_exists('lire_config'))
		include_spip('inc/config');

	// la config existante pour cette page-secteur
	$liste = trim(lire_config("widgets/$groupe/liste_blocs_$config",''));

	// si page article, on fallback sur config rubrique sinon
	if (!$liste AND strncmp($config,"article",7)==0){
		$config = str_replace("article","rubrique",$config);
		$liste = trim(lire_config("widgets/$groupe/liste_blocs_$config",''));
	}

	// sinon on fallback sur config sommaire
	if (!$liste)
		$liste = trim(lire_config("widgets/$groupe/liste_blocs_sommaire",''));

	// sinon liste en dur
	if (!$liste AND isset($GLOBALS["defaut_widgets_$groupe"]))
		$liste = $GLOBALS["defaut_widgets_$groupe"];

	$liste = explode(",",$liste);
	$blocs = array();
	foreach($liste as $l){
		$l = explode(":",$l);
		$b = reset($l);
		if ($b){
			$actif = (count($l)>1?intval($l[1]):1);
			$blocs[reset($l)] = $actif;
		}
	}

	return $blocs;
}

/**
 * Trouver tous les widgets dispos pour un groupe
 *
 * @param string $groupe
 * @return array
 */
function widgets_dispos($groupe) {
	$liste = array();
	$fonds = find_all_in_path("$groupe/widgets/","\.html$");
	foreach($fonds as $fond){
		$liste[] = basename($fond,".html");
	}

	return $liste;
}