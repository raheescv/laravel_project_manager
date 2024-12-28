const express = require('express');
const { Client, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const cors = require('cors');
const bodyParser = require('body-parser');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(cors());
app.use(bodyParser.json());

const client = new Client();

// Generate QR code for WhatsApp login
client.on('qr', (qr) => {
    console.log('QR Code received, scan it with WhatsApp:');
    qrcode.generate(qr, { small: true });
});
client.on('ready', () => {
    console.log('WhatsApp client is ready!');
});
client.initialize();

// Endpoint to send a message
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
        res.status(500).json({ success: false, error: error.message });
    }
});

const PORT = 3002;
app.listen(PORT, () => {
    console.log(`Server running on port ${PORT}`);
});
