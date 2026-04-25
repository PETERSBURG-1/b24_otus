(function (window, BX) {
    if (!window || !BX) {
        return;
    }

    BX.namespace('Otus.BeginDateButton');

    BX.Otus.BeginDateButton = {
        popup: null,
        requestRunning: false,
        currentMode: 'open',
        isPatched: false,
        patchTimer: null,
        patchAttempts: 0,

        showPopup: function (mode) {
            this.currentMode = mode === 'reopen' ? 'reopen' : 'open';

            if (this.popup) {
                this.popup.show();
                return;
            }

            this.popup = BX.PopupWindowManager.create('otus-begin-date-button-popup', null, {
                content: BX.message('OTUS_MAIN_BEGIN_DATE_BUTTON_TEXT'),
                width: 500,
                closeIcon: {
                    opacity: 1
                },
                titleBar: BX.message('OTUS_MAIN_BEGIN_DATE_BUTTON_TITLE'),
                closeByEsc: true,
                autoHide: true,
                overlay: {
                    backgroundColor: 'black',
                    opacity: 50
                },
                buttons: [
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_MAIN_BEGIN_DATE_BUTTON_CONFIRM'),
                        className: 'ui-btn ui-btn-success',
                        events: {
                            click: function () {
                                BX.Otus.BeginDateButton.startDay();
                            }
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: BX.message('OTUS_MAIN_BEGIN_DATE_BUTTON_CANCEL'),
                        className: 'ui-btn ui-btn-light-border',
                        events: {
                            click: function () {
                                if (BX.Otus.BeginDateButton.popup) {
                                    BX.Otus.BeginDateButton.popup.close();
                                }
                            }
                        }
                    })
                ]
            });

            this.popup.show();
        },

        startDay: function () {
            if (this.requestRunning) {
                return;
            }

            this.requestRunning = true;

            BX.ajax.runAction('otus:main.TimemanActions.Timeman.startDay', {
                data: {
                    mode: this.currentMode
                }
            }).then(
                function () {
                    BX.Otus.BeginDateButton.requestRunning = false;

                    if (BX.Otus.BeginDateButton.popup) {
                        BX.Otus.BeginDateButton.popup.close();
                    }

                    window.location.reload();
                },
                function (response) {
                    BX.Otus.BeginDateButton.requestRunning = false;

                    if (window.console && response) {
                        console.log(response);
                    }

                    alert(BX.message('OTUS_MAIN_BEGIN_DATE_BUTTON_ERROR'));
                }
            );
        },

        patchTimeMan: function () {
            if (
                this.isPatched
                || !BX.CTimeMan
                || !BX.CTimeMan.prototype
                || typeof BX.CTimeMan.prototype.OpenDay !== 'function'
                || typeof BX.CTimeMan.prototype.ReOpenDay !== 'function'
            ) {
                return;
            }

            BX.CTimeMan.prototype.OpenDay = function (event) {
                BX.Otus.BeginDateButton.showPopup('open');
                return BX.PreventDefault(event);
            };

            BX.CTimeMan.prototype.ReOpenDay = function (event) {
                BX.Otus.BeginDateButton.showPopup('reopen');
                return BX.PreventDefault(event);
            };

            this.isPatched = true;

            if (this.patchTimer) {
                clearInterval(this.patchTimer);
                this.patchTimer = null;
            }
        },

        init: function () {
            this.patchTimeMan();

            if (!this.isPatched) {
                this.patchTimer = setInterval(function () {
                    BX.Otus.BeginDateButton.patchAttempts++;
                    BX.Otus.BeginDateButton.patchTimeMan();

                    if (!BX.Otus.BeginDateButton.isPatched && BX.Otus.BeginDateButton.patchAttempts >= 40) {
                        clearInterval(BX.Otus.BeginDateButton.patchTimer);
                        BX.Otus.BeginDateButton.patchTimer = null;
                    }
                }, 300);
            }
        }
    };

    BX(function () {
        BX.Otus.BeginDateButton.init();
    });
})(window, window.BX);
