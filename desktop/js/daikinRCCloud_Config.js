$('#bt_generateToken').on('click', function () {
    $.ajax({
        type: 'POST',
        url: 'plugins/daikinRCCloud/core/ajax/daikinRCCloud.ajax.php',
        data: {
            action: 'regenToken'
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error, $('#div_AboAlert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_AboAlert').showAlert({message: 'ERROR', level: 'danger'});
                return;
            }
            $('#div_AboAlert').showAlert({message: 'Le token a bien été mis a jour', level: 'success'});
        }
    });
});

$('#bt_generateEqLogics').on('click', function () {
    $.ajax({
        type: 'POST',
        url: 'plugins/daikinRCCloud/core/ajax/daikinRCCloud.ajax.php',
        data: {
            action: 'generateEqLogics'
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error, $('#div_AboAlert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_AboAlert').showAlert({message: 'ERROR', level: 'danger'});
                return;
            }
            $('#div_AboAlert').showAlert({message: 'Generation des équipements en cours veuillez patienter', level: 'success'});
        }
    });
});

