<script>
    {literal}

    function getHeight() {
        return jQuery(window).height() - jQuery('h1').outerHeight(true);
    }

    function sizeFormatter(bytes, row, index, field) {
        if(bytes == 0) return '0 Bytes';
        var k = 1024,
            decimals = 2,
            sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
    }

    function dateTimeFormattter(value, row, index, field) {
        var date = new Date(value*1000);
        return date.toLocaleDateString() + " " + date.toLocaleTimeString();
    }
</script>
{/literal}

{include file="$integrity_template"}
{include file="$malware_template"}
