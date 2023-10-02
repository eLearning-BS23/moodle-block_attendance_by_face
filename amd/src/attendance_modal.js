define(['jquery', 'core/ajax', 'core/str','core/modal_factory', 'core/notification', './webcam'],
    function($, Ajax, str, ModalFactory, Notification, Webcam) {
      return {
        async init (studentid, successmessage, failedmessage, threshold, modelurl) {
          // Load the model.
          await faceapi.nets.ssdMobilenetv1.loadFromUri(modelurl);

          let desc_webcam = ""; 

          let start_webcam = "";

          let submit_attendance = "";

          let try_again = "";

          let cancel = "";

          let warning = "";

          async function getMessages() {
            desc_webcam = await str.get_string('desc_webcam', 'block_attendance_by_face');
            start_webcam = await str.get_string('start_webcam', 'block_attendance_by_face');
            submit_attendance = await str.get_string('submit_attendance', 'block_attendance_by_face');
            try_again = await str.get_string('try_again', 'block_attendance_by_face');
            cancel = await str.get_string('cancel', 'block_attendance_by_face');
            warning = await str.get_string('warning_webcam', 'block_attendance_by_face');
            exceeded_limit = await str.get_string('failedmessagetextlimitexceeded', 'block_attendance_by_face');
            invalid_credentials = await str.get_string('failedmessageinvalidapi', 'block_attendance_by_face');
            invalid_upload_image = await str.get_string('failedmessagefaceimage', 'block_attendance_by_face');
            invalid_webcam_image = await str.get_string('failedmessagewebcamimage', 'block_attendance_by_face');
          }

          // Function to detect the face.
          var detectface = async function (input, croppedImage){
            const output = await faceapi.detectAllFaces(input);
            if (output[0] == null) {
              return "";
            }
            detections = output[0].box;
            let res = extractFaceFromBox(input, detections, croppedImage);
            return res;
          }

          // Function to draw image from the box data.
          async function extractFaceFromBox(imageRef, box, croppedImage) {
            const regionsToExtract = [
              new faceapi.Rect(box.x, box.y, box.width, box.height)
            ];
            let faceImages = await faceapi.extractFaces(imageRef, regionsToExtract);

            if (faceImages.length === 0) {
              console.log("No face found");
            } else {

              faceImages.forEach((cnv) => {
                croppedImage.src = cnv.toDataURL();
              });
             
              return croppedImage.src;
            }
          }

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
              getMessages().then(() => {
                console.log("Modal Messages are printed");
                create_modal();
              });
            })
            .fail(Notification.exception);
          // end of ajax call
      
          let create_modal = () => {
            ModalFactory.create({
              type: ModalFactory.types.SAVE_CANCEL,
              title: str.get_string('title_webcam', 'block_attendance_by_face'),
              body: `
              <div>
              <p id='desc_webcam'> ` + desc_webcam + `
              </p><p> ` + warning + ` </p>
              </div>
              <video id="webcam" autoplay playsinline width="300" height="225" style="display:none;margin:auto"></video>
              <canvas id="canvas" class="d-none" style="display:none;"></canvas>
              <img id="st-image" style="display: none;"/>
              <img id="st-image-cropped" style="display: none;"/>
              <img id="webcam-image" style="display: none;"/>
              <img id="webcam-image-cropped" style="display: none;"/>
              <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; padding: 0.75rem;">
                <button id='start-webcam' class="btn btn-primary"> ` + start_webcam +  `</button>
                <button id="submit-attendance" style="display:none;" class="btn btn-primary"> ` + submit_attendance + `</button>
                <button id="try-again" style="display:none;" class="btn btn-primary"> ` + try_again + ` </button>
                <button id='stop-webcam' class="btn btn-secondary" style="margin-left:5px;"> ` + cancel + ` </button>
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
                console.log("TRY AGAIN");
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
              let displayFailedMessage = (message) => {
                hideSubmitAttendance();
                displayTryAgain();
                displayMessage(failedmessage + message, 0);
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
                
                Ajax.call([request])[0]
                  .done(function () {
                    window.console.log("Attendance logged");
                  })
                  .fail(Notification.exception);
              };
              let submitAttendance = (st_img, image, sessionId) => {
                let wsfunction = "block_attendance_by_face_recognition_api";
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
                    let distance = value["distance"];
                    let status = value["status"];
                    window.console.log(distance);
      
                    if (status == 200 && distance != null && distance < threshold) {
                      let today = new Date();
                      webcam.stop();
                      displaySuccessMessage();
                      logAttendance(sessionId);
      
                      Notification.confirm(
                        successmessage,
                        `
                        Course: ${course_name}<br>
                        Date: ${today.toLocaleDateString("en-UK")}<br>
                        `,
                        "Continue", // Confirm.
                        "Cancel", // Cancel.
                        () => (window.location.href = $(location).attr("href")),
                        () => (window.location.href = $(location).attr("href"))
                      );
                    } else if (status == 403) {
                      displayFailedMessage(invalid_credentials);
                    } else if (status == 429) {
                      displayFailedMessage(exceeded_limit);
                    } else if (status == 435) {
                      displayFailedMessage(invalid_upload_image);
                    } else if (status == 436) {
                      displayFailedMessage(invalid_webcam_image);
                    } else {
                      displayFailedMessage("");
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

                      document.getElementById('submit-attendance').disabled = true;
                      document.getElementById('submit-attendance').innerText = "";
                      document.getElementById('submit-attendance').innerHTML = "<div id='spinner' class='spinner-border spinner-border-sm' role='status'></div>";

                      if (!st_img) {
                        st_img = getDataUrl(studentimg);
                      }
                      let image = webcam.snap();
                      let webcamimg = document.getElementById("webcam-image");
                      let webcamimgcrop = document.getElementById("webcam-image-cropped");
                      let studentimgcrop = document.getElementById("st-image-cropped");
                      webcamimg.src = image;
                      async function a () {
                        st_img = await detectface(studentimg, studentimgcrop);
                        image = await detectface(webcamimg, webcamimgcrop);
                      };
                      a().then(() => {
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
                      
                      Ajax.call([request])[0]
                      .done(function (value) {
                        if(value.active == 1) {
                          submitAttendance(st_img, image, value.sessionid);
                        } else {
                          displayMessage("Course is not open for taking attendance", 0);
                        }
                      })
                      .fail(function (err) {
                        window.console.log(err);
                      });

                      });
                    });
                    $("#try-again").on("click", function () {
                      window.location.href = $(location).attr("href");
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
      }
    };
    }
  );