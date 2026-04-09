<h1 class="booking__heading">Book a Session</h1>
<p class="booking__subheading">Select a date and time below to reserve your slot.</p>

<div class="booking__layout">
    <form method="POST" id="booking-form" class="booking__calendar-col">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="calendar">
            <div class="calendar__header">
                <button type="button" class="calendar__nav" id="cal-prev">&#8249;</button>
                <span class="calendar__month-year" id="cal-title"></span>
                <button type="button" class="calendar__nav" id="cal-next">&#8250;</button>
            </div>
            <div class="calendar__grid">
                <div class="calendar__day-name">Mon</div>
                <div class="calendar__day-name">Tue</div>
                <div class="calendar__day-name">Wed</div>
                <div class="calendar__day-name">Thu</div>
                <div class="calendar__day-name">Fri</div>
                <div class="calendar__day-name">Sat</div>
                <div class="calendar__day-name">Sun</div>
            </div>
            <div class="calendar__days" id="cal-days"></div>
        </div>

        <div id="cal-time-picker" style="display:none; margin-top:1.25rem;">
            <label style="color:#999; font-size:0.85rem; display:block; margin-bottom:0.5rem;">Select a time slot</label>
            <div class="calendar__slots" id="cal-slots">
                <div class="calendar__slot" data-time="10:00:00">10am</div>
                <div class="calendar__slot" data-time="11:00:00">11am</div>
                <div class="calendar__slot" data-time="12:00:00">12pm</div>
                <div class="calendar__slot" data-time="13:00:00">1pm</div>
                <div class="calendar__slot" data-time="14:00:00">2pm</div>
                <div class="calendar__slot" data-time="15:00:00">3pm</div>
                <div class="calendar__slot" data-time="16:00:00">4pm</div>
                <div class="calendar__slot" data-time="17:00:00">5pm</div>
                <div class="calendar__slot" data-time="18:00:00">6pm</div>
            </div>
        </div>

        <div id="cal-reason-picker" style="display:none; margin-top:1.25rem;">
            <label style="color:#999; font-size:0.85rem; display:block; margin-bottom:0.5rem;">Reason for visit</label>
            <select name="reason" id="booking-reason-input" class="admin__select admin__select--wide">
                <option value="">— Select a reason —</option>
                <option value="Initial Consultation">Initial Consultation</option>
                <option value="Follow-up Appointment">Follow-up Appointment</option>
                <option value="Document Review">Document Review</option>
                <option value="Legal Advice">Legal Advice</option>
                <option value="Financial Planning">Financial Planning</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <input type="hidden" name="date" id="booking-date-input" value="">
        <input type="hidden" name="time" id="booking-time-input" value="">
        <p id="cal-summary" style="display:none; color:#999; font-size:0.9rem; margin-top:1.25rem;"></p>
        <button type="submit" class="main__btn main__btn--dark" id="cal-submit" style="display:none; margin: 0.75rem 0 0 0;">Confirm Booking</button>
        <a href="<?= BASE_URL ?>/index.php" class="main__btn main__btn--dark" style="margin: 0.75rem 0 0 0; align-self:flex-start;">Back to Home</a>
    </form>

    <div class="booking__info-col">
        <h2 class="booking__info-title">How it works</h2>
        <p class="booking__info-text">Select a date and time that works for you, choose a reason for your visit, and submit your request. We'll review it and send you a confirmation email once it's approved.</p>
        <ul class="booking__info-list">
            <li>Bookings must be made at least one day in advance</li>
            <li>Appointments are available Monday to Friday, 10am – 6pm</li>
            <li>You can hold up to two active bookings at a time</li>
            <li>Cancellations within 24 hours must be made by contacting us directly</li>
        </ul>
        <p class="booking__info-text">Once confirmed, you'll receive a reminder email the day before your appointment and your booking will appear <a href="<?=  BASE_URL ?>/account.php ?>">on you User Profile</a>. Please note that all bookings are subject to our review and acceptance.</p>
        <p class="booking__info-text" style="color:var(--text-muted); font-size:0.8rem;">Booking confirmations are subject to our terms of service. We reserve the right to reschedule or decline appointments where necessary.</p>
    </div>
</div>

<script>
    const title = document.getElementById('cal-title');
    const daysContainer = document.getElementById('cal-days');
    const dateInput = document.getElementById('booking-date-input');
    const timeInput = document.getElementById('booking-time-input');
    const submitBtn = document.getElementById('cal-submit');
    const timePicker = document.getElementById('cal-time-picker');
    const reasonPicker = document.getElementById('cal-reason-picker');
    const reasonInput = document.getElementById('booking-reason-input');
    const summary = document.getElementById('cal-summary');

    function updateSummary() {
        if (!dateInput.value || !timeInput.value) { summary.style.display = 'none'; return; }
        const d = new Date(dateInput.value + 'T00:00:00');
        const dayName = d.toLocaleString('default', { weekday: 'long' });
        const day = d.getDate();
        const month = d.toLocaleString('default', { month: 'long' });
        const suffix = (day === 1 || day === 21 || day === 31) ? 'st' : (day === 2 || day === 22) ? 'nd' : (day === 3 || day === 23) ? 'rd' : 'th';
        const slotLabel = document.querySelector(`.calendar__slot[data-time="${timeInput.value}"]`)?.textContent ?? '';
        summary.textContent = `${dayName} ${day}${suffix} ${month}, ${slotLabel}`;
        summary.style.display = 'block';
    }

    document.getElementById('cal-slots').addEventListener('click', (e) => {
        const slot = e.target.closest('.calendar__slot');
        if (!slot || slot.classList.contains('calendar__slot--taken')) return;
        document.querySelectorAll('.calendar__slot--selected').forEach(el => el.classList.remove('calendar__slot--selected'));
        slot.classList.add('calendar__slot--selected');
        timeInput.value = slot.dataset.time;
        updateSummary();
        reasonPicker.style.display = 'block';
        submitBtn.style.display = 'block';
    });

    const bookedDates = new Set(<?= json_encode($booked_dates ?? []) ?>);
    const today = new Date();
    const maxDate = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate());
    let current = new Date();

    function renderCalendar() {
        const year = current.getFullYear();
        const month = current.getMonth();

        title.textContent = current.toLocaleString('default', { month: 'long', year: 'numeric' });
        daysContainer.innerHTML = '';
        dateInput.value = '';
        timeInput.value = '';
        submitBtn.style.display = 'none';
        timePicker.style.display = 'none';
        reasonPicker.style.display = 'none';
        reasonInput.value = '';
        document.querySelectorAll('.calendar__slot--selected').forEach(el => el.classList.remove('calendar__slot--selected'));

        const isMaxMonth = year === maxDate.getFullYear() && month === maxDate.getMonth();
        const isMinMonth = year === today.getFullYear() && month === today.getMonth();
        document.getElementById('cal-prev').disabled = isMinMonth;
        document.getElementById('cal-next').disabled = isMaxMonth;

        const firstDay = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();

        const offset = (firstDay === 0) ? 6 : firstDay - 1;
        for (let i = 0; i < offset; i++) {
            daysContainer.innerHTML += '<div></div>';
        }

        for (let d = 1; d <= totalDays; d++) {
            const date = new Date(year, month, d);
            const dayOfWeek = date.getDay();
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            const isToday = date.toDateString() === today.toDateString();
            const isPast = date <= new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const isBeyondMax = date > maxDate;
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const isBooked = bookedDates.has(dateStr);

            const cell = document.createElement('div');
            cell.textContent = d;
            cell.classList.add('calendar__cell');
            if (isToday) cell.classList.add('calendar__cell--today');
            if (isBooked) cell.classList.add('calendar__cell--booked');
            if (isWeekend || isPast || isBeyondMax) cell.classList.add('calendar__cell--disabled');

            if (!isWeekend && !isPast && !isBeyondMax) {
                cell.addEventListener('click', () => {
                    document.querySelectorAll('.calendar__cell--selected').forEach(el => el.classList.remove('calendar__cell--selected'));
                    cell.classList.add('calendar__cell--selected');

                    const y = year;
                    const m = String(month + 1).padStart(2, '0');
                    const day = String(d).padStart(2, '0');
                    dateInput.value = `${y}-${m}-${day}`;

                    timeInput.value = '';
                    submitBtn.style.display = 'none';
                    summary.style.display = 'none';
                    reasonPicker.style.display = 'none';
                    reasonInput.value = '';
                    document.querySelectorAll('.calendar__slot--selected').forEach(el => el.classList.remove('calendar__slot--selected'));

                    timePicker.style.display = 'block';
                });
            }

            daysContainer.appendChild(cell);
        }
    }

    document.getElementById('cal-prev').addEventListener('click', () => {
        current.setMonth(current.getMonth() - 1);
        renderCalendar();
    });

    document.getElementById('cal-next').addEventListener('click', () => {
        current.setMonth(current.getMonth() + 1);
        renderCalendar();
    });

    renderCalendar();
</script>
