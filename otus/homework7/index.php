<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #7: Создание кастомных полей и встраивание их в систему");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Cвой тип поля для элементов инфоблока</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=17&type=Otus&lang=ru&find_section_section=0&SECTION_ID=0&apply_filter=Y">Инфоблок с процедурами</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/services/lists/19/view/0/">Список с врачами</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/services/lists/18/view/0/">Список по бронированию</a>
    </li>
    <li class="list-group-item">
        <a href="https://cc838297.tw1.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fmodules%2Fotus.main%2Flib%2FIblock%2FDoctorBookingProperty.php&site=s1&lang=ru">Класс пользовательского свойства для бронирования</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
