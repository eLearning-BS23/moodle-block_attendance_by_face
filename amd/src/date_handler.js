import $ from "jquery";
export const init = (fromMonth, fromDay, fromYear, toMonth, toDay, toYear, site_url) => {
  fromYear = String(fromYear).padStart(4, "20");
  fromMonth = String(fromMonth).padStart(2, "0");
  fromDay = String(fromDay).padStart(2, "0");
  console.log(fromDay);

  toYear = String(toYear).padStart(4, "20");
  toMonth = String(toMonth).padStart(2, "0");
  toDay = String(toDay).padStart(2, "0");

  let fromDatePattern = fromYear + "-" + fromMonth + "-" + fromDay;
  let toDatePattern = toYear + "-" + toMonth + "-" + toDay;

  let fromDate = document.getElementById("from_date");
  fromDate.value = fromDatePattern;

  let toDate = document.getElementById("to_date");
  toDate.value = toDatePattern;
  console.log(toDate.value);
  console.log(site_url);
  $("#date_check").on("click", function () {
    if (fromDate.value && toDate.value) {
      let from = fromDate.value;
      from = from.split("-");
      fromYear = from[0];
      fromMonth = from[1];
      fromDay = from[2];

      let to = toDate.value;
      to = to.split("-");
      toYear = to[0];
      toMonth = to[1];
      toDay = to[2];
      console.log(toDay);

      window.location.replace(
        site_url + "&fm=" + fromMonth + "&fd=" + fromDay + "&fy=" + fromYear + "&tm=" + toMonth + "&td=" + toDay + "&ty=" + toYear
      );
    } else {
      window.location.replace(site_url);
    }
  });
};
