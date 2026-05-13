const express = require('express');
const router = express.Router();
const db = require('../db');
const { authMiddleware, adminOnly } = require('../middleware/auth');

// GET /api/reservations — moje rezervace (nebo všechny pro admina)
router.get('/', authMiddleware, async (req, res) => {
    try {
        let query, params;

        if (req.user.role === 'admin') {
            query = `
                SELECT r.*, u.name as user_name, u.email, f.name as facility_name, f.type
                FROM reservations r
                JOIN users u ON r.user_id = u.id
                JOIN facilities f ON r.facility_id = f.id
                ORDER BY r.date, r.time_from
            `;
            params = [];
        } else {
            query = `
                SELECT r.*, f.name as facility_name, f.type
                FROM reservations r
                JOIN facilities f ON r.facility_id = f.id
                WHERE r.user_id = ?
                ORDER BY r.date, r.time_from
            `;
            params = [req.user.id];
        }

        const [rows] = await db.query(query, params);
        res.json(rows);
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// POST /api/reservations — vytvoření rezervace
router.post('/', authMiddleware, async (req, res) => {
    const { facility_id, date, time_from, time_to, people_count = 1 } = req.body;

    if (!facility_id || !date || !time_from || !time_to) {
        return res.status(400).json({ error: 'Vyplňte sportoviště, datum a čas' });
    }

    if (!Number.isInteger(Number(facility_id)) || Number(facility_id) < 1) {
        return res.status(400).json({ error: 'Neplatné sportoviště' });
    }

    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(date) || isNaN(Date.parse(date))) {
        return res.status(400).json({ error: 'Neplatný formát data (očekáváno YYYY-MM-DD)' });
    }

    if (new Date(date) < new Date(new Date().toDateString())) {
        return res.status(400).json({ error: 'Nelze rezervovat v minulosti' });
    }

    const timeRegex = /^\d{2}:\d{2}$/;
    if (!timeRegex.test(time_from) || !timeRegex.test(time_to)) {
        return res.status(400).json({ error: 'Neplatný formát času (očekáváno HH:MM)' });
    }

    if (time_from >= time_to) {
        return res.status(400).json({ error: 'Čas od musí být před časem do' });
    }

    const peopleNum = parseInt(people_count);
    if (isNaN(peopleNum) || peopleNum < 1 || peopleNum > 10000) {
        return res.status(400).json({ error: 'Neplatný počet osob' });
    }

    try {
        const [facilities] = await db.query('SELECT * FROM facilities WHERE id = ?', [facility_id]);
        if (facilities.length === 0) {
            return res.status(404).json({ error: 'Sportoviště nenalezeno' });
        }
        const facility = facilities[0];

        if (peopleNum > facility.capacity) {
            return res.status(400).json({
                error: `Počet osob (${peopleNum}) překračuje kapacitu sportoviště (${facility.capacity})`
            });
        }

        // Zkontroluj každou hodinu v rezervovaném rozsahu samostatně
        const fromHour = parseInt(time_from.split(':')[0]);
        const toHour   = parseInt(time_to.split(':')[0]);

        for (let h = fromHour; h < toHour; h++) {
            const slotStart = `${String(h).padStart(2, '0')}:00`;
            const slotEnd   = `${String(h + 1).padStart(2, '0')}:00`;

            const [[{ booked }]] = await db.query(`
                SELECT COALESCE(SUM(people_count), 0) AS booked
                FROM reservations
                WHERE facility_id = ?
                  AND date = ?
                  AND status = 'active'
                  AND time_from < ?
                  AND time_to   > ?
            `, [facility_id, date, slotEnd, slotStart]);

            const available = facility.capacity - booked;
            if (available < peopleNum) {
                return res.status(409).json({
                    error: `V čase ${slotStart}–${slotEnd} zbývá jen ${available} míst (obsazeno ${booked}/${facility.capacity}).`
                });
            }
        }

        const [result] = await db.query(
            'INSERT INTO reservations (user_id, facility_id, date, time_from, time_to, people_count) VALUES (?, ?, ?, ?, ?, ?)',
            [req.user.id, facility_id, date, time_from, time_to, peopleNum]
        );

        res.status(201).json({ message: 'Rezervace vytvořena', id: result.insertId });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

// DELETE /api/reservations/:id — zrušení rezervace
router.delete('/:id', authMiddleware, async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM reservations WHERE id = ?', [req.params.id]);
        const reservation = rows[0];

        if (!reservation) {
            return res.status(404).json({ error: 'Rezervace nenalezena' });
        }

        // Uživatel může rušit jen své rezervace, admin může vše
        if (req.user.role !== 'admin' && reservation.user_id !== req.user.id) {
            return res.status(403).json({ error: 'Nemáte oprávnění zrušit tuto rezervaci' });
        }

        await db.query('UPDATE reservations SET status = ? WHERE id = ?', ['cancelled', req.params.id]);
        res.json({ message: 'Rezervace zrušena' });
    } catch (err) {
        res.status(500).json({ error: 'Chyba serveru' });
    }
});

module.exports = router;
