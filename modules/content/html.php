<?php //> � <- UTF mark

// ������� ��� �������� �������� ��� ��������������
define('MODULE_CONTENT_TEXTAREA_WRAPPER', '
<form action="./%1$s" method="post" id="editpagecontentform">
<input type="hidden" name="action" value="savepage" />
<input type="hidden" name="module" value="content" />
<textarea style="min-height:500px;" name="pagecontent" id="editor" class="apply_tinymce">%2$s</textarea>
</form>
');

// ������� ��� php-����
define('MODULE_CONTENT_TEXTAREA_WRAPPER_PHP', '
<form action="./%1$s" method="post" id="editpagecontentform">
<input type="hidden" name="action" value="savepage" />
<input type="hidden" name="module" value="content" />
<h4 class="php_code_informer">This page is script-generated, edit on your own risk</h4>
<textarea style="min-height:500px; width: 100%%" name="pagecontent" id="editor">%2$s</textarea>
</form>
');

// ��������� ����� �������� � ������ �������� php-����������
define('MODULE_CONTENT_NEW_PHP_PAGE', '<?php

// this is auto-generated function. just fill it with your code!
function %s() {
	$html = \'New page content\';
	
	return $html;
}
?>');

// html ��� ���� "��� ������"
define('MODULE_CONTENT_PRINT_FORM', '
<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
%content%
</body>
</html>
');


?>