<?php
include_once(realpath(dirname(__FILE__)) . "/../../../classes/PluginsClassiques.class.php");

class Sitemaps extends PluginsClassiques{

    function demarrage() {
        global $res, $reptpl, $fond;

        // chargement du squelette
        if($res == "") {

            $tpl = $reptpl . $fond;

            // $tpl doit impérativement être dans le répertoire $reptpl, ou un de ses sous répertoires.
            $path_tpl = realpath(dirname($tpl));
            $path_reptpl = realpath($reptpl);
            if (strpos($path_tpl, $path_reptpl) !== 0) {
                die("FOND Invalide: $fond");
            }

            $sitemaps_fonds =  array('robots', 'sitemap');
            $sitemaps_tpl = array(
                $tpl,
                $tpl.'.html',
    			'client/plugins/sitemaps/fonds/' . $fond,
    			'client/plugins/sitemaps/fonds/' . $fond . '.html'
    		);

			if(in_array($fond, $sitemaps_fonds) || substr($fond, 0, 8) == 'sitemap-') {
			    foreach($sitemaps_tpl as $template) {
			        if(file_exists($template)) {
			            $res = file_get_contents($template);
			            break;
			        }
			    }
			}
        }
    }

    /**
     * Les sitemaps pour les produits et les rubriques ne peuvent être générées
     * par les boucles normales, trop lentes.
     * On défini donc de nouvelles boucles pour améliorer la génération des sitemaps
     * @see PluginsClassiques::boucle()
     */
    function boucle($texte, $args) {
        $boucle = lireTag($args, "boucle", "string");
        switch($boucle) {
            case 'produit' :
                return $this->_boucleProduit($texte, $args);
                break;
            case 'rubrique' :
                return $this->_boucleRubrique($texte, $args);
                break;
            case 'contenu' :
                return $this->_boucleContenu($texte, $args);
                break;
            case 'dossier' :
                return $this->_boucleDossier($texte, $args);
                break;
            case 'image' :
                return $this->_boucleImage($texte, $args);
                break;
        }
    }

    /**
     * Boucle pour produits
     * @param string $texte
     * @param string $args
     */
    private function _boucleProduit($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('
            SELECT
                produit.id,
                produit.rubrique
            FROM ' . Produit::TABLE . ' AS produit
            WHERE ligne=1', $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE
                        fond="produit"
                        AND param="&id_produit=' . $row->id . '&id_rubrique=' . $row->rubrique . '"'
                        , $this->link);
                        if(!empty($resultUrl)) {
                            $url = $urlsite->valeur . '/' . $resultUrl[0]->url;
                        }
            }
            if(empty($url)) {
                $url = $urlsite->valeur . '/?fond=produit&amp;id_produit=' . $row->id . '&amp;id_rubrique=' . $row->rubrique;
            }
            $temp = str_replace('#URL', $url, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }

     /**
     * Boucle pour rubriques
     * @param string $texte
     * @param string $args
     */
    private function _boucleRubrique($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('
        	SELECT id 
        	FROM ' . Rubrique::TABLE . ' 
        	WHERE ligne=1', $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE fond="rubrique" AND param="&id_rubrique=' . $row->id . '"'
                    , $this->link
                );
                if(!empty($resultUrl)) {
                    $url = $urlsite->valeur . '/' . $resultUrl[0]->url;
                }
            }
            if(empty($url)) {
                $url = $urlsite->valeur . "/?fond=rubrique&amp;id_rubrique=" . $row->id;
            }
            $temp = str_replace('#URL', $url, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }
    
     /**
     * Boucle pour contenus
     * @param string $texte
     * @param string $args
     */
	private function _boucleContenu($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('
            SELECT
                contenu.id,
                contenu.dossier
            FROM ' . Contenu::TABLE . ' AS contenu
            WHERE ligne=1', $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE
                        fond="contenu"
                        AND param="&id_contenu=' . $row->id . '&id_dossier=' . $row->dossier . '"'
                        , $this->link);
                        if(!empty($resultUrl)) {
                            $url = $urlsite->valeur . '/' . $resultUrl[0]->url;
                        }
            }
            if(empty($url)) {
                $url = $urlsite->valeur . '/?fond=contenu&amp;id_contenu=' . $row->id . '&amp;id_dossier=' . $row->dossier;
            }
            $temp = str_replace('#URL', $url, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }
    
 	/**
     * Boucle pour dossier
     * @param string $texte
     * @param string $args
     */
    private function _boucleDossier($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('
        	SELECT id 
        	FROM ' . Dossier::TABLE . ' 
        	WHERE ligne=1', $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE fond="dossier" AND param="&id_dossier=' . $row->id . '"'
                    , $this->link);
                    if(!empty($resultUrl)) {
                        $url = $urlsite->valeur . '/' . $resultUrl[0]->url;
                    }
            }
            if(empty($url)) {
                $url = $urlsite->valeur . "/?fond=dossier&amp;id_dossier=" . $row->id;
            }
            $temp = str_replace('#URL', $url, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }

     /**
     * Boucle pour images
     * @param string $texte
     * @param string $args
     */
    private function _boucleImage($texte, $args) {
        $res = '';
        $produit = lireTag($args, "produit", "int");
        $rubrique = lireTag($args, "rubrique", "int");
        $contenu = lireTag($args, "contenu", "int");
        $dossier = lireTag($args, "dossier", "int");

        $objet = null;
        if($produit) {
            $objet = 'produit';
            $idObjet = $produit;
        }
        elseif($rubrique) {
            $objet = 'rubrique';
            $idObjet = $rubrique;
        }
    	elseif($contenu) {
            $objet = 'contenu';
            $idObjet = $contenu;
        }
    	elseif($dossier) {
            $objet = 'dossier';
            $idObjet = $dossier;
        }

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $sql = '
            SELECT
                imagedesc.titre,
                image.fichier
            FROM ' .
        Image::TABLE . ' AS image LEFT JOIN ' .
        Imagedesc::TABLE . ' AS imagedesc ON (
                    image.id=imagedesc.image
                )';

        if(!is_null($objet)) {
            $sql .= ' WHERE image.' . $objet . '=' . $idObjet;
        }
        $resul = CacheBase::getCache()->mysql_query($sql, $this->link);
        foreach((array) $resul as $row) {
            $temp = str_replace('#URL', $urlsite->valeur . '/client/gfx/photos/' . $objet . '/' . $row->fichier, $texte);
            $res .= $temp;
        }
        return $res;
    }
}
