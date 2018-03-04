<?php
header('Content-Type: text/html; charset=utf-8');
require('config.php');

if (substr($path_for_php_works, -1) != '/') {
	$path_for_php_works .= './';
}

if (isset($_SERVER['WINDIR'])) {
	$filesystem_encoding = 'windows-1251';
} else {
	$filesystem_encoding = 'utf-8';
}

// save from POST
if (isset($_FILES['filename'])) {
	for ($i=0; $i<count($_FILES['filename']['name']); $i++) {
		if (@$_FILES['filename']['size'][$i] > 0) {
			if (preg_match('~^[a-zA-Z0-9\-_а-яА-Я\(\)][\sa-zA-Z0-9\-_а-яА-Я.\(\)]+$~u', $_FILES['filename']['name'][$i]) > 0) {
				move_uploaded_file(
					$_FILES['filename']['tmp_name'][$i],
					iconv('utf-8', $filesystem_encoding, $path_for_php_works.$_FILES['filename']['name'][$i])
				);
			}
		}
	}
}

$file_elem_template = <<<HTML
	<tr>
		<td><input type="radio" name="selector[]" value="$path_for_js_works%1\$s" /></td>
		<td><img src="%2\$s" alt="" /> %1\$s</td>
	</tr>
HTML;

$list = scandir($path_for_php_works);
$_ = '';
foreach($list as $filename) {
	if (substr($filename, 0, 1) == '.') {
		continue;
	}
	$file_ext = pathinfo($filename, PATHINFO_EXTENSION);
	if (!file_exists($image = 'images/'.$file_ext.'.png')) {
		$image = 'images/default.png';
	}
	$_ .= sprintf($file_elem_template, iconv($filesystem_encoding, 'utf-8', $filename), $image);
}


?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title></title>
	<link rel="stylesheet" type="text/css" href="css.css" />
</head>
<body>

	<form class="container add-file-container" enctype="multipart/form-data" method="post">
		<span class="container-caption">Добавить файлы в хранилище:</span>
		<input type="file" multiple="multiple" name="filename[]" />
		<input type="submit" value="Добавить" />
	</form>

	<div class="container list-container">
		<span class="container-caption">Добавить файл из хранилища:</span>
		<div class="table-container">
			<table>
				<?php echo $_; ?>
			</table>
		</div>
	</div>

	<div class="container link-text-container">
		<span class="container-caption">Отображаемый текст:</span>
		<input type="text" name="link_text" data-meaning="link-text" placeholder="visible link text" />
	</div>

</body>
</html>