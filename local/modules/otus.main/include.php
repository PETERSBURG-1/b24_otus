<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'otus.main',
    [
        'Otus\\Main\\Crm\\EntityDetailsTabs' => 'lib/Crm/EntityDetailsTabs.php',
        'Otus\\Main\\Model\\CrmEntityDataTable' => 'lib/Model/CrmEntityDataTable.php',
        'Otus\\Main\\Iblock\\DoctorBookingProperty' => 'lib/Iblock/DoctorBookingProperty.php',
        'Otus\\Main\\Iblock\\DoctorBookingSync' => 'lib/Iblock/DoctorBookingSync.php',
        'Otus\\Main\\Service\\DoctorProcedureService' => 'lib/Service/DoctorProcedureService.php',
        'Otus\\Main\\Service\\BookingService' => 'lib/Service/BookingService.php',
        'Otus\\Main\\Ui\\BeginDateButton' => 'lib/Ui/BeginDateButton.php',
        'Otus\\Main\\Controllers\\TimemanActions\\Timeman' => 'lib/Controllers/TimemanActions/Timeman.php',
    ]
);
