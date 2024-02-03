var verifyForm = {
    user_login: false,
    user_email: false,
    user_name: false,
    password: false
};

function addNewUser(form) {
    if (!unlockButton(verifyForm, "addNewUserButton")) {
        alert("Check the form for errors");
        return false;
    }
    const params = {
        hash: form.hash.value,
        action: form.action.value,
        user_status: form.user_status.value,
        user_name: form.user_name.value,
        user_login: form.user_login.value,
        user_email: form.user_email.value,
        password: form.password.value
    };
    $.ajax({
        url: '',
        method: 'post',
        dataType: 'json',
        data: params,
        success: function (data) {
            alert(data.message);
            if (data.status) {
                location.reload();
            }
        }
    });
    return false;
}

function editUser(action, colname, user_id, value) {
    if (action === "delete" && !confirm("Do you really want to delete " + user_id + " ?")) {
        return false;
    }
    if ((action === "edit" && validator(value, colname)) || action === "delete") {
        const params = {
            action: action,
            colname: colname,
            value: value,
            user_id: user_id,
            hash: hash
        };
        $.ajax({
            url: '',
            method: 'post',
            dataType: 'json',
            data: params,
            success: function (data) {
                if (params.action === "delete") {
                    location.reload();
                } else if (params.action === "edit") {
                    let leart = data.status === false ? "danger" : "success";
                    $("#notice").html('<div class="alert alert-' + leart + ' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + data.message + '</div>');
                }
            }
        });
    } else {
        $("#notice").html('<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>Invalid type.</div>');
    }
}