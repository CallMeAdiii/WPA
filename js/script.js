// js/script.js – SportHub frontend logika

document.addEventListener('DOMContentLoaded', () => {

    /* ============================================================
       SPORTOVIŠTĚ – filtrování karet (jen vizuální)
       ============================================================ */
    const pills = document.querySelectorAll('.filter-pills .pill');
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
        });
    });

    /* ============================================================
       REZERVACE – slot kalendář
       ============================================================ */
    const slotsGrid    = document.getElementById('slotsGrid');
    const slotsLoading = document.getElementById('slotsLoading');
    const dateInput    = document.getElementById('date');
    const timeFromInput = document.getElementById('time_from');
    const timeToInput   = document.getElementById('time_to');
    const peopleCard   = document.getElementById('peopleCard');
    const summaryCard  = document.getElementById('summaryCard');
    const submitBtn    = document.getElementById('submitBtn');

    if (!slotsGrid) return; // stránka bez slot gridu

    let currentSlots   = window.__slots || null;
    let selectedStart  = null; // první kliknutý slot (int)
    let selectedEnd    = null; // druhý kliknutý slot (int), null = jen 1 hodina
    let currentPeople  = 1;

    // ── Načtení slotů pro dané datum ───────────────────────────
    function loadSlots(date) {
        if (!date) return;
        const facilityId = window.__facilityId;
        slotsGrid.innerHTML    = '';
        slotsLoading.style.display = 'block';
        selectedStart = null;
        selectedEnd   = null;
        updateFormState();

        fetch(`facility-slots.php?id=${facilityId}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                slotsLoading.style.display = 'none';
                if (data.slots) {
                    currentSlots = data;
                    renderSlots(data.slots);
                } else {
                    slotsGrid.innerHTML = '<p class="slots-error">Nepodařilo se načíst termíny.</p>';
                }
            })
            .catch(() => {
                slotsLoading.style.display = 'none';
                slotsGrid.innerHTML = '<p class="slots-error">Chyba připojení.</p>';
            });
    }

    // ── Vykreslení slot gridu ───────────────────────────────────
    function renderSlots(slots) {
        slotsGrid.innerHTML = '';
        slots.forEach(slot => {
            const hour   = parseInt(slot.time);
            const pct    = slot.capacity > 0 ? (slot.booked / slot.capacity) * 100 : 0;
            const cls    = slotClass(pct, slot.available);
            const isFull = slot.available === 0;

            const row = document.createElement('div');
            row.className  = `slot-row ${cls}${isFull ? ' slot-full' : ''}`;
            row.dataset.hour      = hour;
            row.dataset.available = slot.available;
            row.innerHTML = `
                <span class="slot-time">${slot.time}</span>
                <div class="slot-bar-wrap">
                    <div class="slot-bar-fill" style="width:${pct.toFixed(1)}%"></div>
                </div>
                <span class="slot-numbers">${isFull ? 'PLNO' : `${slot.booked}/${slot.capacity}`}</span>
            `;

            if (!isFull) {
                row.addEventListener('click', () => handleSlotClick(hour));
            }
            slotsGrid.appendChild(row);
        });

        // Hint
        const hint = document.createElement('p');
        hint.className   = 'slot-hint';
        hint.id          = 'slotHint';
        hint.textContent = 'Klikni na začátek rezervace';
        slotsGrid.appendChild(hint);

        applySelectionHighlight();
    }

    function slotClass(pct, available) {
        if (available === 0) return 'slot-full';
        if (pct === 0)       return 'slot-empty';
        if (pct < 50)        return 'slot-low';
        if (pct < 80)        return 'slot-medium';
        return 'slot-high';
    }

    // ── Klik na slot ────────────────────────────────────────────
    function handleSlotClick(hour) {
        if (selectedStart === null) {
            // Žádný výběr → nastav začátek
            selectedStart = hour;
            selectedEnd   = null;
        } else if (selectedEnd === null) {
            if (hour === selectedStart) {
                // Klik na stejný slot → odznač
                selectedStart = null;
            } else if (hour > selectedStart) {
                // Zkontroluj jestli v rozsahu není plný slot
                const hasBlock = currentSlots?.slots.some(s => {
                    const h = parseInt(s.time);
                    return h > selectedStart && h <= hour && s.available === 0;
                });
                if (hasBlock) {
                    // Přes plný slot nelze → reset na nový začátek
                    selectedStart = hour;
                } else {
                    selectedEnd = hour;
                }
            } else {
                // Klik před začátkem → nový začátek
                selectedStart = hour;
            }
        } else {
            // Rozsah už vybrán → začni znovu
            selectedStart = hour;
            selectedEnd   = null;
        }
        applySelectionHighlight();
        updateFormState();
    }

    // ── Zvýraznění vybraného rozsahu ────────────────────────────
    function applySelectionHighlight() {
        const end = selectedEnd ?? selectedStart;
        document.querySelectorAll('.slot-row').forEach(row => {
            const h = parseInt(row.dataset.hour);
            row.classList.remove('slot-selected-start', 'slot-selected-end', 'slot-in-range');
            if (selectedStart === null) return;
            if (h === selectedStart)                          row.classList.add('slot-selected-start');
            else if (selectedEnd !== null && h === selectedEnd) row.classList.add('slot-selected-end');
            else if (h > selectedStart && h < end)            row.classList.add('slot-in-range');
        });

        const hint = document.getElementById('slotHint');
        if (hint) {
            if (selectedStart === null)      hint.textContent = 'Klikni na začátek rezervace';
            else if (selectedEnd === null)   hint.textContent = 'Klikni na konec — nebo potvrď kliknutím na stejný slot';
            else                             hint.textContent = `Vybráno ${selectedStart}:00 – ${selectedEnd + 1}:00`;
        }
    }

    // ── Aktualizace formulářových vstupů a UI ───────────────────
    function updateFormState() {
        const hasSelection = selectedStart !== null;
        const end      = selectedEnd ?? selectedStart;
        const timeFrom = hasSelection ? `${String(selectedStart).padStart(2,'0')}:00` : '';
        const timeTo   = hasSelection ? `${String(end + 1).padStart(2,'0')}:00` : '';

        if (timeFromInput) timeFromInput.value = timeFrom;
        if (timeToInput)   timeToInput.value   = timeTo;

        if (peopleCard)  peopleCard.style.display  = hasSelection ? 'block' : 'none';
        if (summaryCard) summaryCard.style.display = hasSelection ? 'block' : 'none';

        if (hasSelection) {
            // Min dostupných míst přes celý vybraný rozsah
            const rangeSlots = currentSlots?.slots.filter(s => {
                const h = parseInt(s.time);
                return h >= selectedStart && h <= end;
            }) || [];
            const minAvail = rangeSlots.reduce((m, s) => Math.min(m, s.available), Infinity);
            updatePeopleMax(isFinite(minAvail) ? minAvail : 1);
            updateSummary(timeFrom, timeTo);
        }

        if (submitBtn) submitBtn.disabled = !hasSelection;
    }

    // ── Aktualizace počtu osob ───────────────────────────────────
    const peopleValInput  = document.getElementById('people_count');
    const peopleMinus     = document.getElementById('peopleMinus');
    const peoplePlus      = document.getElementById('peoplePlus');
    const peopleAvailNote = document.getElementById('peopleAvailNote');

    function updatePeopleMax(max) {
        if (!peopleValInput) return;
        const cap = window.__facilityCapacity || max;
        const effectiveMax = Math.min(max, cap);
        peopleValInput.max = effectiveMax;
        if (parseInt(peopleValInput.value) > effectiveMax) {
            peopleValInput.value = effectiveMax;
            currentPeople = effectiveMax;
        }
        if (peoplePlus) peoplePlus.disabled = (currentPeople >= effectiveMax);
        if (peopleAvailNote) {
            peopleAvailNote.innerHTML = `max <strong>${effectiveMax}</strong> volných míst`;
        }
    }

    if (peopleMinus) {
        peopleMinus.addEventListener('click', () => {
            if (currentPeople > 1) {
                currentPeople--;
                peopleValInput.value = currentPeople;
                syncPeopleButtons();
                updateSummary(timeFromInput.value, timeToInput.value);
            }
        });
    }

    if (peoplePlus) {
        peoplePlus.addEventListener('click', () => {
            const max = parseInt(peopleValInput.max) || window.__facilityCapacity || 99;
            if (currentPeople < max) {
                currentPeople++;
                peopleValInput.value = currentPeople;
                syncPeopleButtons();
                updateSummary(timeFromInput.value, timeToInput.value);
            }
        });
    }

    if (peopleValInput) {
        peopleValInput.addEventListener('change', () => {
            const max = parseInt(peopleValInput.max) || window.__facilityCapacity || 99;
            currentPeople = Math.max(1, Math.min(parseInt(peopleValInput.value) || 1, max));
            peopleValInput.value = currentPeople;
            syncPeopleButtons();
            updateSummary(timeFromInput.value, timeToInput.value);
        });
    }

    function syncPeopleButtons() {
        const max = parseInt(peopleValInput?.max) || 99;
        if (peopleMinus) peopleMinus.disabled = (currentPeople <= 1);
        if (peoplePlus)  peoplePlus.disabled  = (currentPeople >= max);
    }

    // ── Souhrn ──────────────────────────────────────────────────
    function updateSummary(timeFrom, timeTo) {
        const date = dateInput?.value || '';

        const summaryDate = document.getElementById('summaryDate');
        const summaryTime = document.getElementById('summaryTime');
        const summaryDur  = document.getElementById('summaryDuration');
        const summaryPpl  = document.getElementById('summaryPeople');

        if (summaryDate) summaryDate.textContent = date ? formatDateCZ(date) : '—';
        if (summaryTime) summaryTime.textContent = (timeFrom && timeTo) ? `${timeFrom} – ${timeTo}` : '—';
        if (summaryDur)  summaryDur.textContent  = (timeFrom && timeTo) ? formatDuration(calcDuration(timeFrom, timeTo)) : '—';

        if (summaryPpl) {
            const cap = window.__facilityCapacity || '?';
            summaryPpl.textContent = `${currentPeople} os. / ${cap} kapacita`;
        }
    }

    // ── Date change → reload slots ───────────────────────────────
    if (dateInput) {
        dateInput.addEventListener('change', () => loadSlots(dateInput.value));
    }

    // ── Inicializace s daty ze stránky ───────────────────────────
    if (currentSlots && currentSlots.slots) {
        slotsLoading.style.display = 'none';
        renderSlots(currentSlots.slots);
        // Předvyber sloty při editaci
        if (window.__selectedStart !== undefined) {
            selectedStart = window.__selectedStart;
            selectedEnd   = window.__selectedEnd ?? null;
            applySelectionHighlight();
            updateFormState();
        }
    } else if (dateInput?.value) {
        loadSlots(dateInput.value);
    }

    /* ============================================================
       UTILITY
       ============================================================ */
    function calcDuration(from, to) {
        const [fh, fm] = from.split(':').map(Number);
        const [th, tm] = to.split(':').map(Number);
        return (th * 60 + tm) - (fh * 60 + fm);
    }

    function formatDuration(mins) {
        if (mins <= 0) return '—';
        if (mins < 60) return `${mins} min`;
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        const label = h === 1 ? 'hodina' : (h < 5 ? 'hodiny' : 'hodin');
        return m > 0 ? `${h} h ${m} min` : `${h} ${label}`;
    }

    function formatDateCZ(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        return `${d}. ${m}. ${y}`;
    }

});
