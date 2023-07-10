$(document).ready(function () {
    const API_URL = 'http://gre2.test/api/'

    // reset all modal inputs before opening the modal
    $('.form-modal').on('show.bs.modal', function (e) {
        resetForm($(this).find('form').attr('id'))
    })

    $(document).on('click', '.form-submit-btn', function (ev) {
        ev.preventDefault()
        $(`form#${$(this).data('form-id')}`).submit()
    })

    // hide all errors if user attemps to enter any value
    $(document).on('keyup', '.form-modal input', function (ev) {
        $('.form-modal .alert').css('display', 'none')
        $('.form-modal #login-email-error, .form-modal #login-password-error').css('display', 'none')
        $('.form-modal .email-group, .form-modal .password-group').removeClass('has-error')
    })

    // handle login form submission
    $(document).on('submit', '#login-form', function (ev) {
        ev.preventDefault()

        // validate the form
        let valid = true
        const email = $('#login-email').val()
        const password = $('#login-password').val()

        if (!email || !email.length || !isValidEmail(email)) {
            $(this).find('.email-error').css('display', 'block')
            $(this).find('.email-group').addClass('has-error')

            valid = false
        }

        if (!password || !password.length) {
            $('#login-form .password-group').addClass('has-error')
            valid = false
        }

        // stop submit event if form is not valid
        if (!valid) return

        // form is valid
        $.ajax({
            url: API_URL + 'login.php',
            method: 'POST',
            contentType: 'application/json',
            accepts: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                email,
                password
            })
        })
            .success(function (data) {
                if (!data) {
                    $('#login-error-alert').css('display', 'block')
                    return
                }

                if (data.success === false && data.info.length) {
                    $('#login-error-alert').css('display', 'block')
                    $('#login-error-alert #content').text(data.info)
                    return
                }

                $('#login-success-alert').css('display', 'block')
                setTimeout(() => $('.form-modal').modal('hide'), 1500)
            })
            .error(function (data) {
                $('#login-error-alert').css('display', 'block')
            })
            .done(function () {
                resetForm('login-form')
            })
    })

    // handle signup form submission
    $(document).on('submit', '#signup-form', function (ev) {
        ev.preventDefault()

        // validate the form
        let valid = true
        const userName = $(this).find('.user-name').val()
        const email = $(this).find('.user-email').val()
        const password = $(this).find('.user-password').val()

        if (!email || !email.length || !isValidEmail(email)) {
            $(this).find('.email-error').css('display', 'block')
            $(this).find('.email-group').addClass('has-error')

            valid = false
        }

        if (!password || !password.length) {
            $(this).find('.password-group').addClass('has-error')
            valid = false
        }

        // stop submit event if form is not valid
        if (!valid) return

        // form is valid
        $.ajax({
            url: API_URL + 'register.php',
            method: 'POST',
            contentType: 'application/json',
            accepts: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                email,
                password,
                username: userName
            })
        })
            .success(function (data) {
                if (!data) {
                    $('#signup-error-alert').css('display', 'block')
                    return
                }

                if (data.success === false && data.info.length) {
                    $('#signup-error-alert').css('display', 'block')
                    $('#signup-error-alert #content').text(data.info)
                    return
                }

                $('#signup-success-alert').css('display', 'block')
                setTimeout(() => $('.form-modal').modal('toggle'), 1500)
            })
            .error(function (data) {
                $('#signup-error-alert').css('display', 'block')
            })
            .done(function () {
                resetForm('signup-form')
            })
    })

    /**
     * check if email is valid
     *
     * @param {string} email
     * @returns bool
     */
    function isValidEmail(email) {
        return !!email.match(/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)
    }

    /**
     * reset form
     *
     * @param {string} formSelector
     */
    function resetForm(formSelector) {
        $(`#${formSelector}`).find('input').val('')
        $(`#${formSelector}`).find('.help-block').css('display', 'none')
        $(`#${formSelector}`).find('.alert').css('display', 'none')
    }
})
