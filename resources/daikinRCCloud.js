const express = require('express');
const logLevel = require('./lib/utils');
const {getAllDevices, getDevices, setData} = require('./lib/daikinAPI')

/* Configuration */
const config = {
    logger: logLevel,
    listeningPort: 8890
};
let lastStart = 0;

let app = express();
let server = null;

/***** Stop the server *****/
app.get('/stop', (req, res) => {
    config.logger('DaikinRCCloud: Shutting down');
    res.status(200).json({});
    server.close(() => {
        process.exit(0);
    });
});

/***** Restart server *****/
app.get('/restart', (req, res) => {
    config.logger('daikinrccloud: Restart');
    res.status(200).json({});
    config.logger('daikinrccloud: ******************************************************************');
    config.logger('daikinrccloud: *****************************Relance forcée du Serveur*************');
    config.logger('daikinrccloud: ******************************************************************');
    startServer();

});

/***** Get All devices *****/
app.get('/devices', async (req, res) => {
    res.send(await getAllDevices());
});

app.get('/devices/:devicesID', async (req, res) => {
    return res.send(await getDevices(req.params.devicesID));
});

app.get('/setdata/:devicesID', async (req, res) => {

    console.log(req.query)

    return res.send(await setData(req.params.devicesID, req.query.managementPoint, req.query.dataPoint, req.query.dataPointPath,req.query.dataValue));
});

startServer();

function startServer() {
    lastStart = Date.now();
    config.logger('daikinRCCloud:    ******************** Lancement du server WEB ***********************', 'INFO');


    server = app.listen(config.listeningPort, () => {
        config.logger('daikinrccloud:    **************************************************************', 'INFO');
        config.logger('daikinrccloud:    ************** Server OK listening on port ' + server.address().port + ' **************', 'INFO');
        config.logger('daikinrccloud:    **************************************************************', 'INFO');
    });
}