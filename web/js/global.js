function showWaiter() {
    let shader = $('<div class="shader"><div class="col-sm-12 text-center shader-status"></div></div>');
    $('body').append(shader).css({'overflow': 'hidden'});
    $('div.wrap, div.modal').addClass('blured');
    shader.showLoading();
}

function deleteWaiter() {
    $('div.wrap, div.modal').removeClass('blured');
    $('body').css({'overflow': ''});
    let shader = $('div.shader');
    if (shader.length > 0)
        shader.hideLoading().remove();
}

function makeInformerModal(header, text, acceptAction, declineAction) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose"><div class="modal-dialog text-center"><div class="modal-content"><div class="modal-header"><h3>' + header + '</h3></div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-success" type="button" id="acceptActionBtn">Ок</button></div></div></div>');
    $('body').append(modal);
    let acceptButton = modal.find('button#acceptActionBtn');
    modal.on('shown.bs.modal', function () {
        acceptButton.focus();
    });
    if (declineAction) {
        let declineBtn = $('<button class="btn btn-warning" role="button">Отмена</button>');
        declineBtn.insertAfter(acceptButton);
        declineBtn.on('click.custom', function () {
            modal.modal('hide');
            declineAction();
        });
    }
    modal.modal({
        keyboard: false,
        backdrop: 'static',
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        modal.remove();
    });

    acceptButton.on('click', function () {
        modal.modal('hide');
        if (acceptAction) {
            acceptAction();
        } else {
            location.reload();
        }
    });

    return modal;
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let header = data['header'] ? data['header'] : "Успешно";
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal(
                header,
                message,
                (data.reload ? function () {
                    location.reload();
                } : function () {
                })
            );
        }
    }
}

// ========================================================== ИНФОРМЕР
// СОЗДАЮ ИНФОРМЕР
function makeInformer(type, header, body) {
    if (!body)
        body = '';
    const container = $('div#alertsContentDiv');
    const informer = $('<div class="alert-wrapper"><div class="alert alert-' + type + ' alert-dismissable my-alert"><div class="panel panel-' + type + '"><div class="panel-heading">' + header + '<button type="button" class="close">&times;</button></div><div class="panel-body">' + body + '</div></div></div></div>');
    informer.find('button.close').on('click.hide', function (e) {
        e.preventDefault();
        closeAlert(informer)
    });
    container.append(informer);
    showAlert(informer)
}

// ПОКАЗЫВАЮ ИНФОРМЕР
function showAlert(alertDiv) {
    // считаю расстояние от верха страницы до места, где располагается информер
    const topShift = alertDiv[0].offsetTop;
    const elemHeight = alertDiv[0].offsetHeight;
    let shift = topShift + elemHeight;
    alertDiv.css({'top': -shift + 'px', 'opacity': '0.1'});
    // анимирую появление информера
    alertDiv.animate({
        top: 0,
        opacity: 1
    }, 500, function () {
        // запускаю таймер самоуничтожения через 5 секунд
        /*setTimeout(function () {
            closeAlert(alertDiv)
        }, 5000);*/
    });

}

// СКРЫВАЮ ИНФОРМЕР
function closeAlert(alertDiv) {
    const elemWidth = alertDiv[0].offsetWidth;
    alertDiv.animate({
        left: elemWidth
    }, 500, function () {
        alertDiv.animate({
            height: 0,
            opacity: 0
        }, 300, function () {
            alertDiv.remove();
        });
    });
}

// сериализация объектов формы
function serialize(obj) {
    const str = [];
    for (let p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

// отправка ajax-запроса
function sendAjax(method, url, callback, attributes, isForm) {
    showWaiter();
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            deleteWaiter();
            callback(e);
        }).fail(function () {
            deleteWaiter();
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            deleteWaiter();
            callback(e);
        }).fail(function () {
            deleteWaiter();
        });
    }
}

// навигация по табам
function enableTabNavigation() {
    let url = location.href.replace(/\/$/, "");
    if (location.hash) {
        const hash = url.split("#");
        $('a[href="#' + hash[1] + '"]').tab("show");
        url = location.href.replace(/\/#/, "#");
        history.replaceState(null, null, url);
    }

    $('a[data-toggle="tab"]').on("click", function () {
        let newUrl;
        const hash = $(this).attr("href");
        if (hash === "#home") {
            newUrl = url.split("#")[0];
        } else {
            newUrl = url.split("#")[0] + hash;
        }
        history.replaceState(null, null, newUrl);
    });
}

// обработка активаторов AJAX-запросов =================================================================================
function handleAjaxActivators() {
    "use strict";
    // найду активаторы AJAX-запросов
    let activators = $('.activate');
    activators.off('click.request');
    activators.on('click.request', function () {
        let action = $(this).attr('data-action');
        if (action) {
            sendAjax(
                "get",
                action,
                simpleAnswerHandler
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
}