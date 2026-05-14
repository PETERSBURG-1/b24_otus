<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Редактор формы');

$APPLICATION->IncludeComponent(
    'bitrix:ui.sidepanel.wrapper',
    '',
    [
        'POPUP_COMPONENT_NAME' => 'rexp.form:designer.editor',
        'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
        'POPUP_COMPONENT_PARAMS' => [
            'CONTROLLER' => 'rexp:form.Designer',
        ],
        'USE_PADDING' => false,
        'USE_UI_TOOLBAR' => 'Y',
        'PLAIN_VIEW' => 'N',
        'PAGE_MODE' => false,
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
