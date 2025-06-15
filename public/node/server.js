const express = require('express');
const { Client, MessageMedia } = require('whatsapp-web.js');
const cors = require('cors');
const bodyParser = require('body-parser');
const fs = require('fs').promises;
const fsSync = require('fs');
const qrCode = require('qrcode');
const path = require('path');

require('dotenv').config({ path: '../../.env' });

const app = express();

// Enhanced middleware with better security and parsing
app.use(cors({
    origin: process.env.ALLOWED_ORIGINS?.split(',') || ['http://localhost:3000'],
    credentials: true
}));
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '10mb' }));

// Global variables
let client = null;
let latestQrCode = null;
let clientReady = false;
let isInitializing = false;

// Enhanced logging utility
const logger = {
    info: (msg) => console.log(`[${new Date().toISOString()}] INFO: ${msg}`),
    error: (msg) => console.error(`[${new Date().toISOString()}] ERROR: ${msg}`),
    warn: (msg) => console.warn(`[${new Date().toISOString()}] WARN: ${msg}`)
};

// Client initialization with better error handling
async function initializeClient() {
    if (isInitializing) {
        logger.warn('Client initialization already in progress');
        return;
    }

    try {
        isInitializing = true;
        logger.info('Initializing WhatsApp client...');

        client = new Client({
            puppeteer: {
                headless: true,
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu'
                ],
            },
        });

        // QR Code event handler
        client.on('qr', async (qr) => {
            try {
                logger.info('QR Code received, generating data URL...');
                latestQrCode = await qrCode.toDataURL(qr);
                logger.info('QR Code generated successfully');
            } catch (err) {
                logger.error(`Error generating QR code: ${err.message}`);
            }
        });

        // Ready event handler
        client.on('ready', () => {
            clientReady = true;
            latestQrCode = null; // Clear QR code when ready
            logger.info('WhatsApp client is ready!');
        });

        // Authentication success handler
        client.on('authenticated', () => {
            logger.info('WhatsApp client authenticated successfully');
        });

        // Authentication failure handler
        client.on('auth_failure', (msg) => {
            logger.error(`Authentication failed: ${msg}`);
            clientReady = false;
        });

        // Disconnection handler
        client.on('disconnected', async (reason) => {
            logger.warn(`WhatsApp client disconnected: ${reason}`);
            clientReady = false;
            latestQrCode = null;

            try {
                if (client) {
                    await client.destroy();
                    logger.info('Client destroyed successfully');
                }
            } catch (error) {
                logger.error(`Error destroying client: ${error.message}`);
            }
        });

        await client.initialize();
        logger.info('Client initialization started');
    } catch (error) {
        logger.error(`Failed to initialize client: ${error.message}`);
        clientReady = false;
    } finally {
        isInitializing = false;
    }
}

// Initialize client
initializeClient();

// API Routes

// Get QR Code endpoint
app.get('/get-qr', (req, res) => {
    try {
        if (latestQrCode) {
            res.json({
                success: true,
                qr: latestQrCode,
                timestamp: new Date().toISOString()
            });
        } else if (clientReady) {
            res.json({
                success: true,
                message: 'Client is already authenticated',
                ready: true
            });
        } else {
            res.status(404).json({
                success: false,
                message: 'QR code not available yet. Please wait...',
                ready: false
            });
        }
    } catch (error) {
        logger.error(`Error in /get-qr: ${error.message}`);
        res.status(500).json({
            success: false,
            message: 'Internal server error'
        });
    }
});

// Check client status endpoint
app.get('/check-status', (req, res) => {
    try {
        const status = {
            success: clientReady,
            ready: clientReady,
            timestamp: new Date().toISOString()
        };

        if (clientReady && client?.info) {
            status.info = {
                wid: client.info.wid,
                pushname: client.info.pushname,
                platform: client.info.platform
            };
            status.message = 'Client is ready and connected';
        } else if (isInitializing) {
            status.message = 'Client is initializing. Please wait...';
        } else {
            status.message = 'Client is not ready. Please scan QR code or check connection.';
        }

        res.json(status);
    } catch (error) {
        logger.error(`Error in /check-status: ${error.message}`);
        res.status(500).json({
            success: false,
            message: 'Error checking client status'
        });
    }
});

// Disconnect and reconnect endpoint
app.post('/disconnect', async (req, res) => {
    try {
        logger.info('Received disconnect request');

        if (!client) {
            return res.status(400).json({
                success: false,
                message: 'No client instance found'
            });
        }

        // Reset state
        clientReady = false;
        latestQrCode = null;

        try {
            await client.logout();
            logger.info('Client logged out successfully');
        } catch (logoutError) {
            logger.warn(`Logout warning: ${logoutError.message}`);
        }

        try {
            await client.destroy();
            logger.info('Client destroyed successfully');
        } catch (destroyError) {
            logger.warn(`Destroy warning: ${destroyError.message}`);
        }

        // Wait a bit before reinitializing
        setTimeout(() => {
            initializeClient();
        }, 2000);

        res.json({
            success: true,
            message: 'Client disconnect initiated. Reconnecting...'
        });
    } catch (error) {
        logger.error(`Error in /disconnect: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Send message endpoint with enhanced validation
app.post('/send-message', async (req, res) => {
    try {
        // Check if client is ready
        if (!clientReady || !client?.info) {
            return res.status(400).json({
                success: false,
                message: "WhatsApp client is not connected. Please ensure client is ready."
            });
        }

        const { number, message, filePath } = req.body;

        // Validate input
        if (!number) {
            return res.status(400).json({
                success: false,
                message: "Phone number is required"
            });
        }

        if (!message && !filePath) {
            return res.status(400).json({
                success: false,
                message: "Either message text or file path is required"
            });
        }

        // Validate and format phone number
        const cleanNumber = number.replace(/[^\d]/g, '');
        if (cleanNumber.length < 10) {
            return res.status(400).json({
                success: false,
                message: "Invalid phone number format"
            });
        }

        const chatId = `${cleanNumber}@c.us`;

        // Validate file if provided
        if (filePath) {
            try {
                await fs.access(filePath);
                const stats = await fs.stat(filePath);
                if (!stats.isFile()) {
                    throw new Error("Path is not a file");
                }

                // Check file size (max 64MB for WhatsApp)
                if (stats.size > 64 * 1024 * 1024) {
                    return res.status(400).json({
                        success: false,
                        message: "File size too large. Maximum 64MB allowed."
                    });
                }
            } catch (fileError) {
                return res.status(400).json({
                    success: false,
                    message: `Invalid file: ${fileError.message}`
                });
            }
        }

        // Send message
        let response;
        const startTime = Date.now();

        try {
            if (message && filePath) {
                // Send text with media
                const media = MessageMedia.fromFilePath(filePath);
                response = await client.sendMessage(chatId, media, { caption: message });
                logger.info(`Message with media sent to ${cleanNumber}`);
            } else if (message) {
                // Send text only
                response = await client.sendMessage(chatId, message);
                logger.info(`Text message sent to ${cleanNumber}`);
            } else if (filePath) {
                // Send media only
                const media = MessageMedia.fromFilePath(filePath);
                response = await client.sendMessage(chatId, media);
                logger.info(`Media sent to ${cleanNumber}`);
            }

            const duration = Date.now() - startTime;

            res.status(200).json({
                success: true,
                message: 'Message sent successfully',
                data: {
                    messageId: response.id.id,
                    timestamp: response.timestamp,
                    duration: `${duration}ms`,
                    recipient: cleanNumber
                }
            });

        } catch (sendError) {
            logger.error(`Failed to send message to ${cleanNumber}: ${sendError.message}`);

            // Handle specific WhatsApp errors
            let errorMessage = 'Failed to send message';
            if (sendError.message.includes('phone number is not registered')) {
                errorMessage = 'Phone number is not registered on WhatsApp';
            } else if (sendError.message.includes('rate limit')) {
                errorMessage = 'Rate limit exceeded. Please try again later';
            }

            res.status(400).json({
                success: false,
                message: errorMessage,
                error: sendError.message
            });
        }

    } catch (error) {
        logger.error(`Error in /send-message: ${error.message}`);
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// Server configuration
const DEFAULT_PORT = 3000;
const PORT = process.env.WHATSAPP_PORT || DEFAULT_PORT;

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        client: {
            ready: clientReady,
            initializing: isInitializing
        }
    });
});

// Start server with enhanced error handling
const server = app.listen(PORT, () => {
    logger.info(`WhatsApp server running on port ${PORT}`);
    logger.info(`Health check available at: http://localhost:${PORT}/health`);
}).on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        logger.error(`Port ${PORT} is already in use. Please free up the port or use a different port in .env file`);
        process.exit(1);
    } else {
        logger.error(`Failed to start server: ${err.message}`);
        process.exit(1);
    }
});

// Graceful shutdown handling
const gracefulShutdown = async (signal) => {
    logger.info(`Received ${signal}. Starting graceful shutdown...`);

    try {
        // Close HTTP server
        server.close(() => {
            logger.info('HTTP server closed');
        });

        // Close WhatsApp client
        if (client) {
            logger.info('Closing WhatsApp client...');
            try {
                await client.destroy();
                logger.info('WhatsApp client closed successfully');
            } catch (error) {
                logger.error(`Error closing WhatsApp client: ${error.message}`);
            }
        }

        logger.info('Graceful shutdown completed');
        process.exit(0);
    } catch (error) {
        logger.error(`Error during shutdown: ${error.message}`);
        process.exit(1);
    }
};

// Handle process termination
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT', () => gracefulShutdown('SIGINT'));

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
    logger.error(`Uncaught Exception: ${error.message}`);
    logger.error(error.stack);
    process.exit(1);
});

// Handle unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    logger.error(`Unhandled Rejection at: ${promise}, reason: ${reason}`);
    process.exit(1);
});

logger.info('WhatsApp server initialized successfully');
