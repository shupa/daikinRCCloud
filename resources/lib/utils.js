const fetch = require('node-fetch');

let IPJeedom;
let APIKey;

function setUtilsData(jeedomIP, key) {
    IPJeedom = jeedomIP;
    APIKey = key;
}

function logLevel(text, level = 'debug') {
    try {
        sendToJeedom("log", {level: level, text: text})
    } catch (e) {
        sendToJeedom("log", {level: "error", text: text})
        console.log(arguments[0]);
    }
}

function sendToJeedom(Action, Data) {
    let url = IPJeedom + "/plugins/daikinRCCloud/core/php/jeeDaikinRCCloud.php?apikey=" + APIKey + "&action=" + Action;

    fetch(url, {method: 'post', body: JSON.stringify(Data)})
        .then(res => {
            if (!res.ok) {
                console.log("Erreur lors du contact de votre JeeDom")
            }
        })
}
module.exports = {
    logLevel,
    sendToJeedom,
    setUtilsData
};