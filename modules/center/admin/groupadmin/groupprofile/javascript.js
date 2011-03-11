function formCheck() {

  if (document.getElementById("name").value == "") {
    alert("You must enter a name");
    return false;
  }

  return true;
}

