(function (window, BX) {
    if (!window || !BX) {
        return;
    }

    let currentPopup = null;

    function stopEvent(event) {
        if (!event) {
            return false;
        }

        if (event.preventDefault) {
            event.preventDefault();
        }

        if (event.stopPropagation) {
            event.stopPropagation();
        }

        event.cancelBubble = true;

        return false;
    }

    function notify(message) {
        if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
            BX.UI.Notification.Center.notify({content: message});
            return;
        }

        alert(message);
    }

    function buildProcedureOptions(procedures) {
        let html = '';
        let i = 0;

        for (i = 0; i < procedures.length; i++) {
            html += '<option value="' + BX.util.htmlspecialchars(String(procedures[i].ID)) + '">'
                + BX.util.htmlspecialchars(String(procedures[i].NAME))
                + '</option>';
        }

        return html;
    }

    function centerPopup(popup) {
        let popupNode = null;
        let left = 0;
        let top = 0;

        if (!popup || !popup.popupContainer) {
            return;
        }

        popupNode = popup.popupContainer;

        popupNode.style.position = 'fixed';
        popupNode.style.margin = '0';

        left = Math.round((window.innerWidth - popupNode.offsetWidth) / 2);
        top = Math.round((window.innerHeight - popupNode.offsetHeight) / 2);

        if (left < 0) {
            left = 0;
        }

        if (top < 20) {
            top = 20;
        }

        popupNode.style.left = left + 'px';
        popupNode.style.top = top + 'px';
    }

    function initCalendar(content) {
        let input = content.querySelector('.otus-booking-time');

        if (!input) {
            return;
        }

        BX.bind(input, 'click', function () {
            BX.calendar({
                node: input,
                field: input,
                value: input.value || '',
                bTime: true,
                callback: function (value) {
                    input.value = value;
                }
            });
        });
    }

    function setButtonLoadingState(popupButton, isLoading) {
        let node = null;

        if (!popupButton) {
            return;
        }

        node = popupButton.buttonNode || popupButton.container;

        popupButton.__otusLoading = isLoading;

        if (!node) {
            return;
        }

        if (typeof popupButton.__otusOriginalText === 'undefined') {
            popupButton.__otusOriginalText = node.textContent;
        }

        if (isLoading) {
            node.setAttribute('disabled', 'disabled');
            BX.addClass(node, 'popup-window-button-disabled');
            node.textContent = BX.message('OTUS_DOCTOR_BOOKING_SAVING');
        } else {
            node.removeAttribute('disabled');
            BX.removeClass(node, 'popup-window-button-disabled');
            node.textContent = popupButton.__otusOriginalText;
        }
    }

    function closeCurrentPopup() {
        if (!currentPopup) {
            return;
        }

        currentPopup.close();
    }

    function submit(button, content, popupButton) {
        let patientInput = null;
        let timeInput = null;
        let procedureInput = null;
        let patientName = '';
        let appointmentTime = '';
        let procedureId = '';
        let doctorId = '0';
        let url = '';
        let responseMessage = '';

        if (popupButton && popupButton.__otusLoading) {
            return;
        }

        patientInput = content.querySelector('.otus-booking-patient');
        timeInput = content.querySelector('.otus-booking-time');
        procedureInput = content.querySelector('.otus-booking-procedure');

        patientName = patientInput ? patientInput.value.trim() : '';
        appointmentTime = timeInput ? timeInput.value.trim() : '';
        procedureId = procedureInput ? procedureInput.value : '';
        doctorId = button.getAttribute('data-doctor-id') || '0';
        url = button.getAttribute('data-url') || '';

        if (!procedureId || !patientName || !appointmentTime) {
            notify(BX.message('OTUS_DOCTOR_BOOKING_REQUIRED'));
            return;
        }

        setButtonLoadingState(popupButton, true);

        BX.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: {
                sessid: BX.bitrix_sessid(),
                doctor_id: doctorId,
                procedure_id: procedureId,
                patient_name: patientName,
                appointment_time: appointmentTime
            },
            onsuccess: function (response) {
                setButtonLoadingState(popupButton, false);

                if (response && response.success) {
                    responseMessage = response.message || BX.message('OTUS_DOCTOR_BOOKING_SUCCESS');
                    notify(responseMessage);
                    closeCurrentPopup();
                    return;
                }

                responseMessage = response && response.message
                    ? response.message
                    : BX.message('OTUS_DOCTOR_BOOKING_REQUEST_ERROR');

                notify(responseMessage);
            },
            onfailure: function () {
                setButtonLoadingState(popupButton, false);
                notify(BX.message('OTUS_DOCTOR_BOOKING_REQUEST_ERROR'));
            }
        });
    }

    window.OtusDoctorBooking = {
        stop: function (event) {
            return stopEvent(event);
        },

        open: function (button, event) {
            let proceduresRaw = '[]';
            let procedures = [];
            let content = null;
            let doctorId = '0';
            let popupId = '';

            stopEvent(event);

            if (!button || typeof button.getAttribute !== 'function') {
                return false;
            }

            doctorId = button.getAttribute('data-doctor-id') || '0';
            proceduresRaw = button.getAttribute('data-procedures') || '[]';

            try {
                procedures = JSON.parse(proceduresRaw);
            } catch (error) {
                procedures = [];
            }

            if (!procedures.length) {
                notify(BX.message('OTUS_DOCTOR_BOOKING_REQUEST_ERROR'));
                return false;
            }

            if (currentPopup) {
                currentPopup.destroy();
                currentPopup = null;
            }

            content = BX.create('div', {
                props: {
                    className: 'otus-doctor-booking-popup'
                },
                html: ''
                    + '<div style="min-width:360px;">'
                    +     '<div style="margin-bottom:12px;">'
                    +         '<label style="display:block;margin-bottom:4px;">'
                    +             BX.message('OTUS_DOCTOR_BOOKING_PROCEDURE')
                    +         '</label>'
                    +         '<select class="ui-ctl-element otus-booking-procedure" style="width:100%;">'
                    +             buildProcedureOptions(procedures)
                    +         '</select>'
                    +     '</div>'
                    +     '<div style="margin-bottom:12px;">'
                    +         '<label style="display:block;margin-bottom:4px;">'
                    +             BX.message('OTUS_DOCTOR_BOOKING_PATIENT')
                    +         '</label>'
                    +         '<input type="text" class="ui-ctl-element otus-booking-patient" style="width:100%;" value="">'
                    +     '</div>'
                    +     '<div>'
                    +         '<label style="display:block;margin-bottom:4px;">'
                    +             BX.message('OTUS_DOCTOR_BOOKING_TIME')
                    +         '</label>'
                    +         '<input type="text" readonly="readonly" class="ui-ctl-element otus-booking-time" style="width:100%;cursor:pointer;" value="">'
                    +     '</div>'
                    + '</div>'
            });

            popupId = 'otus-doctor-booking-popup-' + doctorId + '-' + (new Date().getTime());

            currentPopup = BX.PopupWindowManager.create(popupId, null, {
                autoHide: true,
                closeByEsc: true,
                overlay: true,
                offsetLeft: 0,
                offsetTop: 0,
                content: content,
                titleBar: BX.message('OTUS_DOCTOR_BOOKING_TITLE'),
                buttons: [
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_DOCTOR_BOOKING_SAVE'),
                        className: 'popup-window-button-accept',
                        events: {
                            click: function () {
                                submit(button, content, this);
                            }
                        }
                    }),
                    new BX.PopupWindowButtonLink({
                        text: BX.message('OTUS_DOCTOR_BOOKING_CANCEL'),
                        events: {
                            click: function () {
                                closeCurrentPopup();
                            }
                        }
                    })
                ],
                events: {
                    onPopupClose: function (popupWindow) {
                        if (popupWindow) {
                            popupWindow.destroy();
                        }

                        if (currentPopup === popupWindow) {
                            currentPopup = null;
                        }
                    }
                }
            });

            currentPopup.show();

            setTimeout(function () {
                if (currentPopup) {
                    centerPopup(currentPopup);
                    initCalendar(content);
                }
            }, 0);

            return false;
        }
    };
})(window, window.BX);