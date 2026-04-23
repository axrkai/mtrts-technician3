<style>
/* ═══════════════════════════════════════════════════════════════
   Technician Ops — shared styles (redesigned)
   Font: DM Sans (body) + IBM Plex Mono (numbers/codes)
   ═══════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;700&family=DM+Sans:wght@400;500;600&display=swap');

/* ── Design tokens ──────────────────────────────────────────── */
:root {
  --tech-green:      #3B6D11;
  --tech-green-lt:   #EAF3DE;
  --tech-green-mid:  #639922;
  --tech-green-dk:   #27500A;
  --tech-green-bd:   #C0DD97;

  --tech-red:        #A32D2D;
  --tech-red-lt:     #FCEBEB;
  --tech-red-bd:     #F7C1C1;

  --tech-amber:      #854F0B;
  --tech-amber-lt:   #FAEEDA;
  --tech-amber-bd:   #F5C89A;

  --tech-blue:       #185FA5;
  --tech-blue-lt:    #E6F1FB;
  --tech-blue-bd:    #B5D4F4;

  --tech-gray-900:   #1a1a1a;
  --tech-gray-800:   #2c2c2c;
  --tech-gray-700:   #3f3f3f;
  --tech-gray-600:   #525252;
  --tech-gray-500:   #6b6b6b;
  --tech-gray-400:   #9a9a9a;
  --tech-gray-300:   #c4c4c4;
  --tech-gray-200:   #e5e5e5;
  --tech-gray-100:   #f0f0ee;
  --tech-gray-50:    #f7f7f5;

  --tech-surface:    #ffffff;
  --tech-page:       #f7f7f5;

  --tech-radius:     10px;
  --tech-radius-lg:  14px;
  --tech-radius-xl:  18px;

  --tech-mono: 'IBM Plex Mono', ui-monospace, Menlo, monospace;
  --tech-sans: 'DM Sans', system-ui, sans-serif;
}

/* ── Sync status pill ───────────────────────────────────────── */
.sync-badge {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-200);
  border-radius: 8px;
  padding: 6px 11px;
  font-size: 12px; color: var(--tech-gray-500); font-weight: 500;
  font-family: var(--tech-sans);
}
.sync-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  display: inline-block;
  background: #F59E0B;
  flex-shrink: 0;
}
.sync-dot.sync-dot-checking { background: #F59E0B; }
.sync-dot.sync-dot-online   { background: #22C55E; }
.sync-dot.sync-dot-offline  { background: #EF4444; }

/* ── WO Header card ─────────────────────────────────────────── */
.wo-header-card {
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-200);
  border-radius: var(--tech-radius-xl);
  padding: 22px 24px 18px;
  margin-bottom: 12px;
  position: relative;
  overflow: hidden;
}
.wo-header-card__accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--tech-green) 0%, var(--tech-green-mid) 100%);
}

/* ── WO number pill ─────────────────────────────────────────── */
.vf-mono {
  font-family: var(--tech-mono);
  font-size: 11.5px; font-weight: 700;
  color: var(--tech-green);
  background: var(--tech-green-lt);
  padding: 3px 9px; border-radius: 6px;
  letter-spacing: .5px;
}

/* ── Status badges ──────────────────────────────────────────── */
.wo-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11.5px; font-weight: 600;
  padding: 3px 9px; border-radius: 6px;
  font-family: var(--tech-sans);
}
.bdot {
  width: 6px; height: 6px; border-radius: 50%; display: inline-block; flex-shrink: 0;
}
.badge-new        { background: var(--tech-gray-100); color: var(--tech-gray-700); }
.badge-new        .bdot { background: var(--tech-gray-400); }
.badge-assigned   { background: var(--tech-blue-lt);  color: var(--tech-blue); }
.badge-assigned   .bdot { background: var(--tech-blue); }
.badge-scheduled  { background: #EFF6FF; color: #1D4ED8; }
.badge-scheduled  .bdot { background: #3B82F6; }
.badge-in_progress{ background: #FEF3C7; color: #92400E; }
.badge-in_progress .bdot { background: #D97706; }
.badge-on_hold    { background: #FDF2F8; color: #9D174D; }
.badge-on_hold    .bdot { background: #EC4899; }
.badge-resolved   { background: var(--tech-green-lt); color: var(--tech-green-dk); }
.badge-resolved   .bdot { background: var(--tech-green); }
.badge-closed     { background: var(--tech-gray-100); color: var(--tech-gray-700); }
.badge-closed     .bdot { background: var(--tech-gray-400); }

/* ── Metadata strip ─────────────────────────────────────────── */
.vf-lbl {
  font-size: 10.5px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .6px;
  color: var(--tech-gray-400); margin-bottom: 3px;
}
.vf-val {
  font-size: 13px; font-weight: 500; color: var(--tech-gray-800);
  font-family: var(--tech-sans);
}
.vf-empty { color: var(--tech-gray-400); font-style: italic; font-weight: 400; }

/* ── Offline notice ─────────────────────────────────────────── */
.offline-notice {
  display: flex; align-items: center; gap: 8px;
  margin-top: 14px; padding: 9px 13px;
  background: var(--tech-blue-lt);
  border-radius: 8px;
  font-size: 12px; color: var(--tech-blue); font-weight: 500;
}
.offline-notice svg { width: 14px; height: 14px; flex-shrink: 0; }

/* ── Primary tabs ───────────────────────────────────────────── */
.tab-nav {
  display: flex;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.tab-nav::-webkit-scrollbar { display: none; }

.tab-nav.primary-tabs {
  border-bottom: 1.5px solid var(--tech-gray-100);
  padding: 0 6px;
  background: var(--tech-surface);
}
.primary-tab-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 14px 18px;
  font-size: 14px; font-weight: 500;
  color: var(--tech-gray-400);
  background: transparent; border: none;
  cursor: pointer; position: relative;
  transition: color .15s;
  white-space: nowrap;
  font-family: var(--tech-sans);
  flex-shrink: 0;
  margin-bottom: 0;
  border-bottom: none;
}
.primary-tab-btn:hover { color: var(--tech-gray-800); }
.primary-tab-btn.tab-on {
  color: var(--tech-green);
  font-weight: 600;
}
.primary-tab-btn.tab-on::after {
  content: '';
  position: absolute; bottom: -1.5px; left: 18px; right: 18px;
  height: 2px; background: var(--tech-green);
  border-radius: 2px 2px 0 0;
}
.tab-btn__icon {
  width: 28px; height: 28px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  background: var(--tech-gray-100);
  flex-shrink: 0; transition: background .15s;
}
.tab-btn__icon svg { width: 14px; height: 14px; }
.primary-tab-btn:hover .tab-btn__icon { background: var(--tech-gray-200); }
.primary-tab-btn.tab-on .tab-btn__icon {
  background: var(--tech-green-lt);
  color: var(--tech-green);
}

/* ── Secondary tabs ─────────────────────────────────────────── */
.tab-nav.secondary-tabs {
  display: flex; gap: 2px;
  padding: 8px 16px 0;
  background: var(--tech-gray-50);
  border-bottom: 1px solid var(--tech-gray-100);
}
.secondary-tab-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px 9px;
  font-size: 12.5px; font-weight: 500;
  color: var(--tech-gray-400);
  background: transparent; border: none;
  border-radius: 8px 8px 0 0;
  cursor: pointer; transition: all .15s;
  white-space: nowrap;
  font-family: var(--tech-sans);
  flex-shrink: 0;
}
.secondary-tab-btn:hover {
  color: var(--tech-gray-800);
  background: rgba(255,255,255,.7);
}
.secondary-tab-btn.tab-on {
  color: var(--tech-green); font-weight: 600;
  background: var(--tech-surface);
  box-shadow:
    0 -1px 0 0 var(--tech-gray-100) inset,
    1px 0 0 0 var(--tech-gray-100) inset,
    -1px 0 0 0 var(--tech-gray-100) inset;
}
.secondary-tab-btn svg { width: 13px; height: 13px; flex-shrink: 0; }
.tab-count-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 99px;
  font-size: 10.5px; font-weight: 700;
  background: var(--tech-gray-200); color: var(--tech-gray-500);
}
.tab-count-badge.done { background: var(--tech-green-lt); color: var(--tech-green-dk); }
.tab-count-badge.warn { background: var(--tech-red-lt);   color: var(--tech-red); }

/* ── Section cards (inner panels) ───────────────────────────── */
.tech-card {
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-100);
  border-radius: var(--tech-radius-lg);
  overflow: hidden;
  margin-bottom: 16px;
}
.tech-card:last-child { margin-bottom: 0; }
.tech-card__head {
  padding: 13px 18px;
  border-bottom: 1px solid var(--tech-gray-100);
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.tech-card__title {
  font-size: 13px; font-weight: 600; color: var(--tech-gray-800);
  display: flex; align-items: center; gap: 8px;
  font-family: var(--tech-sans);
}
.tech-card__title svg { width: 15px; height: 15px; }
.tech-card__meta {
  font-size: 12px; color: var(--tech-gray-400);
  font-family: var(--tech-sans);
}
.tech-card__body { padding: 16px 18px; }

/* ── Progress bars ──────────────────────────────────────────── */
.tech-progress { height: 3px; background: var(--tech-gray-100); }
.tech-progress-fill {
  height: 100%; border-radius: 0;
  transition: width .4s ease;
}
.tech-progress-fill.green { background: var(--tech-green); }
.tech-progress-fill.red   { background: var(--tech-red); }
/* aliases for JS usage */
.cl-progress-fill   { background: var(--tech-green); height: 100%; border-radius: 0; transition: width .4s; }
.safety-progress-fill { height: 100%; border-radius: 0; transition: width .3s; }

/* ── Safety info banner ─────────────────────────────────────── */
.safety-info-banner {
  padding: 10px 18px;
  background: #FFF9F9; border-bottom: 1px solid #FDDEDE;
  display: flex; align-items: center; gap: 8px;
  font-size: 12px; color: var(--tech-red); font-weight: 500;
}
.safety-info-banner svg { width: 14px; height: 14px; flex-shrink: 0; }

/* ── Checklist items ─────────────────────────────────────────── */
.checklist-item {
  display: flex; flex-direction: column;
  padding: 13px 18px;
  transition: background .15s;
  border-bottom: 1px solid var(--tech-gray-100);
}
.checklist-item:last-child { border-bottom: none; }
.checklist-item--done { background: #FAFDF7; }
.checklist-item--safety.checklist-item--done { background: #FFF9F9; }

.checklist-item__row {
  display: flex; align-items: center; gap: 12px; cursor: pointer;
}
.checklist-item__check {
  flex-shrink: 0; width: 20px; height: 20px;
  border-radius: 5px; border: 1.5px solid var(--tech-gray-300);
  background: #fff;
  display: flex; align-items: center; justify-content: center;
  transition: all .15s; cursor: pointer; padding: 0;
}
.checklist-item__check:hover { border-color: var(--tech-green); }
.checklist-item__check--checked {
  background: var(--tech-green); border-color: var(--tech-green);
}
.checklist-item__check--safety:hover { border-color: var(--tech-red); }
.checklist-item__check--safety.checklist-item__check--checked {
  background: var(--tech-red); border-color: var(--tech-red);
}
.checklist-item__tick {
  width: 11px; height: 11px; color: #fff;
  opacity: 0; transition: opacity .15s;
}
.checklist-item__check--checked .checklist-item__tick { opacity: 1; }
.checklist-item__check:disabled { opacity: .6; cursor: not-allowed; }
.checklist-item__check:disabled:hover { border-color: inherit; }

.checklist-item__text {
  flex: 1; font-size: 13px; color: var(--tech-gray-800);
  font-weight: 500; line-height: 1.4; transition: color .15s;
  font-family: var(--tech-sans);
}
.checklist-item--done .checklist-item__text {
  color: var(--tech-gray-400);
  text-decoration: line-through;
  text-decoration-color: var(--tech-gray-300);
}
.checklist-item__badges {
  display: flex; gap: 4px; align-items: center; flex-shrink: 0;
}
.checklist-badge {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 10px; font-weight: 700;
  padding: 2px 6px; border-radius: 5px;
}
.checklist-badge--req   { background: var(--tech-red-lt);  color: var(--tech-red); }
.checklist-badge--photo { background: var(--tech-blue-lt); color: var(--tech-blue); gap: 4px; }
.checklist-badge--photo svg { width: 10px; height: 10px; }
.checklist-item__done-note {
  display: flex; align-items: center; gap: 4px;
  margin-top: 5px; margin-left: 32px;
  font-size: 11px; font-weight: 600; color: var(--tech-green);
}
.checklist-item__done-note--safety { color: var(--tech-red); }

/* ── Form inputs ─────────────────────────────────────────────── */
.fin {
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-200);
  border-radius: var(--tech-radius);
  padding: 9px 12px;
  font-size: 13.5px; color: var(--tech-gray-900);
  font-family: var(--tech-sans);
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.fin::placeholder { color: var(--tech-gray-400); font-weight: 400; }
.fin:focus {
  border-color: var(--tech-green-mid);
  box-shadow: 0 0 0 3px rgba(99,153,34,.1);
}
.fin:disabled {
  background: var(--tech-gray-50); color: var(--tech-gray-400); cursor: not-allowed;
}
select.fin {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' viewBox='0 0 24 24'%3E%3Cpath stroke='%239a9a9a' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 32px;
}
select.fin:focus {
  box-shadow: 0 0 0 3px rgba(99,153,34,.1);
  border-color: var(--tech-green-mid);
}

/* ── Timer display ───────────────────────────────────────────── */
.timer-display {
  text-align: center;
  padding: 20px 16px 16px;
  background: var(--tech-gray-50);
  border-radius: var(--tech-radius-lg);
  border: 1px solid var(--tech-gray-100);
  margin-bottom: 16px;
}
#timerValue {
  font-family: var(--tech-mono);
  font-size: 46px; font-weight: 700;
  color: var(--tech-gray-900);
  letter-spacing: 2px; line-height: 1;
  display: block;
}
.timer-state-label {
  font-size: 11.5px; font-weight: 600;
  color: var(--tech-gray-400);
  margin-top: 6px;
  text-transform: uppercase; letter-spacing: .8px;
  font-family: var(--tech-sans);
  display: block;
}
.timer-state-label.running { color: var(--tech-green); }
.timer-state-label.paused  { color: var(--tech-amber); }

#timerState {
  font-size: 12px; font-weight: 600;
  color: var(--tech-gray-500);
  padding: 2px 8px;
  background: var(--tech-gray-100);
  border-radius: 6px;
  font-family: var(--tech-sans);
}

/* ── Timer action buttons ────────────────────────────────────── */
#btnStart, #btnStop { flex-shrink: 0; white-space: nowrap; }

/* ── Time log entries ─────────────────────────────────────────── */
.timeLog {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 9px 12px !important;
  background: var(--tech-gray-50) !important;
  border: 1px solid var(--tech-gray-100) !important;
  border-radius: 9px !important;
  transition: all .2s !important;
  position: relative !important;
}
.timeLog:hover {
  background: var(--tech-surface) !important;
  border-color: var(--tech-gray-200) !important;
  box-shadow: 0 1px 3px rgba(0,0,0,.04) !important;
}
.timeLog__header { display: contents !important; }
.timeLog__type {
  font-size: 12.5px !important; font-weight: 700 !important;
  color: var(--tech-gray-800) !important;
  text-transform: capitalize !important;
  flex: 1 !important;
  font-family: var(--tech-sans) !important;
}
.timeLog__timestamp {
  font-size: 11px !important; color: var(--tech-gray-400) !important; white-space: nowrap !important;
}
.timeLog__time {
  font-family: var(--tech-mono) !important;
  font-size: 13px !important; font-weight: 700 !important;
  color: var(--tech-green) !important;
  background: var(--tech-green-lt) !important;
  border: 1px solid var(--tech-green-bd) !important;
  border-radius: 6px !important;
  padding: 2px 8px !important;
  white-space: nowrap !important;
}
.timeLog__remove {
  position: static !important; background: none !important; border: none !important;
  color: var(--tech-gray-300) !important; font-size: 16px !important;
  cursor: pointer !important;
  width: 22px !important; height: 22px !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  border-radius: 50% !important;
  transition: color .2s, background .2s !important;
  flex-shrink: 0 !important; padding: 0 !important;
}
.timeLog__remove:hover { color: #ef4444 !important; background: #fee2e2 !important; }

/* ── Notes ────────────────────────────────────────────────────── */
.note {
  padding: 10px 12px !important;
  background: var(--tech-gray-50) !important;
  border: 1px solid var(--tech-gray-100) !important;
  border-radius: 9px !important;
  font-size: 13px !important;
  transition: all .2s !important;
}
.note:hover {
  background: var(--tech-surface) !important;
  border-color: var(--tech-gray-200) !important;
  box-shadow: 0 1px 3px rgba(0,0,0,.04) !important;
}
.note__header {
  display: flex !important; align-items: center !important;
  gap: 8px !important; margin-bottom: 4px !important;
}
.note__title {
  font-size: 13px !important; font-weight: 700 !important;
  color: var(--tech-gray-800) !important; flex: 1 !important;
  font-family: var(--tech-sans) !important;
}
.note__meta { font-size: 11px !important; color: var(--tech-gray-400) !important; white-space: nowrap !important; }
.note__text {
  font-size: 12.5px !important; color: var(--tech-gray-600) !important;
  line-height: 1.55 !important;
}
.note__remove {
  background: none !important; border: none !important;
  color: var(--tech-gray-300) !important; font-size: 16px !important;
  cursor: pointer !important;
  width: 22px !important; height: 22px !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  border-radius: 50% !important;
  transition: color .2s, background .2s !important;
  flex-shrink: 0 !important; padding: 0 !important;
}
.note__remove:hover { color: #ef4444 !important; background: #fee2e2 !important; }

/* ── Parts list items ──────────────────────────────────────────── */
.part-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px;
  background: var(--tech-gray-50);
  border: 1px solid var(--tech-gray-100);
  border-radius: 9px; font-size: 13px; transition: all .2s;
}
.part-item:hover {
  background: var(--tech-surface);
  border-color: var(--tech-gray-200);
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.part-item__icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: var(--tech-green-lt); color: var(--tech-green-dk);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.part-item__icon svg { width: 14px; height: 14px; }
.part-item__info { flex: 1; min-width: 0; }
.part-item__name {
  font-weight: 700; color: var(--tech-gray-800);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  font-family: var(--tech-sans);
}
.part-item__meta { font-size: 11.5px; color: var(--tech-gray-400); margin-top: 1px; }
.part-item__qty {
  font-family: var(--tech-mono); font-size: 12px; font-weight: 700;
  color: var(--tech-green);
  background: var(--tech-green-lt); border: 1px solid var(--tech-green-bd);
  border-radius: 6px; padding: 2px 8px; white-space: nowrap;
}
.part-item__remove {
  background: none; border: none; color: var(--tech-gray-300); font-size: 16px;
  cursor: pointer; padding: 2px; width: 22px; height: 22px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: color .2s, background .2s;
  border-radius: 50%;
}
.part-item__remove:hover { color: #ef4444; background: #fee2e2; }

/* ── Media tiles ────────────────────────────────────────────────── */
.mediaTile {
  width: 100%; aspect-ratio: 1;
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-200);
  border-radius: 9px;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; transition: all .2s;
}
.mediaTile:hover { border-color: var(--tech-gray-300); box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.mediaTile__content {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; padding: 8px;
}
.mediaTile__x {
  position: absolute; top: 4px; right: 4px;
  background: rgba(0,0,0,.45); color: #fff; border: none;
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; cursor: pointer; transition: background .2s;
}
.mediaTile__x:hover { background: rgba(0,0,0,.7); }
.mediaTile--error   { border-color: #ef4444; background: #fef2f2; }
.mediaTile--loading { border-color: #f59e0b; background: #fffbeb; }

/* Config tiles → compact file rows */
#configMedia { grid-template-columns: 1fr !important; gap: 6px !important; }
#configMedia .mediaTile {
  aspect-ratio: unset !important; flex-direction: row !important;
  padding: 8px 12px !important; align-items: center !important;
  gap: 10px !important; height: auto !important;
}
#configMedia .mediaTile__content {
  flex-direction: row !important; justify-content: flex-start !important;
  padding: 0 !important; gap: 8px !important;
  font-size: 12.5px !important; color: var(--tech-gray-700) !important;
  font-weight: 600 !important; flex: 1 !important;
}
#configMedia .mediaTile__x {
  position: static !important; background: none !important;
  color: var(--tech-gray-300) !important; font-size: 18px !important;
  margin-left: auto !important;
}
#configMedia .mediaTile__x:hover { color: #ef4444 !important; }

/* ── Validation / alert boxes ───────────────────────────────────── */
.alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; }
.alert--warn {
  background: var(--tech-amber-lt);
  border: 1px solid var(--tech-amber-bd);
  color: #6D3F0A;
}
.alert--warn strong { color: var(--tech-amber); }
.alert--warn ul { list-style: disc; padding-left: 16px; }
.alert--warn li { margin-top: 3px; }

/* ── Stat cards (index page) ─────────────────────────────────── */
.stat-card {
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-100);
  border-radius: var(--tech-radius-lg);
  box-shadow: 0 1px 2px rgba(0,0,0,.03);
  padding: 16px;
  display: flex; flex-direction: column; gap: 12px;
  position: relative; overflow: hidden;
  transition: border-color .15s, box-shadow .15s;
}
.stat-card:hover {
  border-color: var(--tech-gray-200);
  box-shadow: 0 2px 6px rgba(0,0,0,.06);
}
.stat-card__accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  border-radius: var(--tech-radius-lg) var(--tech-radius-lg) 0 0;
}
.stat-card--blue   .stat-card__accent { background: var(--tech-blue); }
.stat-card--amber  .stat-card__accent { background: #D97706; }
.stat-card--purple .stat-card__accent { background: #534AB7; }
.stat-card--green  .stat-card__accent { background: var(--tech-green); }

.stat-card__icon {
  width: 34px; height: 34px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-card__icon svg { width: 17px; height: 17px; }
.stat-card--blue   .stat-card__icon { background: var(--tech-blue-lt);  color: var(--tech-blue); }
.stat-card--amber  .stat-card__icon { background: var(--tech-amber-lt); color: var(--tech-amber); }
.stat-card--purple .stat-card__icon { background: #EEEDFE; color: #534AB7; }
.stat-card--green  .stat-card__icon { background: var(--tech-green-lt); color: var(--tech-green); }

.stat-card__value {
  font-size: 28px; font-weight: 700; color: var(--tech-gray-900); line-height: 1;
  font-family: var(--tech-sans);
}
.stat-card__label {
  font-size: 12px; color: var(--tech-gray-500); font-weight: 400; margin-top: 2px;
}

/* ── Filter tab bar (index page) ────────────────────────────── */
.filter-bar {
  display: inline-flex; gap: 4px; padding: 4px;
  background: var(--tech-gray-100); border-radius: 12px;
}
.filter-tab, .chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 9px;
  font-size: 13px; font-weight: 500; color: var(--tech-gray-500);
  cursor: pointer; border: none; background: transparent;
  transition: background .15s, color .15s, box-shadow .15s;
  white-space: nowrap; user-select: none;
  font-family: var(--tech-sans);
}
.filter-tab:hover, .chip:hover {
  color: var(--tech-gray-800); background: rgba(255,255,255,.6);
}
.filter-tab.chip-on, .filter-tab.is-active,
.chip.chip-on {
  background: var(--tech-surface); color: var(--tech-gray-800); font-weight: 600;
  box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 0 0 .5px var(--tech-gray-200);
}
.filter-tab__count {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 19px; height: 19px; padding: 0 5px;
  border-radius: 99px; font-size: 11px; font-weight: 600;
  background: var(--tech-gray-200); color: var(--tech-gray-500);
  transition: background .15s, color .15s;
}
.filter-tab.chip-on .filter-tab__count,
.filter-tab.is-active .filter-tab__count {
  background: var(--tech-green-lt); color: var(--tech-green-dk);
}

/* ── Job cards (index page) ─────────────────────────────────── */
.job-card {
  background: var(--tech-surface);
  border: 1px solid var(--tech-gray-100);
  border-radius: var(--tech-radius-lg);
  box-shadow: 0 1px 2px rgba(0,0,0,.03);
  padding: 0 16px 14px;
  display: flex; flex-direction: column; height: 100%;
}
.job-card-tag {
  font-family: var(--tech-mono); font-size: 12px;
  color: var(--tech-green); font-weight: 700;
}
.job-card-body  { flex: 1; display: flex; flex-direction: column; }
.job-loc        { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--tech-gray-500); flex-wrap: wrap; }
.job-loc-dot    { width: 4px; height: 4px; border-radius: 50%; background: var(--tech-gray-300); display: inline-block; }
.claim-badge    { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: var(--tech-gray-100); color: var(--tech-gray-500); font-size: 11.5px; font-weight: 700; }

/* ── Modal dialogs ───────────────────────────────────────────── */
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-overlay {
  animation: fadeIn 0.3s ease-out;
}

.modal-dialog {
  font-family: var(--tech-sans);
}
</style>
