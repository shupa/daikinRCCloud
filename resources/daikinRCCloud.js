const express = require('express');
const {logLevel, setUtilsData} = require('./lib/utils');
const {getAllDevices, getDevices, setData} = require('./lib/daikinAPI')

/*** Envoi des information du jeedom au fonction qui les utilise ***/
setUtilsData(process.argv[2], process.argv[3])

/*** Configuration ***/
const config = {
    logger: logLevel,
    listeningPort: 8890
};
let lastStart = 0;

/** Setup Web Server ***/
let app = express();
let server = null;

/*** Stop the server ***/
app.get('/stop', (req, res) => {
    config.logger('Shutting down');
    res.status(200).json({});
    server.close(() => {
        process.exit(0);
    });
});

/*** Restart server ***/
app.get('/restart', (req, res) => {
    config.logger('Restart');
    res.status(200).json({});
    config.logger('******************************************************************');
    config.logger('***************** Relance forcée du Serveur **********************');
    config.logger('******************************************************************');
    startServer();

});

/*** Get All devices ***/
app.get('/devices', async (req, res) => {
    res.send(await getAllDevices());
});

/*** Get devices By ID ***/
app.get('/devices/:devicesID', async (req, res) => {
    return res.send(await getDevices(req.params.devicesID));
});

/** Set devices data ***/
app.get('/setdata/:devicesID', async (req, res) => {

     config.logger(req.query)

    return res.send(await setData(req.params.devicesID, req.query.managementPoint, req.query.dataPoint, req.query.dataPointPath,req.query.dataValue));
});

app.get('/setdatas/:devicesID', async (req, res) => {

    config.logger(req.query)
    let data = req.query.data;
    let devicesID = req.params.devicesID;

    let result = await setData(devicesID, data);
    return res.send(result);
});

/*** Start server web ***/
startServer();

/*** Fonction de demarage du server web ***/
function startServer() {
    lastStart = Date.now();
    config.logger('*************** Lancement du server WEB **********************', 'INFO');


    server = app.listen(config.listeningPort, () => {
        config.logger('**************************************************************', 'INFO');
        config.logger('************** Server OK listening on port ' + server.address().port + ' **************', 'INFO');
        config.logger('**************************************************************', 'INFO');
    });
}