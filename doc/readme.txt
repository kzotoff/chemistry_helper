справка по написанию справки:

классы:
	like-admin-box : кнопка как в админ-панели
	pre            : исходные тексты
	code           : ссылки, имена файлов
	li.alert-sign  : элемент списка с красным треугольничком
	show-me-button : хрень с серым фоном и пунктирной границей, удобна для инлайновых кнопок

data-flash-item:
	если у контейнера есть этот атрибут, принажатии на него элемент по селектору, указанному в атрибуте, будет подсвечен
	пример: <span class="test" data-flash-item="#the-button" />
	если нету, попробует показать вспомогательный хелп

	
ссылки:
	a href="help.php?path=MODULE_NAME - ссылка на файл справки модуля
	a href="help.php?path=MODULE_NAME&get=SOMETHING.HTML - использовать общий шаблон хелпа (скрипты, CSS), но контент вывести из файла
	a href="help.php?path=MODULE_NAME&proxy=SOMETHING - вывести файл SOMETHING модуля MODULE_NAME  (удобно для картинок)
	a href="help.php?path=MODULE_NAME#anchor - вывести справку модуля MODULE_NAME, сразу открыть раздел с id="anchor"
	
картинки:
	простая:
	
	<img src="help.php?module=MODULE_NAME&proxy=YEAH.PNG" alt="sample" />

	
	сложная, с тенью (стиль пишем руками в каждую)
	
		<div class="pic-with-text" style=" . . . ">
			<img src="help.php?path=feedback&proxy=feedback1.png" alt="" />
		</div>
	
	
	сложная, с тенью и надписью. Прописываем стили положения и у внешнего дива, и у внутреннего (у внутреннего еще и размер желательно).
	
		<div class="pic-with-text" style=" . . . ">
			<img src="help.php?path=MODULE_NAME&proxy=SAMPLE.PNG" alt="" />
			<div style="left: 110px; top: 46px; width: 10em; height: 4em;" class="pic-text">
				тут текст надписи
				<div class="pic-text-back"></div>
			</div>
		</div>
	