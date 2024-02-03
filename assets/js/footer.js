function setTool(value){
    let piObj = {
        importer: {
            remote_link_label: "remote link to importer",
            tool_name_placeholder: "site.by",
            remote_link_placeholder: "https://site.by/importer/",
            help_block: "Address of the directory with the unloaded importer"
        },
        tool: {
            remote_link_label: "Link to download file",
            tool_name_placeholder: "site.by",
            remote_link_placeholder: "https://export.admitad.com/ru/webmaster/websites/000000/products/export_adv_products/?user=user&code=f000f00000&template=00000&currency=BYN&feed_id=00000&last_import=",
            help_block: "Upload format: CSV"
        }
    };
    $("#modal_tool_name").attr("placeholder", piObj[value]['tool_name_placeholder']);
    $("#modal_remote_link").attr("placeholder", piObj[value]['remote_link_placeholder']);
    $("#remote_link_label").html(piObj[value]['remote_link_label']);
    $("#help-block").html(piObj[value]['help_block']);
}


function addToolModal(toolname){
    let tool = $("#modal_tool").val();
    let tool_name = $("#modal_tool_name").val();
    let remote_link = $("#modal_remote_link").val();
    let params = {
        tool_name: tool_name,
        remote_link: remote_link,
        tool: tool,
        action: "newTool"
    };
    $.ajax({
        url: window.location.href,
        method: 'post',
        dataType: 'json',
        data: params,
        success: function (data){
            console.log(data);
            if (data.status) {
                window.location.replace(window.location.origin + "/" + tool + "s/" + tool_name);
            } else {
                alert(data.message);
            }
        }
    });
}

addToolInput = {modal_tool_name: false, modal_remote_link: false};
$(document).ready(function()
{
    checkVerify(addToolInput, 'addToolButton');
});
$('.tooltip-demo').tooltip({
    selector: "[data-toggle=tooltip]",
    container: "body"
});
$("[data-toggle=popover]").popover();
