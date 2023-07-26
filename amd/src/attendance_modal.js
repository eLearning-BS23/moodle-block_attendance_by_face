import $ from "jquery";
import ModalFactory from "core/modal_factory";
import Notification from "core/notification";
import Webcam from "./webcam";
import Ajax from "core/ajax";
export const init = (studentid, successmessage, failedmessage, threshold) => {
  $(".action-modal").on("click", function () {
    let st_img_url = "";
    let course_name = "";
    let course_id = $(this).attr("id");

    // ajax call
    let wsfunction = "block_attendance_by_face_image_api";
    let params = {
      courseid: course_id,
      studentid: studentid,
    };
    let request = {
      methodname: wsfunction,
      args: params,
    };

    Ajax.call([request])[0]
      .done(function (value) {
        st_img_url = value["image_url"];
        course_name = value["course_name"];
        create_modal();
      })
      .fail(Notification.exception);
    // end of ajax call

    let create_modal = () => {
      ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: "Turn on webcam",
        body: `
        <div>
        <p>Webcam will be turned on to take video and image input for your attendance.
        <i class="icon fa fa-exclamation-circle text-muted fa-fw " 
            title="If facing any issue with webcam, refresh the site and try again" role="img" 
            aria-label="If facing any issue with webcam, refresh the site and try again">
        </i>
        </p>
        </div>
        <video id="webcam" autoplay playsinline width="300" height="225" style="display:none;margin:auto"></video>
        <canvas id="canvas" class="d-none" style="display:none;"></canvas>
        <img id="st-image" style="display: none;"/>
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; padding: 0.75rem;">
        
          <button id='start-webcam' class="btn btn-primary" >Start Webcam</button>
          <button id="submit-attendance" style="display:none;" class="btn btn-primary" >Submit attendance</button>
          <button id="try-again" style="display:none;" class="btn btn-primary" >Try again</button>
          <button id='stop-webcam' class="btn btn-secondary" style="margin-left:5px;">Cancel</button>
        
        </div>
        <div id="message"></div>`,
      }).then(function (modal) {
        modal.show();
        $(".modal-footer").hide();

        const webcamElement = document.getElementById("webcam");
        const canvasElement = document.getElementById("canvas");
        let studentimg = document.getElementById("st-image");
        studentimg.src = st_img_url;
        let st_img = "";

        let webcam = new Webcam(webcamElement, "user", canvasElement);

        $(".close").on("click", function () {
          webcam.stop();
          window.location.href = $(location).attr("href");
        });

        let getDataUrl = (studentimg) => {
          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");
          // Set width and height
          canvas.width = studentimg.width;
          canvas.height = studentimg.height;
          // Draw the image
          ctx.drawImage(studentimg, 0, 0);
          return canvas.toDataURL("image/png");
        };
        let displaySubmitAttendance = () => {
          document.getElementById("submit-attendance").style.display = "block";
        };
        let hideSubmitAttendance = () => {
          document.getElementById("submit-attendance").style.display = "none";
        };
        let displayTryAgain = () => {
          document.getElementById("try-again").style.display = "block";
        };
        let hideTryAgain = () => {
          document.getElementById("try-again").style.display = "none";
        };
        let removeMessages = () => {
          const message = document.getElementById("message");
          while (message.hasChildNodes()) {
            message.removeChild(message.firstChild);
          }
        };
        let displaySuccessMessage = () => {
          hideSubmitAttendance();
          displayMessage(successmessage, 1);
        };
        let displayFailedMessage = () => {
          hideSubmitAttendance();
          displayTryAgain();
          displayMessage(failedmessage, 0);
        };
        let displayMessage = (message, flag) => {
          var spn = document.createElement("span");
          spn.textContent = message + ".";
          spn.setAttribute("class", flag ? "text-success" : "text-danger");
          document.getElementById("message").appendChild(spn);
        };
        let logAttendance = (sessionId) => {
          let wsfunction =
            "block_attendance_by_face_update_db";
          let params = {
            courseid: course_id,
            studentid: studentid,
            sessionid: sessionId,
          };
          let request = {
            methodname: wsfunction,
            args: params,
          };
          console.log(request);
          Ajax.call([request])[0]
            .done(function () {
              window.console.log("Attendance logged");
            })
            .fail(Notification.exception);
        };
        let submitAttendance = (st_img, image, sessionId) => {
          let wsfunction =
            "block_face_recognition_student_attendance_face_recog_api";
          let params = {
            studentimg: st_img,
            webcampicture: image,
          };
          let request = {
            methodname: wsfunction,
            args: params,
          };

          Ajax.call([request])[0]
            .done(function (value) {
              let result = value["confidence"];
              window.console.log(value);

              if (result >= threshold) {
                let today = new Date();
                webcam.stop();
                displaySuccessMessage();
                logAttendance(sessionId);

                Notification.confirm(
                  "Attendance submitted successfully",
                  `
                  Course: ${course_name}<br>
                  Date: ${today.toLocaleDateString("en-UK")}<br>
                  `,
                  "Continue", // Confirm.
                  "Cancel", // Cancel.
                  () => (window.location.href = $(location).attr("href")),
                  () => (window.location.href = $(location).attr("href"))
                );
              } else {
                displayFailedMessage();
              }
            })
            .fail(function (err) {
              window.console.log(err);
            });
          // end of ajax call
        };
        // let getRequestForCheckingActiveWindowAPI = (course_id) => {
        //   let wsfunction =
        //     "block_face_recognition_student_attendance_check_active_window";
        //   let params = {
        //     courseid: course_id
        //   };
        //   let request = {
        //     methodname: wsfunction,
        //     args: params,
        //   };
        //   return request;
        // }
        $("#start-webcam").on("click", function () {
          webcamElement.style.display = "block";
          canvasElement.style.display = "block";
          $("#start-webcam").hide();
          webcam
            .start()
            .then((result) => {
              displaySubmitAttendance();

              $("#submit-attendance").on("click", function () {
                removeMessages();
                if (!st_img) {
                  st_img = getDataUrl(studentimg);
                }
                let image = webcam.snap();
                //let request = getRequestForCheckingActiveWindowAPI(course_id);
                let wsfunction =
                  "block_attendance_by_face_check_active_window";
                let params = {
                  courseid: course_id
                };
                let request = {
                  methodname: wsfunction,
                  args: params,
                };
                console.log(params);
                
                Ajax.call([request])[0]
                .done(function (value) {
                  console.log(value);
                  if(value.active == 1) {
                    console.log('Active Course');
                    submitAttendance(st_img, image, value.sessionid);
                  }
                  else {
                    displayMessage("Course is not open for taking attendance", 0);
                  }
                })
                .fail(function (err) {
                  window.console.log(err);
                });
                

              });
              $("#try-again").on("click", function () {
                removeMessages();
                hideTryAgain();
                document.getElementById("submit-attendance").click();
              });
            })
            .catch((err) => {
              window.console.log(err);
            });
        });
        $("#stop-webcam").on("click", function () {
          webcam.stop();
          window.location.href = $(location).attr("href");
        });
      });
    };
  });
};
