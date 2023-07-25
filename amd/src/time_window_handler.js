import $ from "jquery";
import Ajax from "core/ajax";

export const init = (userId) => {
  $(".stop-btn").on("click", function () {
    let elementId  = $(this).attr("id");
    console.log(elementId);
    let idArray = elementId.split("-");
    let courseId = idArray[0];
    let sessionId = parseInt(idArray[1]);
    console.log(courseId);
    console.log(sessionId);
    toggle_time_window(courseId, userId, sessionId, 0);
  });

  $(".start-btn").on("click", function () {
    let courseID = $(this).attr("id");

    toggle_time_window(courseID, userId, 0, 1);
  });

  let toggle_time_window = (course_id, userid, sessionId, active) => {
    // ajax call
    let wsfunction = "local_participant_image_upload_active_window";
    let params = {
      courseid: course_id,
      changedby: userid,
      sessionid: sessionId,
      active: active,
    };
    let request = {
      methodname: wsfunction,
      args: params,
    };
    console.log(request);

    Ajax.call([request])[0]
      .done(function (value) {
        window.console.log(value);
        window.location.href = $(location).attr("href");
      })
      .fail(Notification.exception);
  };
};
