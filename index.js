import express from 'express';
import bodyParser from 'body-parser';
import path from 'path';
import { fileURLToPath } from 'url';

const app = express();

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Routes
app.get('/', function (req, res) {
    res.sendFile(path.join(__dirname, 'register.html'));
});

app.post('/', function (req, res) {
    console.log(req.body);
    res.send('Form submitted!');
});

// Start server
app.listen(5500, () => {
    console.log('Server running on port 5500');
});
