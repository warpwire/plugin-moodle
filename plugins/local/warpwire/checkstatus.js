(function() {
    window.addEventListener('DOMContentLoaded', function() {
        var warpwireStatusContainer = document.getElementById('warpwire_status_container');

        function warpwireCheckStatus(ajax) {
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
                            warpwireCheckStatus(ajax);
                        }, 5000);
                    },
                    fail: function(ex) {
                        console.error(ex);
                        warpwireStatusContainer.innerText = 'ERROR';
                    }
                }
            ]);
        }

        setTimeout(function() {
            require(['core/ajax'], function(ajax) {
                warpwireCheckStatus(ajax);
            });
        }, 10);
    });
})();
