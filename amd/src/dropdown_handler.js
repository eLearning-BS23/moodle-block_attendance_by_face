import $ from "jquery";
export const init = (redirectUrl) => {
    // $('.give-attendance').on("click", function () {
    $('#data_table').on("click", '.give-attendance', function () {
        let userId = $(this).attr('id');
        let sessionId = document.getElementById(`select-${userId}`).value;
        if (sessionId && userId) {

            window.location.replace(
                redirectUrl + "&session_id=" + sessionId + "&id=" + userId
            );
        } else {
            window.location.replace(redirectUrl);
        }
    });
}