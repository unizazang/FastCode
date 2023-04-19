let popupModal = document.querySelector("dialog"),
  popupClose = document.querySelector(".popup-close"),
  dayCheck = document.querySelector("#dont-see");

function setCookie(name, value, day) {
  let date = new Date();
  date.setDate(date.getDate() + day);
  document.cookie = `${name}=${value};expires=${date.toUTCString()}`;
}

function checkCookie(name) {
  let cookieArr = document.cookie.split(";");
  let visited = false;
  for (let cookie of cookieArr) {
    if (cookie.search(name) > -1) {
      visited = true;
      break;
    }
  }
  if (visited == false) {
    popupModal.setAttribute("open", "");
  }
}

checkCookie("lscookie");

popupClose.addEventListener("click", () => {
  if (dayCheck.checked) {
    setCookie("lscookie", "home", 1);
  } else {
    setCookie("lscookie", "home", -1);
  }
  popupModal.removeAttribute("open");
});
