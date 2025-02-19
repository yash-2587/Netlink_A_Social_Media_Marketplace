const express = require('express');
const mysql = require('mysql2');
const cors = require('cors'); // Add this line
const path = require('path');
const app = express();
const port = 5500;

// Enable CORS
app.use(cors());

// Create MySQL connection
const pool = mysql.createPool({
    host: '127.0.0.1',
    user: 'fcs2',
    password: 'fcs123',
    database: 'user_management',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// API endpoint for all users
app.get('/api/users', (req, res) => {
    pool.query('SELECT id, username, email, is_verified FROM users', (error, results) => {
        if (error) {
            console.error('Database error:', error);
            return res.status(500).json({ error: 'Database query failed' });
        }
        res.json(results);
    });
});


app.delete('/api/users/:id', (req, res) => {
    const userId = req.params.id;
    pool.query('DELETE FROM users WHERE id = ?', [userId], (error, results) => {
        if (error) {
            console.error('Database error:', error);
            return res.status(500).json({ error: 'Failed to delete user' });
        }
        if (results.affectedRows === 0) {
            return res.status(404).json({ error: 'User not found' });
        }
        res.json({ message: 'User deleted successfully' });
    });
});


// Catch-all route for HTML5 history API
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

app.listen(port, () => {
    console.log(`Server running on http://localhost:${port}`);
});