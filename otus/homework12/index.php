<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #11: Собственные обработчики REST ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Cобственные CRUD-методы для работы с кастомной сущностью</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/perfmon_table.php?lang=ru&table_name=otus_rest_book">Таблица с кастомной сущностью "Книга"</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/devops/list/">Настроенный вебхуки</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fmodules%2Fotus.main%2Flib%2FRest%2FBookRest.php&site=s1&lang=ru">Класс с собственными обработчиками REST</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Flogs%2Frest_book.log&site=s1&lang=ru">Файл с логом</a>
    </li>
</ul>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
