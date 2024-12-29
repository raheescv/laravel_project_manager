const express = require('express');
const { Client, MessageMedia } = require('whatsapp-web.js');
const cors = require('cors');
const bodyParser = require('body-parser');
const fs = require('fs');
const qrCode = require('qrcode');

const app = express();
app.use(cors());
app.use(bodyParser.json());

let latestQrCode = null;

function initializeClient() {
    client = new Client();

    client.on('qr', (qr) => {
        console.log('QR Code received, scan it with WhatsApp:');
        qrCode.toDataURL(qr, (err, url) => {
            if (err) {
                console.error('Error generating QR code:', err);
                return;
            }
            latestQrCode = url;
            console.log('QR Code sent to WebSocket clients.');
        });
    });

    client.on('ready', () => {
        console.log('WhatsApp client is ready!');
    });

    client.on('disconnected', (reason) => {
        console.log('Disconnecting the WhatsApp client...');
        client.destroy().then(() => {
            console.log('WhatsApp client disconnected successfully.');
        }).catch((error) => {
            console.error('Error while disconnecting the client:', error);
        });
    });

    client.initialize();
}

initializeClient();

app.get('/get-qr', (req, res) => {
    if (latestQrCode) {
        res.json({ success: true, qr: latestQrCode });
    } else {
        res.status(404).json({ success: false, message: 'QR code not available' });
    }
});

app.get('/check-status', (req, res) => {
    if (client.info) {
        res.json({ success: true, message: 'Client is ready' });
    } else {
        res.json({ success: false, message: 'Client is not ready. Please wait or re connect or check for issues.' });
    }
});

app.post('/disconnect', async (req, res) => {
    console.log('Disconnecting the WhatsApp client...');
    try {
        console.log('Disconnecting the client...');
        await client.logout();
        console.log('Successfully logged out. The session is terminated.');
        await client.destroy();
        console.log('Client disconnected successfully.');

        console.log('Reconnecting the client...');
        initializeClient();

        res.json({ success: true, message: 'Client reconnected successfully.' });
    } catch (error) {
        console.error('Error during reconnection:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

app.post('/send-message', async (req, res) => {
    try {
        const { number, message, filePath } = req.body;
        if (!message && !filePath) {
            throw new Error("message or media is required to send a message");
        }
        const chatId = `${number.replace('+', '')}@c.us`;
        if (filePath) {
            if (!fs.existsSync(filePath)) {
                throw new Error("Invalid File");
            }
        }
        let response;
        if (message && filePath) {
            const media = MessageMedia.fromFilePath(filePath);
            response = await client.sendMessage(chatId, message, { media });
        } else if (message) {
            response = await client.sendMessage(chatId, message);
        } else if (filePath) {
            const media = MessageMedia.fromFilePath(filePath);
            response = await client.sendMessage(chatId, media);
        }
        res.status(200).json({ success: true, message: 'Message sent', response });
    } catch (error) {
        res.status(500).json({ success: false, message: error.message });
    }
});

const PORT = 3002;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
