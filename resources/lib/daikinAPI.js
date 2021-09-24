const DaikinCloud = require('daikin-controller-cloud');
const fs = require('fs');
const path = require('path');

const options = {
    logger: console.log,          // optional, logger function used to log details depending on loglevel
    logLevel: 'info',             // optional, Loglevel of Library, default 'warn' (logs nothing by default)
    proxyOwnIp: '127.0.0.1',// required, if proxy needed: provide own IP or hostname to later access the proxy
    proxyPort: 8888,              // required: use this port for the proxy and point your client device to this port
    proxyWebPort: 8889,           // required: use this port for the proxy web interface to get the certificate and start Link for login
    proxyListenBind: '0.0.0.0',   // optional: set this to bind the proxy to a special IP, default is '0.0.0.0'
    proxyDataDir: process.cwd()       // Directory to store certificates and other proxy relevant data to
};
let tokenSet;

// Load Tokens if they already exist on disk
const tokenFile = path.join(__dirname, '../tokenset.json');
if (fs.existsSync(tokenFile)) {
    tokenSet = JSON.parse(fs.readFileSync(tokenFile).toString());
}
// Initialize Daikin Cloud Instance
const daikinCloud = new DaikinCloud(tokenSet, options);

// Event that will be triggered on new or updated tokens, save into file
daikinCloud.on('token_update', tokenSet => {
    console.log(`UPDATED tokens, use for future and wrote to tokenset.json`);
    fs.writeFileSync(tokenFile, JSON.stringify(tokenSet));
});

// If no tokens are existing start Proxy server process
if (!tokenSet) {
    console.log(`Error Token`);
    process.exit(0);
}

async function getAllDevices() {
    return await daikinCloud.getCloudDeviceDetails();
}

async function getDevices(devicesID) {
    const devices = await daikinCloud.getCloudDevices();
    if (devices && devices.length) {
        for (let device of devices) {
            if (device.getId() !== devicesID) {
                continue;
            }
            device.cloud = null;
            device.desc.managementPoints = null;
            return device;
        }
    } else {
        return false;
    }
}

async function setData(devicesID, managementPoint, dataPoint, dataPointPath, dataValue) {
    const devices = await daikinCloud.getCloudDevices();
    if (devices && devices.length) {
        for (let device of devices) {
            if (device.getId() !== devicesID) {
                continue;
            }

            //await device.setData('climateControl', 'onOffMode', 'on');
            //await device.setData('climateControl', 'temperatureControl', '/operationModes/cooling/setpoints/roomTemperature', 20);

            if (!dataPointPath) {
                let oldValue = device.getData(managementPoint, dataPoint).value;
                dataValue = convertValue(dataValue, typeof oldValue)

                await device.setData(managementPoint, dataPoint, dataValue);
            } else {
                let oldValue = device.getData(managementPoint, dataPoint, dataPointPath, dataValue).value;
                dataValue = convertValue(dataValue, typeof oldValue)

                await device.setData(managementPoint, dataPoint, dataPointPath, dataValue);
            }

            await device.updateData();

            device.cloud = null;
            device.desc.managementPoints = null;
            return device;
        }
    } else {
        return false;
    }

}

async function setDatas(devicesID, data) {
    const devices = await daikinCloud.getCloudDevices();
    if (devices && devices.length) {
        for (let device of devices) {
            if (device.getId() !== devicesID) {
                continue;
            }

            for (const cmd of data) {
                let managementPoint = cmd.managementPoint;
                let dataPoint = cmd.dataPoint;
                let dataPointPath = cmd.dataPointPath;
                let dataValue = cmd.dataValue;s

                if (!dataPointPath) {
                    let oldValue = device.getData(managementPoint, dataPoint).value;
                    dataValue = convertValue(dataValue, typeof oldValue)

                    await device.setData(managementPoint, dataPoint, dataValue);
                } else {
                    let oldValue = device.getData(managementPoint, dataPoint, dataPointPath, dataValue).value;
                    dataValue = convertValue(dataValue, typeof oldValue)

                    await device.setData(managementPoint, dataPoint, dataPointPath, dataValue);
                }
            }

            await device.updateData();

            device.cloud = null;
            device.desc.managementPoints = null;
            return device;
        }
    } else {
        return false;
    }

}

function convertValue(value, typeOfValue) {

    console.log(typeOfValue);

    switch (typeOfValue) {
        case 'number':
            value = Number(value);
            break;
        case 'boolean':
            value = Boolean(value)
            break;
        default:
    }

    return value;
}

module.exports = {
    getAllDevices,
    getDevices,
    setData
};