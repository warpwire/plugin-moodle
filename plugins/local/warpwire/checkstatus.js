function warpwireCheckStatus(ajax, warpwireStatusContainer, warpwireTrialButton) {
    ajax.call([
        {
            methodname: 'local_warpwire_check_setup_status',
            args: {},
            done: function(data) {
                warpwireStatusContainer.innerText = data.status + ': ' + data.status_message;

                if (data.status != 'Queued' && data.status != 'Notstarted' && data.status != 'Processing' && data.status != 'Unknown') {
                    window.location.reload();
                }

                setTimeout(function() {
                    warpwireCheckStatus(ajax, warpwireStatusContainer, warpwireTrialButton);
                }, 5000);
            },
            fail: function(ex) {
                console.error(ex);
                warpwireStatusContainer.innerText = 'ERROR';
            }
        }
    ]);
}

(function() {
    window.addEventListener('DOMContentLoaded', function() {
        var warpwireStatusContainer = document.getElementById('warpwire_status_container');
        var warpwireTrialButton = document.getElementById('warpwire_trial_button');

        setTimeout(function() {
            require(['core/ajax'], function(ajax) {
                warpwireCheckStatus(ajax, warpwireStatusContainer, warpwireTrialButton);
            });
        }, 10);
    });
})();
