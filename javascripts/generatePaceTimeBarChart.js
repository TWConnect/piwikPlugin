(function ($, require) {
    $(document).ready(function () {
        var paceTimeCount = new ajaxHelper();

        paceTimeCount.addParams({
            module: 'API',
            method: 'SearchMonitor.getPaceTimeOnSearchResultDistribution',
            format: 'json'
        }, 'get');
        paceTimeCount.setCallback(function (response) {
            var parsedObj = response;
            var labels = parsedObj.map(function (e) {
                return e['label']
            });
            var count = parsedObj.map(function (e) {
                return parseFloat(e['Count'])
            });
            var ctx = document.getElementById("pace_time_bar").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Search (times)',
                            data: count,
                            backgroundColor: "red",
                            borderColor: "red"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
        paceTimeCount.send(false);
    });

})($, require);