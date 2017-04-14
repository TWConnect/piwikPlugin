$(document).ready(function () {
    function getTopSearchKeywords() {
        var config = {attributes: true, childList: true, characterData: true};
        var observer = new MutationObserver(function () {
            $('#keyword-list .dataTable > div.dataTableWrapper > table > tbody > tr').unbind('click', getKeywordRelatedInfo);
            $('#keyword-list .dataTable > div.dataTableWrapper > table > tbody > tr').bind('click', getKeywordRelatedInfo);
        });
        var ajaxRequest = new ajaxHelper();
        ajaxRequest.setLoadingElement('#ajaxLoadingMyTWKeywords');
        ajaxRequest.addParams({
            module: 'SearchMonitor',
            action: 'getSearchKeywords'
        }, 'get');
        ajaxRequest.setCallback(
            function (response) {
                $('#keyword-list').html(response);
                observer.observe(jQuery("#keyword-list")[0], config);
            }
        );
        ajaxRequest.setFormat('html');
        ajaxRequest.send(false);
    }

    function getKeywordRelatedInfo(event) {
        var span = jQuery(event.currentTarget.cells[0]).find('span .value');
        $('#keyword-list .dataTable > div.dataTableWrapper > table > tbody > tr td').css("background-color", "white")
        $(event.currentTarget.cells[0]).css("background-color", "#ddd");
        $(event.currentTarget.cells[1]).css("background-color", "#ddd");
        $('.info').css('background','url(https://i.stack.imgur.com/kOnzy.gif) 50% no-repeat');
        $('.info').css('background-size','100px');
        clearTable();
        keyword = span.text();
        var ajaxRequest = new ajaxHelper();
        ajaxRequest.setLoadingElement('#ajaxLoadingMyTWKeywords');
        ajaxRequest.addParams({
            module: 'API',
            method: 'SearchMonitor.getKeywordRelatedInfo',
            reqKeyword: keyword,
            format: 'json'
        }, 'get');
        ajaxRequest.setCallback(
            function (response) {
                var result = [];
                var data = JSON.parse(response);
                data.forEach(function (item) {
                    // console.log(item);
                    result.push([item.url, item.type, item.count]);
                });

                showRelatedInfo(result);
            }
        );
        ajaxRequest.setFormat('html');
        ajaxRequest.send(false);
    }

    function clearTable() {
        $("#people-url-list").html("");
        $("#content-url-list").html("");
        $("#group-url-list").html("");
    }

    function showRelatedInfo(result) {
        clearTable();
        $('.info').css('background','white');
        $('#content-url-list').append("<tr><td>CONTENT</td><td>COUNT</td></tr>");
        $('#group-url-list').append("<tr><td>GROUP</td><td>COUNT</td></tr>");
        $('#people-url-list').append("<tr><td>PEOPLE</td><td>COUNT</td></tr>");
        var contentCount = 0;
        var peopleCount = 0;
        var groupCount = 0;
        result.forEach(function (elem) {
            switch (elem[1]) {
                case 'content':
                    contentCount++;
                    if (contentCount <= 10) {
                        $('#content-url-list').append("<tr><td><a href='" + elem[0] + "'>" + elem[0] + "</a></td><td>" + elem[2] + "</td></tr>");
                    }
                    break;
                case 'group':
                    groupCount++;
                    if (contentCount <= 10) {
                        $('#group-url-list').append("<tr><td><a href='" + elem[0] + "'>" + elem[0] + "</a></td><td>" + elem[2] + "</td></tr>");
                    }
                    break;
                case 'people':
                    peopleCount++;
                    if (contentCount <= 10) {
                        $('#people-url-list').append("<tr><td><a href='" + elem[0] + "'>" + elem[0] + "</a></td><td>" + elem[2] + "</td></tr>");
                    }
                    break;
                default:
                    break;
            }
        });

        showInfo(undefined, '#content-url-list')
    }

    getTopSearchKeywords();

});

var showInfo = function (event, type) {
    var i, tabcontent, tablinks;
    tabcontent = $(".tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = $(".tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    $(type).css("display", "block");
    if (event !== undefined) {
        event.currentTarget.className += " active";
    } else {
        $("#contentInfo").addClass("active");
    }
};