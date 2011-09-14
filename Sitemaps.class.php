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
            case 'image' :
                return $this->_boucleImage($texte, $args);
                break;
        }
    }

    private function _boucleProduit($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('
            SELECT
                produit.id,
                produit.rubrique
            FROM ' . Produit::TABLE . ' AS produit', $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE
                        fond="produit"
                        AND param="&id_produit=' . $row->id . '&id_rubrique=' . $row->rubrique . '"'
                        , $this->link);
                        if(!empty($resultUrl)) {
                            $prodUrl = $urlsite->valeur . '/' . $resultUrl[0]->url;
                        }
            }
            if(empty($prodUrl)) {
                $prodUrl = $urlsite->valeur . '/?fond=produit&amp;id_produit=' . $row->id . '&amp;id_rubrique=' . $row->rubrique;
            }
            $temp = str_replace('#URL', $prodUrl, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }

    private function _boucleRubrique($texte, $args) {
        $res = '';

        $urlsite = new Variable("urlsite");
        $rewrite = new Variable("rewrite");

        $resul = CacheBase::getCache()->mysql_query('SELECT id FROM ' . Rubrique::TABLE, $this->link);
        foreach((array) $resul as $row) {
            if($rewrite->valeur){
                $resultUrl = CacheBase::getCache()->mysql_query(
                    'SELECT url from ' . Reecriture::TABLE . '
                    WHERE fond="rubrique" AND param="&id_rubrique=' . $row->id . '"'
                    , $this->link);
                    if(!empty($resultUrl)) {
                        $rubUrl = $urlsite->valeur . '/' . $resultUrl[0]->url;
                    }
            }
            if(empty($rubUrl)) {
                $rubUrl = $urlsite->valeur . "/?fond=rubrique&amp;id_rubrique=" . $row->id;
            }
            $temp = str_replace('#URL', $rubUrl, $texte);
            $temp = str_replace('#ID', $row->id, $temp);
            $res .= $temp;
        }
        return $res;
    }

    private function _boucleImage($texte, $args) {
        $res = '';
        $produit = lireTag($args, "produit", "int");
        $rubrique = lireTag($args, "rubrique", "int");

        $objet = null;
        if($produit) {
            $objet = 'produit';
            $idObjet = $produit;
        }
        elseif($rubrique) {
            $objet = 'rubrique';
            $idObjet = $rubrique;
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
        //var_dump($sql);
        //exit();
        $resul = CacheBase::getCache()->mysql_query($sql, $this->link);
        foreach((array) $resul as $row) {
            $temp = str_replace('#URL', $urlsite->valeur . '/client/gfx/photos/' . $objet . '/' . $row->fichier, $texte);
            /*
             $temp = str_replace('#PRODUIT', $params['produit'], $temp);
             $temp = str_replace("#COMPT", $compt, $temp);*/
            $res .= $temp;
        }
        return $res;
    }
}
