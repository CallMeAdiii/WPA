const express = require('express');
const app = express();
require('dotenv').config();

app.use(express.json());

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/facilities', require('./routes/facilities'));
app.use('/api/reservations', require('./routes/reservations'));

// Health check
app.get('/', (req, res) => {
    res.json({ message: 'Rezervační systém sportovišť API běží' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Server běží na portu ${PORT}`);
});
