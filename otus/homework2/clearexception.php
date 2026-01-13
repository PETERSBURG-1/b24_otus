<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
\App\Debug\Log::clear('exception');

LocalRedirect('/otus/homework2/');
