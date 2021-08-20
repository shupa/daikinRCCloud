
function logLevel(text, level = '') {
    try {
        let niveauLevel;
        switch (level) {
            case "ERROR":
                niveauLevel = 400;
                break;
            case "WARNING":
                niveauLevel = 300;
                break;
            case "INFO":
                niveauLevel = 200;
                break;
            case "DEBUG":
                niveauLevel = 100;
                break;
            default:
                niveauLevel = 400; //pour trouver ce qui n'a pas été affecté à un niveau
                break;
        }
    } catch (e) {
        console.log(arguments[0]);
    }
}

module.exports = logLevel;