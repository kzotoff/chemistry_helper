������� �� ��������� �������:

������:
	like-admin-box : ������ ��� � �����-������
	pre            : �������� ������
	code           : ������, ����� ������
	li.alert-sign  : ������� ������ � ������� ��������������
	show-me-button : ����� � ����� ����� � ���������� ��������, ������ ��� ���������� ������

data-flash-item:
	���� � ���������� ���� ���� �������, ���������� �� ���� ������� �� ���������, ���������� � ��������, ����� ���������
	������: <span class="test" data-flash-item="#the-button" />
	���� ����, ��������� �������� ��������������� ����

	
������:
	a href="help.php?path=MODULE_NAME - ������ �� ���� ������� ������
	a href="help.php?path=MODULE_NAME&get=SOMETHING.HTML - ������������ ����� ������ ����� (�������, CSS), �� ������� ������� �� �����
	a href="help.php?path=MODULE_NAME&proxy=SOMETHING - ������� ���� SOMETHING ������ MODULE_NAME  (������ ��� ��������)
	a href="help.php?path=MODULE_NAME#anchor - ������� ������� ������ MODULE_NAME, ����� ������� ������ � id="anchor"
	
��������:
	�������:
	
	<img src="help.php?module=MODULE_NAME&proxy=YEAH.PNG" alt="sample" />

	
	�������, � ����� (����� ����� ������ � ������)
	
		<div class="pic-with-text" style=" . . . ">
			<img src="help.php?path=feedback&proxy=feedback1.png" alt="" />
		</div>
	
	
	�������, � ����� � ��������. ����������� ����� ��������� � � �������� ����, � � ����������� (� ����������� ��� � ������ ����������).
	
		<div class="pic-with-text" style=" . . . ">
			<img src="help.php?path=MODULE_NAME&proxy=SAMPLE.PNG" alt="" />
			<div style="left: 110px; top: 46px; width: 10em; height: 4em;" class="pic-text">
				��� ����� �������
				<div class="pic-text-back"></div>
			</div>
		</div>
	