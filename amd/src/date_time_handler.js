import $ from "jquery";
export const init = (from, to, sort, site_url) => {
    
    console.log(sort);

    const fromMS = from * 1000; // Converting to milliseconds.
    const fromDateObject = new Date(fromMS);
    from = fromDateObject.toLocaleString();

    const toMS = to * 1000;  // Converting to milliseconds.
    const toDateObject = new Date(toMS);
    to = toDateObject.toLocaleString();

    console.log(from);
    console.log(to);
    $('#from_date').val(from);
    $('#to_date').val(to);
    if(sort === 'ASC') {
        $('#asc').prop('checked', true);
    }else {
        $('#desc').prop('checked', true);
    }

    $('#date_check').on("click", function () {
        console.log($('#from_date').val());
        let dateStr = $('#from_date').val();
        let date = new Date(dateStr);
        console.log(Math.floor(date.getTime()/1000));
        from = Math.floor(date.getTime()/1000);

        console.log($('#to_date').val());
        let dateStr1 = $('#to_date').val();
        let date1 = new Date(dateStr1);
        console.log(Math.floor(date1.getTime()/1000));
        to = Math.floor(date1.getTime()/1000);

        sort = document.querySelector("input[type='radio'][name=sortOrder]:checked").value;
        if (from && to) {

            window.location.replace(
                site_url + "&from=" + from + "&to=" + to + "&sort=" + sort
            );
        } else {
            window.location.replace(site_url);
        }
      
        });
};