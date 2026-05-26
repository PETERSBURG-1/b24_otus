<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #11: Локальные приложения и вебхуки");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Локальное REST приложение дата последней коммуникации</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/devops/edit/application/3/">Локальное приложение</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Frest%2Flocal_app%2Fhandler.php&site=s1&lang=ru">Обработчик события обновления контакта</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
