const express = require('express');
const router = express.Router();
const db = require('../db');
const { authMiddleware, adminOnly } = require('../middleware/auth');

const ALLOWED_TYPES = ['tělocvična', 'posilovna', 'ovál', 'hřiště'];

// GET /api/facilities — výpis sportovišť (s volitelným filtrováním)
router.get('/', authMiddleware, async (req, res) => {
    const { type, capacity, location } = req.query;

    let query = 'SELECT * FROM facilities WHERE 1=1';
    const params = [];

    if (type) {
        if (!ALLOWED_TYPES.includes(type)) {
            return res.status(400).json({ error: `Neplatný typ. Povolené hodnoty: ${ALLOWED_TYPES.join(', ')}` });
        }
        query += ' AND type = ?';
        params.push(type);
    }

    if (capacity) {
        query += ' AND capacity >= ?';
        params.push(parseInt(capacity));
    }

    if (location) {
        query += ' AND location LIKE ?';
        params.push(`%${location}%`);
    }

    try {
        const [rows] = await db.query(query, params);
        res.json(rows);
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// GET /api/facilities/:id — detail sportoviště
router.get('/:id', authMiddleware, async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM facilities WHERE id = ?', [req.params.id]);
        if (rows.length === 0) return res.status(404).json({ error: 'Sportoviště nenalezeno' });
        res.json(rows[0]);
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// POST /api/facilities — přidání sportoviště (pouze admin)
router.post('/', authMiddleware, adminOnly, async (req, res) => {
    const { name, type, description, capacity, location } = req.body;

    if (!name || !type || !capacity) {
        return res.status(400).json({ error: 'Vyplňte název, typ a kapacitu' });
    }

    if (typeof name !== 'string' || name.trim().length < 2 || name.trim().length > 100) {
        return res.status(400).json({ error: 'Název musí mít 2–100 znaků' });
    }

    if (!ALLOWED_TYPES.includes(type)) {
        return res.status(400).json({ error: `Neplatný typ. Povolené hodnoty: ${ALLOWED_TYPES.join(', ')}` });
    }

    const capacityNum = parseInt(capacity);
    if (isNaN(capacityNum) || capacityNum < 1 || capacityNum > 10000) {
        return res.status(400).json({ error: 'Kapacita musí být číslo mezi 1 a 10000' });
    }

    if (description && typeof description !== 'string') {
        return res.status(400).json({ error: 'Neplatný popis' });
    }

    if (location && (typeof location !== 'string' || location.trim().length > 100)) {
        return res.status(400).json({ error: 'Umístění může mít max. 100 znaků' });
    }

    try {
        const [result] = await db.query(
            'INSERT INTO facilities (name, type, description, capacity, location) VALUES (?, ?, ?, ?, ?)',
            [name.trim(), type, description?.trim() || null, capacityNum, location?.trim() || null]
        );
        res.status(201).json({ message: 'Sportoviště přidáno', id: result.insertId });
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// DELETE /api/facilities/:id — smazání sportoviště (pouze admin)
router.delete('/:id', authMiddleware, adminOnly, async (req, res) => {
    try {
        const [result] = await db.query('DELETE FROM facilities WHERE id = ?', [req.params.id]);
        if (result.affectedRows === 0) return res.status(404).json({ error: 'Sportoviště nenalezeno' });
        res.json({ message: 'Sportoviště smazáno' });
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

module.exports = router;
