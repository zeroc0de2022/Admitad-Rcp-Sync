// Remove Tool from database
function removeItem(itemName, id, title, rule) {
    let tool = $("#tool").val();
    let conf = confirm("Are you sure you wanna " + title + "?");
    if (conf) {
        let params = {};
        params.action = "remove" + itemName;
        params.id = id;
        if (itemName === 'Tool') {
            params.rule = rule;
        }
        $.ajax({
            url: '',
            method: 'post',
            dataType: 'json',
            data: params,
            success: function (data) {
                alert(data.message);
                if (data.status) {
                    if (itemName === 'Tool') {
                        window.location.replace("/" + tool + "s/");
                    } else {
                        window.location.reload();
                    }
                }
            }
        });
    }
}

// Start parsing
function startParse(action, tool_name, button_id) {
    let conf = confirm("Are you sure you want start parsing?");
    if (conf) {
        let startButton = $("#" + button_id);
        startButton.attr("disabled", "true");
        startButton.html("Parser is active");
        window.open('/pars/?tool_name=' + tool_name + '&action=' + action + '&run=manual', 'Parser', 'fullscreen=yes,status=yes,toolbar=no,menubar=no,location: no;');
    }
}

// Update config
function editToolConf(input_, action) {
    let name = input_.name;
    let value = 1;
    // check link
    if (action === "editTool") {
        value = input_.value;
        if (!validator(value, name)) {
            alert("Invalid format of link to upload file");
            return;
        }
    } else {
        switch (input_.tagName) {
            case "INPUT": {
                if (input_.type === "checkbox")
                    value = ($(input_).prop("checked")) ? 1 : 0;
                else
                    value = input_.value;
            }
                break;
            case "SELECT": {
                value = input_.value;
            }
                break;
        }
        // switch button
        if (name === "local_csv_status" || name === "local_product_status") {
            if (value) {
                $("#manual_" + name).removeAttr("disabled");
            } else {
                $("#manual_" + name).attr("disabled", true);
            }
        }
    }
    let params = {
        action: action,
        tool_name: $("#tool_name_hid").val(),
        name: name,
        value: value
    };
    $.ajax({
        url: '',
        method: 'post',
        dataType: 'json',
        data: params,
        success: function (data) {
        }
    });
}


// setting up hints
function cronHelpBlock(value) {
    let helpBlock = {
        local_csv: "Updating/Adding products to the local database from the donor CSV file",
        local_product: "Parsing add-info about products (description, gallery, attributes, reviews) into a local database"

    };
    $("#cron-help-block").html(helpBlock[value]);
}

// setting up popover hints
function setPopoverText(id) {
    let toggleText = {
        "toggle-min": "<b>Every minute</b><br/>- no comment" +
            "<br><b>Every N minutes</b><br/>- <b>5</b> = every 5 minutes<br/>- <b>10</b> = every 10 minutes, etc." +
            "<br/><b>Set minutes</b><br/>- <b>3</b> = at the 3rd minute of every hour<br/>- <b>1.15</b> = at every 1 and 15 minutes of the hour<br>- <b>1-23</b> = every minute from 1 to 23 minutes of the hour ",
        "toggle-hour": "<b>Every hour</b><br/>- no comment" +
            "<br><b>Every N hours</b><br/>- <b>5</b> = every 5 hours<br/>- <b>10</b> = every 10 hours, etc." +
            "<br/><b>Set hours</b><br/>- <b>5</b> = at 5 o'clock<br/>- <b>1.15</b> = at 1 and 15 hours<br>- <b>1-23</b> = every hour in the range from 1 to 23 hours",
        "toggle-day": "<b>Every day</b><br/>- no comments" +
            "<br><b>Every N days</b><br/>- <b>5</b> = every 5 days<br/>- <b>10</b> = every 10 days, etc." +
            "<br/><b>Set days</b><br/>- <b>5</b> = on the 5th day of the month<br/>- <b>1.15</b> = 1st and 15th of every month<br>- <b>1-23</b> = every day of the month in the range from 1st to 23rd",
        "toggle-month": "<b>Every month</b><br/>- no comments" +
            "<br/><b>Certain months</b><br/>- <b>5</b> = in May (5th month)<br/>- <b>1.12</b > = 1 and 12 months<br>- <b>1-3</b> = from 1 to 3 months",
        "toggle-weekday": "<b>Every week</b><br/>- no comments" +
            "<br/><b>Specific days of the week</b><br/>- <b>1</b> = on Mondays<br/>- <b>1.7</b> = on every 1 and 7th day of the week<br>- <b>1-3</b> = Mondays, Tuesdays and Wednesdays"
    };
    let toggleContent = $("#" + id);
    toggleContent.attr("data-content", toggleText[id]);
}

// set up cron selectors
function addDiv(id, value, selects) {
    for (let select of selects) {
        let wrapperId = select + "-" + id + "-wrapper";
        let wrapper = document.getElementById(wrapperId);
        if (wrapper) {
            if (value === select + "-" + id) {
                wrapper.style.display = 'block';
                if (wrapper.hasAttribute("disabled")) {
                    wrapper.removeAttribute("disabled");
                }
                wrapper.setAttribute("required", "true");
                wrapper.focus();
            } else {
                wrapper.style.display = 'none';
                if (wrapper.hasAttribute("required")) {
                    wrapper.removeAttribute("required");
                }
                wrapper.setAttribute("disabled", "true");
            }
        }
    }
}
