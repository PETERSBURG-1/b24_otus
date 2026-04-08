<?php

use App\Models\DoctorProcedureOfferTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;
$APPLICATION->SetAdditionalCss("/otus/homework4/table/style.css");
Loc::loadMessages(__FILE__);
$APPLICATION->SetTitle(Loc::getMessage('PAGE_TITLE'));

/**
 * Возвращает строки для таблицы.
 *
 * @return array<int, array<string, mixed>>
 */
function getOfferRows(): array
{
    return DoctorProcedureOfferTable::getList([
        'select' => [
            'ID',
            'PRICE',
            'CABINET',
            'SORT',
            'DOCTOR_NAME' => 'DOCTOR.ELEMENT.NAME',
            'PROCEDURE_NAME' => 'PROCEDURE.ELEMENT.NAME',
        ],
        'filter' => [
            '=DOCTOR.ELEMENT.ACTIVE' => 'Y',
            '=PROCEDURE.ELEMENT.ACTIVE' => 'Y',
        ],
        'order' => [
            'SORT' => 'ASC',
            'ID' => 'ASC',
        ],
    ])->fetchAll();
}

    $rows = getOfferRows();
?>

    <div class="container">
        <h1><?=htmlspecialcharsbx(Loc::getMessage('PAGE_HEADING'))?></h1>

            <table class="otus_table">
                <thead>
                <tr>
                    <th class="otus_th">
                        <?=htmlspecialcharsbx(Loc::getMessage('COL_ID'))?>
                    </th>
                    <th class="otus_th">
                        <?=htmlspecialcharsbx(Loc::getMessage('COL_DOCTOR'))?>
                    </th>
                    <th class="otus_th">
                        <?=htmlspecialcharsbx(Loc::getMessage('COL_PROCEDURE'))?>
                    </th>
                    <th class="otus_th">
                        <?=htmlspecialcharsbx(Loc::getMessage('COL_PRICE'))?>
                    </th>
                    <th class="otus_th">
                        <?=htmlspecialcharsbx(Loc::getMessage('COL_CABINET'))?>
                    </th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="otus_td">
                            <?=(int)$row['ID']?>
                        </td>
                        <td class="otus_td">
                            <?=htmlspecialcharsbx((string)$row['DOCTOR_NAME'])?>
                        </td>
                        <td class="otus_td">
                            <?=htmlspecialcharsbx((string)$row['PROCEDURE_NAME'])?>
                        </td>
                        <td class="otus_td">
                            <?=(int)$row['PRICE']?>
                        </td>
                        <td class="otus_td">
                            <?=htmlspecialcharsbx((string)$row['CABINET'])?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    </div>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>