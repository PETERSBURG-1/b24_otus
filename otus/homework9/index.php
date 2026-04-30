<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #9: Написание своих активити для БП");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Бизнес-процесс для обработки элементов инфоблока при создании.</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/services/lists/20/view/0/">Список "Заказы"</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/services/lists/20/bp_edit/6/">Шаблон бизнес-процесса</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Factivities%2Fcustom%2Fotuscompanybyinnactivity%2Fotuscompanybyinnactivity.php&site=s1&lang=ru">Класс активити по поиску ИНН</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
