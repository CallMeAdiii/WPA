const express = require('express');
const router = express.Router();
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const crypto = require('crypto');
const nodemailer = require('nodemailer');
const db = require('../db');
const { authMiddleware } = require('../middleware/auth');

const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: process.env.GMAIL_USER,
        pass: process.env.GMAIL_PASS,
    },
});

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

        if (!user) return res.status(404).json({ message: 'Uživatel nenalezen.' });

        const match = await bcrypt.compare(currentPassword, user.password);
        if (!match) return res.status(401).json({ message: 'Současné heslo je nesprávné.' });
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

// POST /api/auth/forgot-password — odeslání emailu s reset odkazem
router.post('/forgot-password', async (req, res) => {
    const { email } = req.body;

    if (!email) return res.status(400).json({ message: 'Zadejte email.' });

    try {
        const [rows] = await db.query('SELECT id, name FROM users WHERE email = ?', [email]);

        // Vždy vrátit 200 – nechceme prozradit jestli email existuje
        if (rows.length === 0) {
            return res.json({ message: 'Pokud účet existuje, byl odeslán email s odkazem.' });
        }

        const user = rows[0];
        const token = crypto.randomBytes(32).toString('hex');
        const expiresAt = new Date(Date.now() + 60 * 60 * 1000); // 1 hodina

        // Smaž staré tokeny pro tohoto uživatele
        await db.query('DELETE FROM password_reset_tokens WHERE user_id = ?', [user.id]);
        await db.query(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)',
            [user.id, token, expiresAt]
        );

        const resetUrl = `${process.env.FRONTEND_URL}/reset-hesla.php?token=${token}`;

        await transporter.sendMail({
            from: `"SportHub" <${process.env.GMAIL_USER}>`,
            to: email,
            subject: 'SportHub – Reset hesla',
            html: `
                <div style="font-family:sans-serif;max-width:480px;margin:auto;">
                    <h2 style="color:#16a34a;">SportHub</h2>
                    <p>Ahoj <strong>${user.name}</strong>,</p>
                    <p>obdrželi jsme žádost o reset hesla pro tvůj účet.</p>
                    <p style="margin:24px 0;">
                        <a href="${resetUrl}"
                           style="background:#16a34a;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">
                            Resetovat heslo
                        </a>
                    </p>
                    <p style="color:#888;font-size:13px;">Odkaz je platný 1 hodinu. Pokud jsi o reset nepožádal/a, tento email ignoruj.</p>
                </div>
            `,
        });

        res.json({ message: 'Pokud účet existuje, byl odeslán email s odkazem.' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Chyba serveru.' });
    }
});

// POST /api/auth/reset-password/:token — nastavení nového hesla přes token
router.post('/reset-password/:token', async (req, res) => {
    const { token } = req.params;
    const { newPassword } = req.body;

    if (!newPassword) return res.status(400).json({ message: 'Zadejte nové heslo.' });
    if (typeof newPassword !== 'string' || newPassword.length < 6 || newPassword.length > 100) {
        return res.status(400).json({ message: 'Heslo musí mít 6–100 znaků.' });
    }

    try {
        const [rows] = await db.query(
            'SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()',
            [token]
        );

        if (rows.length === 0) {
            return res.status(400).json({ message: 'Odkaz je neplatný nebo vypršel.' });
        }

        const resetToken = rows[0];
        const hashed = await bcrypt.hash(newPassword, 10);

        await db.query('UPDATE users SET password = ? WHERE id = ?', [hashed, resetToken.user_id]);
        await db.query('UPDATE password_reset_tokens SET used = 1 WHERE id = ?', [resetToken.id]);

        res.json({ message: 'Heslo bylo úspěšně změněno.' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Chyba serveru.' });
    }
});

module.exports = router;
