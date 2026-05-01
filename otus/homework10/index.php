<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #10: Обработка событий");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Обработчик изменений в элементе инфоблока</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/services/lists/21/view/0/">Список "Заявки"</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fmodules%2Fotus.main%2Flib%2FIblock%2FRequestDealSync.php&site=s1&lang=ru">Класс синхронизации элементов инфоблока со сделками</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
