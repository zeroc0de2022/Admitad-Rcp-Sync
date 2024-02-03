function validator(str, rule) {
    let validatorRules = {
        user_email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        user_name: /^[a-zA-ZА-яёЁ\s]{2,}$/,
        user_login: /^[a-z0-9_]{4,}$/,
        password: /^\S+$/,
        // tool_name: /^[a-zA-Zа-яёА-ЯЁ0-9\s-_\.]{2,}$/,
        tool_name: /^([a-zA-ZА-яёЁ0-9-_]{2,}\.[a-zA-ZА-яёЁ0-9-_.]{2,})$/,
        modal_tool_name: /^([\wА-яёЁ\-]{2,}\.[\wА-яёЁ\-.]{2,})$/,
        user_status: /^(admin|user|banned)$/,
        url: /^(https*:\/\/[\w-]+[.\w]+\/[\w\/?=&%._+\-]*)$/,
        remote_link: /^(https*:\/\/[\w-]+[.\w]+\/[\w\/?=&%._+\-]*)$/,
        modal_remote_link: /^(https*:\/\/[\w-]+[.\w]+\/[\w\/?=&%._+\-]*)$/
    };
    return validatorRules[rule].test(str);
}


function unlockButton(objForm, btnName){
    Object.values(objForm).forEach(function (value) {
        if (value === true) {
            $("#" + btnName).removeAttr('disabled');
        } else {
            $("#" + btnName).attr('disabled', 'true');
        }
    });
    return true;
}


function isExist(col, val) {
    return new Promise(function (resolve, reject) {
        $.ajax({
            url: '/verify',
            method: 'post',
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            dataType: 'json',
            data: {[col]: val},
            success: function (data) {
                if (data.status) {
                    $("#" + col + "_notice").html('<b class="text-danger"> already exists</b>');
                    resolve(false);
                } else {
                    $("#" + col + "_notice").html('<b class="text-success"> availiable</b>');
                    resolve(true);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX request failed:", textStatus, errorThrown);
                reject(Error("AJAX request failed"));
            }
        });
    });
}


function checkVerify(obj, btnName) {

    for (let key in obj) {
        (function (key) {
            $("#" + key).change(function () {
                let value = $("#" + key).val();
                if (validator(value, key)) {
                    switch (key) {
                        case "user_email":
                        case "user_login":
                        case "remote_link":
                        case "tool_name":
                        case "modal_tool_name":
                        case "modal_remote_link": {
                            isExist(key, value).then(function (result) {
                                obj[key] = result;
                                unlockButton(obj, btnName);
                                //console.log(key + ': ' + verifyForm[key]);
                            }).catch(function (error) {
                                console.log(error);
                            });
                        }
                            break;
                        default : {
                            obj[key] = true;
                            unlockButton(obj, btnName);
                            $("#" + key + "_notice").html('');
                            //console.log(key + ': ' + verifyForm[key]);
                        }
                    }
                } else {
                    obj[key] = false;
                    $("#" + key + "_notice").html('<b class="text-danger"> Invalid type</b>');
                    unlockButton(obj, btnName);
                    //console.log(key + ': ' + verifyForm[key]);
                }
            });
        })(key);
    }
}