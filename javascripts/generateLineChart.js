(function ($, require) {
    $.holdReady(true);
    var s = setInterval(function () {
        if (typeof Chart === "function") {
            $.holdReady(false);
            clearInterval(s);

            $(document).ready(function () {

                var startDateObj = new Date(piwik.startDateString);
                var minusDateNum = 30;
                var period = piwik.period;
                if (period == 'week') {
                    minusDateNum = 140;
                } else if (period == 'month') {
                    startDateObj.setDate(1);
                    minusDateNum = 365;
                } else if (period == 'year') {
                    minusDateNum = 1000;
                } else if (period == 'range') {
                    minusDateNum = 0;
                    period = 'day';
                }
                startDateObj.setDate(startDateObj.getDate() - minusDateNum);

                var formatDate = function (startDateObj) {
                    var d = new Date(startDateObj);
                    var month = d.getMonth() + 1;
                    var day = d.getDate();
                    var year = d.getFullYear();
                    return [year, month, day].join('-');
                };

                var getDateLabel = function (e, period) {
                    var label = e['label'];
                    if (period == 'month') {
                        var date = new Date(label);
                        var month = ["January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"][date.getMonth()];
                        label = month + ' ' + date.getFullYear();
                    }
                    return label;
                };

                // var paceTimeAvgTime = new ajaxHelper();
                // paceTimeAvgTime.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getPaceTimeOnSearchResultTendency',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // paceTimeAvgTime.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var average_time = parsedObj.map(function (e) {
                //         return Math.floor(parseFloat(e['avg_time_on_page']) * 100) / 100.0;
                //     });
                //     var ctx = document.getElementById("pace_time_line");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'Avg Time (s)',
                //                     data: average_time,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 }
                //             ]
                //         },
                //         options: {
                //             scales: {
                //                 yAxes: [{
                //                     ticks: {
                //                         beginAtZero: true
                //                     }
                //                 }]
                //             },
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // paceTimeAvgTime.send(false);
                //
                // var repeatingRateRequest = new ajaxHelper();
                // repeatingRateRequest.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getRepeatingSearchRate',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // repeatingRateRequest.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var repeating_rate = parsedObj.map(function (e) {
                //         return Math.floor(parseFloat(e['repeating_rate']) * 10000) / 100.0;
                //     });
                //     var ctx = document.getElementById("chart1");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'repeating rate (%)',
                //                     data: repeating_rate,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 }
                //             ]
                //         },
                //         options: {
                //             scales: {
                //                 yAxes: [{
                //                     ticks: {
                //                         beginAtZero: true
                //                     }
                //                 }]
                //             },
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // repeatingRateRequest.send(false);
                //
                // var repeatingCountRequest = new ajaxHelper();
                // repeatingCountRequest.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getRepeatingSearchCount',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // repeatingCountRequest.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var repeating_search_count = parsedObj.map(function (e) {
                //         return e['repeating_search_count']
                //     });
                //     var total_search_count = parsedObj.map(function (e) {
                //         return e['total_search_count']
                //     });
                //     var ctx = document.getElementById("chart2");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'repeating search count',
                //                     data: repeating_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 },
                //                 {
                //                     type: 'line',
                //                     label: 'total search count',
                //                     data: total_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "rgba(0,177,177,1)",
                //                     backgroundColor: "rgba(0,177,177,0.7)",
                //                     borderWidth: 2
                //                 }
                //             ]
                //         },
                //         options: {
                //             scales: {
                //                 yAxes: [{
                //                     ticks: {
                //                         beginAtZero: true
                //                     }
                //                 }]
                //             },
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // repeatingCountRequest.send(false);

                // var bounceRateRequest = new ajaxHelper();
                // bounceRateRequest.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getBounceSearchRate',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // bounceRateRequest.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var bounce_search_rate = parsedObj.map(function (e) {
                //         return Math.floor(parseFloat(e['bounce_search_rate']) * 10000) / 100.0;
                //     });
                //     var ctx = document.getElementById("bounce_rate");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'Bounce Search Rate (%)',
                //                     data: bounce_search_rate,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 }
                //             ]
                //         },
                //         options: {
                //             scales: {
                //                 yAxes: [{
                //                     ticks: {
                //                         beginAtZero: true
                //                     }
                //                 }]
                //             },
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // bounceRateRequest.send(false);

                // var bounceCountRequest = new ajaxHelper();
                // bounceCountRequest.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getBounceSearchCount',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // bounceCountRequest.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var bounce_search_count = parsedObj.map(function (e) {
                //         return e['bounce_search_count']
                //     });
                //     var total_search_count = parsedObj.map(function (e) {
                //         return e['total_search_count']
                //     });
                //     var ctx = document.getElementById("bounce_count");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'Bounce Search Count',
                //                     data: bounce_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 },
                //                 {
                //                     type: 'line',
                //                     label: 'Total Search Count',
                //                     data: total_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "rgba(0,177,177,1)",
                //                     backgroundColor: "rgba(0,177,177,0.7)",
                //                     borderWidth: 2
                //                 }
                //             ]
                //         },
                //         options: {
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // bounceCountRequest.send(false);
                //
                // var bounceCountRequestWithoutSum = new ajaxHelper();
                // bounceCountRequestWithoutSum.addParams({
                //     module: 'API',
                //     method: 'SearchMonitor.getBounceSearchCountWithoutSum',
                //     format: 'json',
                //     date: formatDate(startDateObj) + "," + piwik.endDateString,
                //     period: period
                // }, 'get');
                // bounceCountRequestWithoutSum.setCallback(function (response) {
                //     var parsedObj = response;
                //     var labels = parsedObj.map(function (e) {
                //         return getDateLabel(e, period);
                //     });
                //     var bounce_search_count = parsedObj.map(function (e) {
                //         return e['bounce_search_count']
                //     });
                //     var total_search_count = parsedObj.map(function (e) {
                //         return e['total_search_count']
                //     });
                //     var ctx = document.getElementById("bounce_count_month");
                //     var myChart = new Chart(ctx, {
                //         type: 'line',
                //         data: {
                //             labels: labels,
                //             datasets: [
                //                 {
                //                     type: 'line',
                //                     label: 'Bounce Search Count',
                //                     data: bounce_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "red",
                //                     borderWidth: 2,
                //                     backgroundColor: "rgba(255,0,0,0.7)"
                //                 },
                //                 {
                //                     type: 'line',
                //                     label: 'Total Search Count',
                //                     data: total_search_count,
                //                     fill: false,
                //                     lineTension: 0,
                //                     borderColor: "rgba(0,177,177,1)",
                //                     backgroundColor: "rgba(0,177,177,0.7)",
                //                     borderWidth: 2
                //                 }
                //             ]
                //         },
                //         options: {
                //             responsive: true,
                //             maintainAspectRatio: false
                //         }
                //     });
                // });
                // bounceCountRequestWithoutSum.send(false);
                
            });
        }
        ;
    }, 1);
})($, require);