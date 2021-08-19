const express = require('express');
require('fs');

const app = express();
let server = null;

/***** Stop the server *****/
app.get('/stop', (req, res) => {
    config.logger('daikinRCCloud: Shutting down');
    res.status(200).json({});
    server.close(() => {
        process.exit(0);
    });
});

/* Configuration */
const config = {
    logger: console,
    listeningPort: 3477
};

let dernierStartServeur = 0;

function console(text, level = '') {
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

startServer();

function startServer() {
    dernierStartServeur = Date.now();

    config.logger('daikinRCCloud:    ******************** Lancement BOT ***********************', 'INFO');

    server = app.listen(config.listeningPort, () => {
        config.logger('daikinRCCloud:    **************************************************************', 'INFO');
        config.logger('daikinRCCloud:    ************** Server OK listening on port ' + server.address().port + ' **************', 'INFO');
        config.logger('daikinRCCloud:    **************************************************************', 'INFO');
    });
}