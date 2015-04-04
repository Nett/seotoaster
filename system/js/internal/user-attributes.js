$(function(){

    if($('input[name="user-attribute-submit"]').length && $('form[name="user-attribute-form"]').length) {
        $('input[name="user-attribute-submit"]').on('click', function(e) {
            e.preventDefault();
            var currentForm = $(this).parents('form[name="user-attribute-form"]');
            var data = {formData: currentForm.serialize()};
            var uid = currentForm.find('input.user-attribute, textarea.user-attribute, select.user-attribute')
                .filter(':first').attr('data-uid');
            sendUserAttributeRequest(data, uid);
        });
    }else {
        $('input.user-attribute').on('focus', function (e) {
            console.log('@TODO create backup');
        });
        $('input.user-attribute').on('keyup', function (e) {
            if (e.keyCode == 13) {
                $(this).trigger('blur');
                return false;
            }
        });
        $('input.user-attribute, textarea.user-attribute, select.user-attribute').on('change', function (e) {
            var data = {};
            data[$(this).data('attribute')] = $(this).val();
            sendUserAttributeRequest(data, $(this).data('uid'));
        });
    }

    function sendUserAttributeRequest(data, uid) {
        $.ajax({
            url: $('#website_url').val() + 'api/toaster/users/id/' + uid,
            method: 'PUT',
            data: JSON.stringify(data),
            complete: function (xhr, status, response) {
                if (status === 'error') {
                    showMessage(status, true);
                } else {

                }
            }
        })
    }
});