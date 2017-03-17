function getDateLabel(e, period) {
    var label = e['label'];

    if (period == 'month') {
        var mydate = new Date(label);
        var month = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"][mydate.getMonth()];
        label = month + ' ' + mydate.getFullYear();
    }
    return label
}
(function ($, require) {
    $(document).ready(function () {
        var startDateObj = new Date(piwik.startDateString);
        var minusDateNum = 30;
        var period = piwik.period;
        if (period == 'week') {
            minusDateNum = 140;
        } else if (period == 'month') {
            startDateObj.setDate(1);
            startDateObj.setMonth((startDateObj.getMonth() + 1) % 12 + 1);
            minusDateNum = 365;
        } else if (period == 'year') {
            minusDateNum = 1000;
        } else if (period == 'range') {
            minusDateNum = 0;
            period = 'day';
        }
        startDateObj.setDate(startDateObj.getDate() - minusDateNum);
        
        var formatDate = function (startDateObj) {
            var d = new Date(startDateObj), month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
            if (month.length < 2) 
                month = '0' + month;
            if (day.length < 2) 
                day = '0' + day;
            return [year, month, day].join('-');
        };
        
        var paceTimeAvgTime = new ajaxHelper();
        paceTimeAvgTime.addParams({
            module: 'API',
            method: 'SearchMonitor.getPaceTimeOnSearchResultTendency',
            format: 'json',
            date: formatDate(startDateObj) + "," + piwik.endDateString,
            period: period
        }, 'get');
        paceTimeAvgTime.setCallback(function (response) {
            var parsedObj = response;
            var labels = parsedObj.map(function (e) {
                return getDateLabel(e, period);
            });
            var average_time = parsedObj.map(function (e) {
                return Math.floor(parseFloat(e['avg_time_on_page']) * 100) / 100.0;
            });
            var ctx = document.getElementById("pace_time_line").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Avg Time (s)',
                            data: average_time,
                            fill: false,
                            lineTension: 0,
                            borderColor: "red",
                            borderWidth: 2,
                            backgroundColor: "rgba(255,0,0,0.7)"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        paceTimeAvgTime.send(false);

        var repeatingRateRequest = new ajaxHelper();
        repeatingRateRequest.addParams({
            module: 'API',
            method: 'SearchMonitor.getRepeatingSearchRate',
            format: 'json',
            date: formatDate(startDateObj) + "," + piwik.endDateString,
            period: period
        }, 'get');
        repeatingRateRequest.setCallback(function (response) {
            var parsedObj = response;
            var labels = parsedObj.map(function (e) {
                return getDateLabel(e, period);
            });
            var repeating_rate = parsedObj.map(function (e) {
                return Math.floor(parseFloat(e['repeating_rate']) * 10000) / 100.0;
            });
            var ctx = document.getElementById("chart1").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'repeating rate (%)',
                            data: repeating_rate,
                            fill: false,
                            lineTension: 0,
                            borderColor: "red",
                            borderWidth: 2,
                            backgroundColor: "rgba(255,0,0,0.7)"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        repeatingRateRequest.send(false);

        var repeatingCountRequest = new ajaxHelper();
        repeatingCountRequest.addParams({
            module: 'API',
            method: 'SearchMonitor.getRepeatingSearchCount',
            format: 'json',
            date: formatDate(startDateObj) + "," + piwik.endDateString,
            period: period
        }, 'get');
        repeatingCountRequest.setCallback(function (response) {
            var parsedObj = response;
            var labels = parsedObj.map(function (e) {
                return getDateLabel(e, period);
            });
            var repeating_search_count = parsedObj.map(function (e) {
                return e['repeating_search_count']
            });
            var total_search_count = parsedObj.map(function (e) {
                return e['total_search_count']
            });
            var ctx = document.getElementById("chart2").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'repeating search count',
                            data: repeating_search_count,
                            fill: false,
                            lineTension: 0,
                            borderColor: "red",
                            borderWidth: 2,
                            backgroundColor: "rgba(255,0,0,0.7)"
                        },
                        {
                            type: 'line',
                            label: 'total search count',
                            data: total_search_count,
                            fill: false,
                            lineTension: 0,
                            borderColor: "rgba(0,177,177,1)",
                            backgroundColor: "rgba(0,177,177,0.7)",
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        repeatingCountRequest.send(false);
    });

})($, require);