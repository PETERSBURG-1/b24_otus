<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Шаблоны БП формы');

$APPLICATION->IncludeComponent(
    'rexp.form:designer.bizproc',
    '',
    [
        'FORM_ID' => (int)($_REQUEST['FORM_ID'] ?? $_REQUEST['id'] ?? 0),
        'CONTROLLER' => 'rexp:form.Designer',
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
