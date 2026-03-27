const errorMsg = document.getElementById("errorMsg");

form.addEventListener("submit", function(e) {
    e.preventDefault();

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    if(username === "admin" && password === "1234") {
        errorMsg.textContent = "";
        alert("Login succesvol!");
    } else {
        errorMsg.textContent = "Gebruikersnaam of wachtwoord is fout";
    }
});