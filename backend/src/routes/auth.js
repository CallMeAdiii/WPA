const express = require('express');
const router = express.Router();
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const db = require('../db');
const { authMiddleware } = require('../middleware/auth');

// POST /api/auth/register
router.post('/register', async (req, res) => {
    const { name, email, password, role } = req.body;

    if (!name || !email || !password) {
        return res.status(400).json({ error: 'Vyplňte jméno, email a heslo' });
    }

    if (typeof name !== 'string' || name.trim().length < 2 || name.trim().length > 100) {
        return res.status(400).json({ error: 'Jméno musí mít 2–100 znaků' });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return res.status(400).json({ error: 'Neplatný formát emailu' });
    }

    if (typeof password !== 'string' || password.length < 6 || password.length > 100) {
        return res.status(400).json({ error: 'Heslo musí mít 6–100 znaků' });
    }

    const allowedRoles = ['student', 'teacher', 'admin'];
    const userRole = allowedRoles.includes(role) ? role : 'student';

    try {
        const hashedPassword = await bcrypt.hash(password, 10);
        const [result] = await db.query(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [name.trim(), email, hashedPassword, userRole]
        );
        res.status(201).json({ message: 'Registrace proběhla úspěšně', userId: result.insertId });
    } catch (err) {
        if (err.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({ error: 'Tento email je již registrován' });
        }
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// POST /api/auth/login
router.post('/login', async (req, res) => {
    const { email, password } = req.body;

    if (!email || !password) {
        return res.status(400).json({ error: 'Vyplňte email a heslo' });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return res.status(400).json({ error: 'Neplatný formát emailu' });
    }

    if (typeof password !== 'string' || password.length < 6) {
        return res.status(400).json({ error: 'Neplatné heslo' });
    }

    try {
        const [rows] = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        const user = rows[0];

        if (!user) {
            return res.status(401).json({ error: 'Nesprávný email nebo heslo' });
        }

        const match = await bcrypt.compare(password, user.password);
        if (!match) {
            return res.status(401).json({ error: 'Nesprávný email nebo heslo' });
        }

        const token = jwt.sign(
            { id: user.id, email: user.email, role: user.role },
            process.env.JWT_SECRET,
            { expiresIn: '24h' }
        );

        res.json({
            message: 'Přihlášení úspěšné',
            token,
            user: { id: user.id, name: user.name, email: user.email, role: user.role }
        });
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// PATCH /api/auth/change-password — změna hesla přihlášeného uživatele
router.patch('/change-password', authMiddleware, async (req, res) => {
    const { currentPassword, newPassword } = req.body;

    if (!currentPassword || !newPassword) {
        return res.status(400).json({ message: 'Vyplňte současné i nové heslo.' });
    }

    if (typeof newPassword !== 'string' || newPassword.length < 6 || newPassword.length > 100) {
        return res.status(400).json({ message: 'Nové heslo musí mít 6–100 znaků.' });
    }

    try {
        const [rows] = await db.query('SELECT * FROM users WHERE id = ?', [req.user.id]);
        const user = rows[0];

        if (!user) {
            return res.status(404).json({ message: 'Uživatel nenalezen.' });
        }

        const match = await bcrypt.compare(currentPassword, user.password);
        if (!match) {
            return res.status(401).json({ message: 'Současné heslo je nesprávné.' });
        }

        if (currentPassword === newPassword) {
            return res.status(400).json({ message: 'Nové heslo musí být odlišné od současného.' });
        }

        const hashed = await bcrypt.hash(newPassword, 10);
        await db.query('UPDATE users SET password = ? WHERE id = ?', [hashed, req.user.id]);

        res.json({ message: 'Heslo bylo úspěšně změněno.' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Chyba serveru.' });
    }
});

// POST /api/auth/reset-password — reset hesla bez přihlášení (zapomenuté heslo)
router.post('/reset-password', async (req, res) => {
    const { email, newPassword } = req.body;

    if (!email || !newPassword) {
        return res.status(400).json({ message: 'Email a nové heslo jsou povinné.' });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return res.status(400).json({ message: 'Neplatný formát emailu.' });
    }

    if (typeof newPassword !== 'string' || newPassword.length < 6 || newPassword.length > 100) {
        return res.status(400).json({ message: 'Heslo musí mít 6–100 znaků.' });
    }

    try {
        const [rows] = await db.query('SELECT id FROM users WHERE email = ?', [email]);

        if (rows.length === 0) {
            return res.status(404).json({ message: 'Účet s tímto emailem neexistuje.' });
        }

        const hashed = await bcrypt.hash(newPassword, 10);
        await db.query('UPDATE users SET password = ? WHERE email = ?', [hashed, email]);

        res.json({ message: 'Heslo bylo úspěšně změněno.' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Chyba serveru.' });
    }
});

module.exports = router;
