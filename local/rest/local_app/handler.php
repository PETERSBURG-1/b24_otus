<?php
require_once(__DIR__ . '/crest.php');

handleTimelineCommentAdd($_REQUEST);

/**
 * Обрабатывает событие добавления комментария в таймлайн CRM.
 *
 * @param array $request Данные события от Битрикс24.
 * @return void
 */
function handleTimelineCommentAdd(array $request)
{
    $commentId = (int)($request['data']['FIELDS']['ID'] ?? 0);
	if ($commentId <= 0)
	{
		return;
	}

	$comment = getTimelineComment($commentId);
	if (!isContactComment($comment))
	{
		return;
	}

	updateContactLastCommunication((int)$comment['ENTITY_ID']);
}

/**
 * Возвращает данные комментария таймлайна по его идентификатору.
 *
 * @param int $commentId Идентификатор комментария.
 * @return array
 */
function getTimelineComment($commentId)
{
	$result = CRest::call(
		'crm.timeline.comment.get',
		[
			'id' => $commentId,
		]
	);

	return is_array($result['result'] ?? null) ? $result['result'] : [];
}

/**
 * Проверяет, что комментарий относится к контакту CRM.
 *
 * @param array $comment Данные комментария.
 * @return bool
 */
function isContactComment(array $comment)
{
	return mb_strtolower((string)($comment['ENTITY_TYPE'] ?? '')) === 'contact'
		&& (int)($comment['ENTITY_ID'] ?? 0) > 0;
}

/**
 * Записывает текущие дату и время в поле последней коммуникации контакта.
 *
 * @param int $contactId Идентификатор контакта.
 * @return array
 */
function updateContactLastCommunication($contactId)
{
	return CRest::call(
		'crm.contact.update',
		[
			'id' => $contactId,
			'fields' => [
				APP_LAST_COMMUNICATION_FIELD => date('c'),
			],
		]
	);
}
