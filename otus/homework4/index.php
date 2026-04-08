<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #4: Создание своих таблиц БД и написание модели данных к ним ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Модель данных для таблицы БД</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/otus/homework4/table/">Таблица с врачами и процедурами в публичке</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2FApp%2FModels%2FDoctorProcedureOfferTable.php&site=s1&lang=ru">Класс таблицы связей врача и процедуры</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/perfmon_table.php?lang=ru&table_name=otus_doctor_procedure_offer">Таблица в БД</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=16&type=Otus&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Инфоблок с врачами</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=17&type=Otus&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Инфоблок с процедурами</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
