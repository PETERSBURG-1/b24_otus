<?php
require_once(__DIR__ . '/crest.php');

/**
 * Подписывает приложение на событие добавления комментария в таймлайн CRM.
 *
 * @return array
 */
function bindTimelineCommentEvent()
{
	return CRest::call(
		'event.bind',
		[
			'event' => 'onCrmTimelineCommentAdd',
			'handler' => APP_URL . 'handler.php',
		]
	);
}

$result = CRest::installApp();

if ($result['install'] == true)
{
	bindTimelineCommentEvent();
}

if($result['rest_only'] === false):?>
	<head>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<?php if($result['install'] == true):?>
			<script>
				BX24.init(function(){
					BX24.installFinish();
				});
			</script>
		<?php endif;?>
	</head>
	<body>
		<?php if($result['install'] == true):?>
			Приложение установлено
		<?php else:?>
			Ошибка установки приложения
		<?php endif;?>
	</body>
<?php endif;
