function handleTestSend() {
    let form = $('form#testSendForm');
    form.on('submit.send', function (e) {
        console.log('sending');
        e.preventDefault();
        sendAjax(
            'post',
            '/management-actions/test-send',
            simpleAnswerHandler,
            form,
            true
        )
    });
}

$(function () {
    handleAjaxActivators();
    enableTabNavigation();
    handleTestSend();
});