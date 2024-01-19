<?php
//require ('security.php');
require 'class/rencontreDao.class.php';
$rencontre = new RencontreDAO();


if (isset ($_POST['idPoule'])) {
    $GetResultatDesPoules= $rencontre->GetResultatDesPoules($_POST['idPoule']);
   }

  // $GetResultatDesPoules= $rencontre->GetResultatDesPoules(2);
?>
<?php  foreach ($GetResultatDesPoules as $resultat )  ?>
<table class="table" id="classementTableau">
  <thead>
    <tr>
      <th scope="col">Classement</th>
      <th scope="col">Equipe</th>
      <th scope="col">Points</th>
      <th scope="col">Buts+</th>
      <th scope="col">Buts-</th>
      <th scope="col">Buts diff</th>
    </tr>
  </thead>
  <tbody>
    <?php $classement = 0; ?>
    <?php foreach ($GetResultatDesPoules as $resultat): ?>
      <?php $classement++; ?>
      <tr>
        <th scope="row"><?php echo $classement; ?></th>
        <td><?php echo $resultat['nom']; ?></td>
        <td><?php echo $resultat['TotalDesPoints']; ?></td>
        <td><?php echo $resultat['nombreButsMarque']; ?></td>
        <td><?php echo $resultat['nombreButsEncaisse']; ?></td>
        <td><?php echo $resultat['DifferenceButs']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


