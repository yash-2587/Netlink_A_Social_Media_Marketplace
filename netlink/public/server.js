// Import required modules
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const mysql = require('mysql2');

const app = express();
const server = http.createServer(app);
const io = socketIo(server);

// Middleware
app.use(express.static('scripts'));
app.use(express.json());

// MySQL Connection
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root', // Replace with your MySQL username
    password: '1234', // Replace with your MySQL password
    database: 'netlink' // Replace with your database name
});

db.connect((err) => {
    if (err) throw err;
    console.log('Connected to MySQL database');
});

// Routes
app.get('/messages', (req, res) => {
    const { sender_id, receiver_id } = req.query;
    const query = `
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC
    `;
    db.query(query, [sender_id, receiver_id, receiver_id, sender], (err, results) => {
        if (err) {
            console.error(err);
            return res.status(500).json({ 
                success: false, 
                message: 'Failed to fetch messages' 
            });
        }
        res.json({ success: true, messages: results });
    });
});
app.post('/login', (req, res) => {
    const { username } = req.body;
    if (username) {
        res.json({ success: true, username });
    } else {
        res.status(400).json({ success: false, message: 'Username is required' });
    }
});


io.on('connection', (socket) => {
    console.log('A user connected');

    socket.on('sendMessage', (data) => {
        const { sender_id, receiver_id, message } = data;

        // Save message to database
        const query = `
            INSERT INTO messages 
            (sender_id, receiver_id, message, timestamp) 
            VALUES (?, ?, ?, NOW())
        `;
        db.query(query, [sender_id, receiver_id, message], (err) => {
            if (err) {
                console.error('Message save error:', err);
                return;
            }

            // Emit message only to relevant users
            io.emit('receiveMessage', {
                ...data,
                timestamp: new Date().toISOString()
            });
        });
    });
});

// Start the server
const PORT = 8081;
server.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});

