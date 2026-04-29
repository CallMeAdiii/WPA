// js/script.js – SportHub frontend logika

document.addEventListener('DOMContentLoaded', () => {

    /* ============================================================
       REZERVACE – živý souhrn + kontrola dostupnosti
       ============================================================ */
    const dateInput     = document.getElementById('date');
    const timeFromInput = document.getElementById('time_from');
    const timeToInput   = document.getElementById('time_to');
    const summaryDate   = document.getElementById('summaryDate');
    const summaryTime   = document.getElementById('summaryTime');
    const summaryDur    = document.getElementById('summaryDuration');

    // Aktualizuj souhrn při změně polí
    if (dateInput && timeFromInput && timeToInput) {
        [dateInput, timeFromInput, timeToInput].forEach(el => {
            el.addEventListener('change', updateSummary);
            el.addEventListener('input',  updateSummary);
        });
    }

    function updateSummary() {
        const date     = dateInput?.value     ?? '';
        const timeFrom = timeFromInput?.value ?? '';
        const timeTo   = timeToInput?.value   ?? '';

        // Datum
        if (summaryDate) {
            summaryDate.textContent = date ? formatDateCZ(date) : '—';
        }

        // Čas a délka
        if (summaryTime) {
            summaryTime.textContent = (timeFrom && timeTo) ? `${timeFrom} – ${timeTo}` : '—';
        }

        if (summaryDur) {
            if (timeFrom && timeTo) {
                const mins = calcDuration(timeFrom, timeTo);
                summaryDur.textContent = mins > 0 ? formatDuration(mins) : '—';
            } else {
                summaryDur.textContent = '—';
            }
        }

        // Zkontroluj dostupnost po krátké pauze (debounce)
        clearTimeout(window._availTimer);
        if (date && timeFrom && timeTo && calcDuration(timeFrom, timeTo) > 0) {
            window._availTimer = setTimeout(() => checkAvailability(date, timeFrom, timeTo), 500);
        }
    }

    // Živá kontrola dostupnosti přes fetch
    function checkAvailability(date, timeFrom, timeTo) {
        const params = new URLSearchParams(window.location.search);
        const facilityId = params.get('id');
        if (!facilityId) return;

        fetch(`check-availability.php?id=${facilityId}&date=${date}&time_from=${timeFrom}&time_to=${timeTo}`)
            .then(r => r.json())
            .then(data => {
                updateAvailBadge(data.available);
            })
            .catch(() => {
                // Tiché selhání – nevadí, server to zkontroluje při submit
            });
    }

    // Zobraz/skryj badge dostupnosti
    function updateAvailBadge(available) {
        let badge = document.getElementById('availBadge');

        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'availBadge';
            const submitBtn = document.querySelector('#reservationForm .btn-primary');
            if (submitBtn) submitBtn.before(badge);
        }

        if (available) {
            badge.className = 'avail-ok';
            badge.innerHTML = '<span class="avail-dot"></span>Termín je volný';
        } else {
            badge.className = 'avail-error';
            badge.innerHTML = '<span class="avail-error-dot"></span>Termín je obsazený';
        }

        // Disable/enable submit
        const submitBtn = document.querySelector('#reservationForm .btn-primary');
        if (submitBtn) {
            submitBtn.disabled = !available;
            submitBtn.style.opacity = available ? '1' : '0.5';
            submitBtn.style.cursor  = available ? 'pointer' : 'not-allowed';
        }
    }

    /* ============================================================
       SPORTOVIŠTĚ – filtrování karet (jen vizuální, záložní k PHP)
       ============================================================ */
    const pills = document.querySelectorAll('.filter-pills .pill');
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
        });
    });

    /* ============================================================
       UTILITY
       ============================================================ */

    // Délka v minutách mezi dvěma časy "HH:MM"
    function calcDuration(from, to) {
        const [fh, fm] = from.split(':').map(Number);
        const [th, tm] = to.split(':').map(Number);
        return (th * 60 + tm) - (fh * 60 + fm);
    }

    // Formát délky
    function formatDuration(mins) {
        if (mins < 60) return `${mins} minut`;
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return m > 0 ? `${h} h ${m} min` : `${h} hodina${h > 1 ? (h < 5 ? 'y' : '') : ''}`;
    }

    // Formát data Y-m-d → d. m. YYYY (česky)
    function formatDateCZ(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        return `${d}. ${m}. ${y}`;
    }

});
