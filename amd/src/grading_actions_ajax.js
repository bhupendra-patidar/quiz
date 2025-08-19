define([
    "jquery",
    "core/ajax",
    "core/templates",
    "core/notification",
    "core/str",
], function ($, ajax, templates, notification,str) {
    return {
        init: function() {
            $('#id_fill_ai_grade').on('click', function() {

                var url = window.location.href;
                var attemptid = getURLParameter("attempt", url);
                var slot = getURLParameter("slot", url);
                var qid = $(this).attr('data-qid');

                console.log('USER asdada asdad sdsf sdsd');
                console.log(attemptid);
                //console.log(slot);
                console.log(qid);

                $.ajax({
                    url: M.cfg.wwwroot + '/local/quiz/ajax.php',
                    method: 'POST',
                    data: {
                        action: 'getgrades',
                        attemptid: attemptid,
                        qid: qid,
                        sesskey: M.cfg.sesskey
                    },
                    success: function (data) {
                        try {
                            if (data.status) {
                                if (data.errormessage) {
                                    notification.alert(
                                        'Grading Issue',
                                        `There is some issue with the following message: <strong>${data.errormessage}</strong>. Kindly grade this manually.`,
                                        'OK'
                                    );
                                } else {
                                    const editorDiv = $('#q'+attemptid+'\\:'+slot+'_-comment_ideditable')[0];
                                    if (editorDiv) {
                                        editorDiv.focus();
                                        editorDiv.click();
                                        editorDiv.innerHTML = data.feedback;
                                        editorDiv.dispatchEvent(new Event('input', { bubbles: true }));
                                    }

                                    $('#q'+attemptid+'\\:'+slot+'_-mark').val(data.grade); // Use response grade if available
                                }
                            } else {
                                notification.alert('Error', 'There is some issue. Please try again later.', 'OK');
                            }
                        } catch (err) {
                            notification.exception(err);
                        }
                    },
                    error: function (err) {
                        notification.alert('AJAX Error', err.statusText || 'Unknown error', 'OK');
                    }
                });
            });
        }
    };

    function getURLParameter(name, url) {
        return (
                decodeURIComponent(
                        (new RegExp("[?|&]" + name + "=" + "([^&;]+?)(&|#|;|$)").exec(
                                url
                                ) || [null, ""])[1].replace(/\+/g, "%20")
                        ) || null
                );
    }
});
