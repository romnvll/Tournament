<?php
require ('security.php');
//ajout des equipes dans les poules
require 'class/pouleManagerDao.class.php';
require 'class/rencontreDao.class.php';
$pouledao = new PouleManager();
$rencontreDao = new RencontreDAO();

if (isset ($_GET['addpoule'])) {
    

    $pouledao->addEquipeToPoule($_GET['idequipe'],$_GET['pouleNom'],$_GET['tournoiId']);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    
}




if (isset($_GET['delete'])) {

    if ($_GET['delete'] == 1) {
        $rencontreDao->supprimerRencontresParPoule($_GET['pouleId']);

        $pouledao->supprimerLienEquipePoule($_GET['idequipe'],$_GET['pouleId']);
       header("Location: " . $_SERVER['HTTP_REFERER']);

    }

}



if (isset($_GET['CreerRencontre'])) {
        if ($_GET['CreerRencontre'] == 1) {
        $rencontreDao->createRencontreByPoule($_GET['pouleId'],$_GET['tournoiId'],1);
       
      header("Location:  RepartitionDesTours.php?id_tournoi=".$_GET['tournoiId']."&redirect=" . $_SERVER['HTTP_REFERER']);
        }

}


if (isset ($_GET['suppressionPoule'])) {
        if ($_GET['suppressionPoule'] == 1) {
            $pouledao->deletePoule($_GET['pouleId']);
            header("Location: " . $_SERVER['HTTP_REFERER']);
        }


}

?>