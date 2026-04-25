<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #8: Учимся подключать свои скрипты, взаимодействовать с компонентами из фронтенда");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Кастомизация pop-up окна “Начать рабочий день”.</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fmodules%2Fotus.main%2Flib%2FUi%2FBeginDateButton.php&site=s1&lang=ru">Класс для подключения JS, который подключает модальное окно начала рабочего дня</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fmodules%2Fotus.main%2Flib%2FControllers%2FTimemanActions%2FTimeman.php&site=s1&lang=ru">Класс контроллера для запуска рабочего дня</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
