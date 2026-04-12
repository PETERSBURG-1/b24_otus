<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #6: Написание своего модуля");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Кастомная вкладка в различных сущностях CRM</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/crm/deal/details/9/">Пример вывода в кастомной вкладке "Внешние данные"</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/perfmon_table.php?lang=ru&table_name=otus_crm_entity_data">Таблица БД otus_crm_entity_data</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fcomponents%2Fotus%2Fcrm.entity.data.grid%2Fclass.php&site=s1&lang=ru">Класс компонента crm.entity.data.grid</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_admin.php?lang=ru&path=%2Flocal%2Fmodules%2Fotus.main&site=s1">Модуль otus.main</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
