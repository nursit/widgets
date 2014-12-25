<?php
/**
 * Fichier action
 *
 * @plugin     Widgets
 * @copyright  2014
 * @author     Cédric
 * @licence    GNU/GPL
 * @package    SPIP\Widgets\action
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_instituer_widget_dist($arg=null) {

	if (!function_exists('lire_config'))
		include_spip('inc/config');

	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	list($action,$bloc,$groupe,$idconfig) = explode('/', $arg);

	include_spip("inc/widgets");

	if (autoriser('administrer','widgets')){

		if ($action=="raz") {
			effacer_config("widgets/$groupe/liste_blocs_$idconfig");
		}
		else {

			$blocs = widgets_actifs($groupe,$idconfig);

			if ($bloc){
				// ajouter le bloc dans la liste si pas encore mentionne
				if (!isset($blocs[$bloc]))
					$blocs[$bloc] = false;

				switch($action) {

					case "on":
						$blocs[$bloc] = true;
						break;
					case "off":
						$blocs[$bloc] = false;
						break;
					case "first":
						unset($blocs[$bloc]);
						$blocs = array($bloc=>true) + $blocs;
						break;
					case "last":
						unset($blocs[$bloc]);
						$blocs = $blocs + array($bloc=>true);
						break;
					case "up":
						$n = 0;
						foreach($blocs as $b=>$a){
							if ($b==$bloc)
								break;
							$n++;
						}
						if ($n>0) {
							$blocs = array_slice($blocs,0,$n-1)
								+ array_slice($blocs,$n,1)
								+ array_slice($blocs,$n-1,1)
								+ array_slice($blocs,$n+1);
						}
						break;
					case "down":
						$n = 0;
						foreach($blocs as $b=>$a){
							if ($b==$bloc)
								break;
							$n++;
						}
						if ($n<count($blocs)) {
							$blocs = array_slice($blocs,0,$n)
								+ array_slice($blocs,$n+1,1)
								+ array_slice($blocs,$n,1)
								+ array_slice($blocs,$n+2);
						}
						break;
				}
			}

			$config = array();
			foreach($blocs as $b=>$a){
				$config[] = "$b:$a";
			}
			$config = implode(",",$config);
			ecrire_config("widgets/$groupe/liste_blocs_$idconfig",$config);
		}

		// invalider le cache
		include_spip('inc/invalideur');
		suivre_invalideur("id='home'");
	}
}