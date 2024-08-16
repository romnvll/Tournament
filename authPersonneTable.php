
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"/>

<form  method="POST" action="authPersonneTableVerif.php" id="">
					
					<div class="col-auto">
						<label for="inputPassword2" class="visually-hidden">Code pin</label>
						<input type="number" class="form-control" name="codePin" id="codePin" placeholder="Code pin reçu par mail">
						<input type="hidden" name="key" value="<?php echo $_GET['key'] ?>">
						<input type="hidden" name="tournoiId" value="<?php echo $_GET['tournoi_id'] ?>">
					</div>
					<div class="col-auto">
						<button type="submit" class="btn btn-primary mb-3">OK</button>
					</div>
</form>

