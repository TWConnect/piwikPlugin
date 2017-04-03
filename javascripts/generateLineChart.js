(function ($, require) {
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
    });

})($, require);