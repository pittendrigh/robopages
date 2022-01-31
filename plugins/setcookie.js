 document.cookie = 'robopage=' + value + 'path=/; max-age=${60 * 60 * 24 * 14};`;



// Set a Cookie
function setCookie(cName, cValue, expDays) {
        let date = new Date();
        date.setTime(date.getTime() + (expDays * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = cName + "=" + cValue + "; " + expires + "; path=/";
}
// Apply setCookie
setCookie('username', username, 30);


