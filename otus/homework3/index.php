<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #3: Связывание моделей ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Разработка простого приложения для работы со списками на D7</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/otus/homework3/doctors/">Компонент с врачами в публичке</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Flocal%2Fcomponents%2Fotus%2Fdoctors&show_perms_for=0&fu_action=">Реализация компонента с врачами</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Flocal%2FApp%2FModels&show_perms_for=0&fu_action=">Классы для связывания</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=16&type=Otus&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Инфоблок с врачами</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=17&type=Otus&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Инфоблок с процедурами</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
