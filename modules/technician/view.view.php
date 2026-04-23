<?php require __DIR__ . '/_styles.php'; ?>

<?php
/* ── Badge helper ───────────────────────────────────────────── */
$status      = $wo['status'] ?? '';
$badge_cls   = match($status) {
  'assigned'    => 'badge-assigned',
  'scheduled'   => 'badge-scheduled',
  'in_progress' => 'badge-in_progress',
  'on_hold'     => 'badge-on_hold',
  'resolved'    => 'badge-resolved',
  'closed'      => 'badge-closed',
  default       => 'badge-new',
};
$badge_label = ucfirst(str_replace('_', ' ', $status));

/* ── Checklist / safety counts ──────────────────────────────── */
$sf_done  = count(array_filter($safety_checks, fn($i) => $i['is_done']));
$sf_total = count($safety_checks);
$cl_done  = count(array_filter($checklist, fn($i) => $i['is_done']));
$cl_total = count($checklist);
?>

<!-- ── Breadcrumb ─────────────────────────────────────────────── -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm font-medium transition-colors"
     style="color:var(--tech-gray-500);"
     onmouseover="this.style.color='var(--tech-gray-900)'"
     onmouseout="this.style.color='var(--tech-gray-500)'">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to My Jobs
  </a>
  <span style="color:var(--tech-gray-200);">/</span>
  <span class="vf-mono"><?php echo htmlspecialchars($wo['wo_number']); ?></span>
</div>

<!-- ── WO Header card ─────────────────────────────────────────── -->
<div class="wo-header-card mb-3">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <!-- Left: title, tags, description -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap mb-2">
        <span class="vf-mono"><?php echo htmlspecialchars($wo['wo_number']); ?></span>

        <?php if ($status === 'assigned' && !empty($can_edit)): ?>
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold"
                style="background:rgba(34, 197, 94, 0.1);color:#16a34a;border:1px solid rgba(34, 197, 94, 0.2);">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2.25 2.25L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Assigned to you
          </span>
        <?php endif; ?>

        <span id="woStatusBadge" class="wo-badge <?php echo $badge_cls; ?>">
          <span class="bdot"></span><?php echo $badge_label; ?>
        </span>

        <?php if (!empty($wo['assigned_to_name'])): ?>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                style="background:var(--tech-gray-100);color:var(--tech-gray-600);">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM9 19c-4.3-1.4-6-3.1-6-6"/>
            </svg>
            Assigned: <?php echo htmlspecialchars($wo['assigned_to_name']); ?>
          </span>
        <?php endif; ?>

        <?php if (empty($can_edit)): ?>
          <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold"
                style="background:var(--tech-gray-100);color:var(--tech-gray-500);">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            Read-only
          </span>
        <?php endif; ?>

        <?php if ($status === 'assigned' && !empty($can_edit)): ?>
          <button type="button"
                  id="startWorkBtn"
                  onclick="startWork(<?php echo (int)($wo['wo_id'] ?? 0); ?>, this)"
                  class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors"
                  style="background:var(--tech-green);"
                  onmouseover="this.style.background='var(--tech-green-dk)'"
                  onmouseout="this.style.background='var(--tech-green)'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>
            </svg>
            Start Work
          </button>
        <?php endif; ?>
      </div>

      <h1 id="woTitle" class="text-xl font-bold mt-1"
          style="color:var(--tech-gray-900);font-family:var(--tech-sans);line-height:1.3;">
        <?php echo htmlspecialchars($wo['ticket_title'] ?: 'Work Order'); ?>
      </h1>
      <p id="woDesc" class="text-sm mt-0.5 line-clamp-2"
         style="color:var(--tech-gray-500);font-family:var(--tech-sans);">
        <?php echo htmlspecialchars($wo['ticket_description'] ?: ''); ?>
      </p>
    </div>
  </div>

  <!-- Metadata strip -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-0 mt-4 pt-4"
       style="border-top:1px solid var(--tech-gray-100);">
    <div class="pr-4 md:pr-0 md:pl-0" style="border-right:1px solid var(--tech-gray-100);">
      <div class="vf-lbl">Priority</div>
      <?php
        $prio = strtolower($wo['priority'] ?? '');
        $prio_color = match($prio) {
          'high','urgent','critical' => 'var(--tech-red)',
          'medium','normal'          => 'var(--tech-amber)',
          default                    => 'var(--tech-gray-700)',
        };
      ?>
      <div id="woPriority" class="vf-val font-semibold"
           style="color:<?php echo $prio_color; ?>;">
        <?php echo ucfirst($wo['priority'] ?? '—'); ?>
      </div>
    </div>

    <div class="pl-4 md:pl-5" style="border-right:1px solid var(--tech-gray-100);">
      <div class="vf-lbl">Location</div>
      <div id="woLocation" class="vf-val text-sm">
        <?php echo htmlspecialchars(trim($wo['building'] . ' · ' . $wo['floor'] . ' · ' . $wo['room'], ' · ')); ?>
      </div>
    </div>

    <div class="pl-0 md:pl-5 mt-3 md:mt-0" style="border-right:1px solid var(--tech-gray-100);">
      <div class="vf-lbl">Requester</div>
      <div id="woRequester" class="vf-val"><?php echo htmlspecialchars($wo['requester_name'] ?? '—'); ?></div>
      <div class="flex gap-2 mt-1">
        <a id="woRequesterCall" href="tel:<?php echo htmlspecialchars($wo['contact_number'] ?? ''); ?>"
           class="inline-flex items-center gap-1 text-xs font-medium"
           style="color:var(--tech-green);">
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.338c0-1.16.99-2.004 2.145-1.89 1.08.11 2.142.28 3.18.509a1.875 1.875 0 011.212 2.4l-.697 2.093a16.5 16.5 0 006.46 6.46l2.092-.697a1.875 1.875 0 012.4 1.213c.23 1.037.4 2.099.51 3.18.113 1.154-.73 2.144-1.89 2.144H18a15.75 15.75 0 01-15.75-15.75v-.662z"/>
          </svg>
          Call
        </a>
        <a id="woRequesterMail" href="mailto:<?php echo htmlspecialchars($wo['email'] ?? ''); ?>"
           class="inline-flex items-center gap-1 text-xs font-medium"
           style="color:var(--tech-green);">
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
          </svg>
          Email
        </a>
      </div>
    </div>

    <div class="pl-0 md:pl-5 mt-3 md:mt-0">
      <div class="vf-lbl">Scheduled</div>
      <div class="vf-val text-sm">
        <?php if ($wo['scheduled_start'] ?? null): ?>
          <?php echo (new DateTime($wo['scheduled_start']))->format('M j, g:ia'); ?>
        <?php else: ?>
          <span class="vf-empty">Not set</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Second row: Assigned Technician -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-0 mt-4 pt-4"
       style="border-top:1px solid var(--tech-gray-100);">
    <div class="pr-4 md:pr-0 md:pl-0" style="border-right:1px solid var(--tech-gray-100);">
      <div class="vf-lbl">Assigned Technician</div>
      <div class="vf-val text-sm">
        <?php if ($wo['assigned_to_name'] ?? null): ?>
          <?php echo htmlspecialchars($wo['assigned_to_name']); ?>
        <?php else: ?>
          <span class="vf-empty">Unassigned</span>
        <?php endif; ?>
      </div>
    </div>
  </div>



<!-- ══════════════════════════════════════════════════════════════
     Main tab body
     ══════════════════════════════════════════════════════════════ -->
<div class="rounded-xl overflow-hidden mb-4"
     style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);">



  <!-- ── Secondary tabs (unified) ──────────────────────────── -->
  <div class="tab-nav secondary-tabs">

    <button class="tab-btn tab-on secondary-tab-btn"
            data-tab="safety" type="button"
            onclick="switchSecondaryTab('safety', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
      </svg>
      Safety
      <?php if ($sf_total > 0): ?>
        <span class="tab-count-badge <?php echo $sf_done === $sf_total ? 'done' : 'warn'; ?>">
          <?php echo $sf_done; ?>/<?php echo $sf_total; ?>
        </span>
      <?php endif; ?>
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="checklist" type="button"
            onclick="switchSecondaryTab('checklist', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Checklist
      <?php if ($cl_total > 0): ?>
        <span class="tab-count-badge <?php echo $cl_done === $cl_total ? 'done' : ''; ?>">
          <?php echo $cl_done; ?>/<?php echo $cl_total; ?>
        </span>
      <?php endif; ?>
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="timetracking" type="button"
            onclick="switchSecondaryTab('timetracking', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Time Tracking
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="parts" type="button"
            onclick="switchSecondaryTab('parts', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
      </svg>
      Parts
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="communication" type="button"
            onclick="switchSecondaryTab('communication', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
      </svg>
      Notes
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="evidence" type="button"
            onclick="switchSecondaryTab('evidence', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
      </svg>
      Evidence
    </button>

    <button class="tab-btn secondary-tab-btn"
            data-tab="signoff" type="button"
            onclick="switchSecondaryTab('signoff', this)">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
      </svg>
      Sign-off
    </button>
  </div>

  <!-- ════════════════════════════════════════════════════════════
       TAB PANES
       ════════════════════════════════════════════════════════════ -->

  <!-- ── Safety ─────────────────────────────────────────────── -->
  <div class="p-6" id="tab-safety">
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-red);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          Safety Pre-Flight Checks
        </div>
        <span class="tech-card__meta" id="safetyProgress">
          <?php echo $sf_done . '/' . $sf_total . ' complete'; ?>
        </span>
      </div>

      <?php if ($sf_total > 0): ?>
        <div class="tech-progress">
          <div class="tech-progress-fill red safety-progress-fill"
               style="width:<?php echo $sf_total ? round($sf_done/$sf_total*100) : 0; ?>%"></div>
        </div>
      <?php endif; ?>

      <div class="safety-info-banner">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        Complete all required safety checks before starting work. These ensure a safe working environment.
      </div>

      <div class="divide-y" id="safetyList" style="border-color:var(--tech-gray-100);">
        <!-- Rendered by workorder.js -->
      </div>

      <?php if (empty($safety_checks)): ?>
        <div class="px-5 py-10 text-center">
          <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"
               style="color:var(--tech-gray-200);">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm italic" style="color:var(--tech-gray-400);">No safety checks configured for this work order.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Checklist ───────────────────────────────────────────── -->
  <div class="p-6 hidden" id="tab-checklist">
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <?php
            $checklist_name = 'General Repair Checklist';
            echo htmlspecialchars($checklist_name);
          ?>
        </div>
        <div class="flex items-center gap-3">
          <span class="tech-card__meta" id="checklistProgress">
            <?php
              echo $cl_done . '/' . $cl_total . ' items';
              if ($cl_total > 0) echo ' (' . round($cl_done / $cl_total * 100) . '%)';
            ?>
          </span>
        </div>
      </div>

      <?php if ($cl_total > 0): ?>
        <div class="tech-progress">
          <div class="tech-progress-fill green cl-progress-fill"
               style="width:<?php echo $cl_total ? round($cl_done/$cl_total*100) : 0; ?>%"></div>
        </div>
      <?php endif; ?>

      <!-- Info banner -->
      <div class="offline-notice" style="border-radius:0;margin-top:0;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        Some items are auto-verified (e.g., photos taken). Items that cannot be auto-verified can be manually checked off.
      </div>

      <div id="checklistList" class="divide-y" style="border-color:var(--tech-gray-100);">
        <!-- Rendered by workorder.js -->
      </div>

      <?php if (empty($checklist)): ?>
        <div class="px-5 py-10 text-center">
          <svg class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"
               style="color:var(--tech-gray-200);">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm italic" style="color:var(--tech-gray-400);">No checklist items assigned for this work order.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Time Tracking ───────────────────────────────────────── -->
  <div class="p-6 hidden" id="tab-timetracking">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

      <!-- Timer controls -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title" style="color:var(--tech-green);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Time Tracker
          </div>
          <div id="timerState" class="text-xs font-semibold px-2 py-1 rounded-md"
               style="background:var(--tech-gray-100);color:var(--tech-gray-500);">Not started</div>
        </div>
        <div class="tech-card__body">

          <!-- Big clock display -->
          <div class="timer-display">
            <span id="timerValue">00:00:00</span>
            <span class="timer-state-label" id="timerStateLabel">Not started</span>
          </div>

          <!-- Labor type -->
          <div class="mb-4">
            <label class="vf-lbl block mb-2" for="laborType">Labor Type</label>
            <div class="relative">
              <select id="laborType" class="fin text-sm w-full">
                <option value="">Select labor type…</option>
                <option value="travel">Travel</option>
                <option value="diagnosis">Diagnosis</option>
                <option value="repair">Repair</option>
                <option value="cleanup">Cleanup</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <!-- Start / Stop -->
          <div class="flex gap-3 mb-5">
            <button id="btnStart"
                    class="flex-1 inline-flex items-center justify-center gap-2 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm"
                    style="background:var(--tech-green);"
                    onmouseover="this.style.background='var(--tech-green-dk)'"
                    onmouseout="this.style.background='var(--tech-green)'">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/>
              </svg>
              Start
            </button>
            <button id="btnStop"
                    class="flex-1 inline-flex items-center justify-center gap-2 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm"
                    style="background:var(--tech-red);"
                    onmouseover="this.style.background='#7A2020'"
                    onmouseout="this.style.background='var(--tech-red)'">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z"/>
              </svg>
              Stop &amp; Save
            </button>
          </div>

          <!-- Time entries list -->
          <div class="pt-4" style="border-top:1px solid var(--tech-gray-100);">
            <h4 class="vf-lbl mb-3">Time Entries</h4>
            <div id="timeLogsList" class="space-y-2"><!-- Rendered by workorder.js --></div>

            <!-- Total row — shown/updated by workorder.js via data-total-row -->
            <div id="timeTotalRow"
                 class="hidden flex items-center justify-between mt-3 pt-3"
                 style="border-top:1px solid var(--tech-gray-100);">
              <span class="text-sm font-semibold" style="color:var(--tech-gray-500);">Total</span>
              <span id="timeTotalValue"
                    class="text-sm font-bold"
                    style="font-family:var(--tech-mono);color:var(--tech-gray-900);">
                <?php
                  /* Format total_time (seconds) as H:MM:SS for server-side pre-render */
                  $tt = (int)($total_time ?? 0);
                  printf('%d:%02d:%02d', intdiv($tt, 3600), intdiv($tt % 3600, 60), $tt % 60);
                ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Labor breakdown sidebar -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title">Labor Breakdown</div>
        </div>
        <div class="tech-card__body" id="laborBreakdown">
          <p class="text-sm italic text-center py-4" style="color:var(--tech-gray-400);">
            Time entries will appear here once you start tracking.
          </p>
        </div>
      </div>

    </div>
  </div>

  <!-- ── Parts ───────────────────────────────────────────────── -->
  <div class="p-6 hidden" id="tab-parts">
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.653-4.655m5.585-.359c.55-.157 1.12-.393 1.676-.752a12.07 12.07 0 00-9.12-9.12 7.094 7.094 0 00-.752 1.676m.359 5.596l5.877-5.877a2.652 2.652 0 113.75 3.75l-5.877 5.877"/>
          </svg>
          Parts Used
          <span class="text-xs font-normal" style="color:var(--tech-gray-400);">— log parts manually</span>
        </div>
      </div>
      <div class="tech-card__body">
        <div class="flex flex-wrap gap-2 mb-4">
          <input class="fin text-sm flex-1 min-w-36" id="partNumber" placeholder="Part number or name" />
          <input class="fin text-sm w-20" id="partQty" type="number" min="1" value="1" placeholder="Qty" />
          <input class="fin text-sm flex-1 min-w-36" id="partSerial" placeholder="Serial (optional)" />
          <button id="btnAddPart"
                  class="inline-flex items-center justify-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap"
                  style="background:var(--tech-green);"
                  onmouseover="this.style.background='var(--tech-green-dk)'"
                  onmouseout="this.style.background='var(--tech-green)'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Add Part
          </button>
        </div>
        <div id="partsList" class="space-y-2"><!-- Rendered by workorder.js --></div>
      </div>
    </div>
  </div>

  <!-- ── Communication / Notes ───────────────────────────────── -->
  <div class="p-6 hidden" id="tab-communication">
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
          </svg>
          Notes &amp; Communication
          <span class="text-xs font-normal" style="color:var(--tech-gray-400);">— text or voice</span>
        </div>
      </div>
      <div class="tech-card__body">
        <div class="space-y-3 mb-4">
          <input id="noteTitle" type="text" class="fin text-sm w-full"
                 placeholder="Note title (optional)…" />
          <textarea id="noteText" rows="4" class="fin text-sm w-full resize-none"
                    placeholder="Add a progress note…"></textarea>
        </div>
        <div class="flex items-center justify-between gap-3">
          <button id="btnVoice"
                  class="inline-flex items-center gap-1.5 text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                  style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);color:var(--tech-gray-700);"
                  onmouseover="this.style.background='var(--tech-gray-50)'"
                  onmouseout="this.style.background='var(--tech-surface)'">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/>
            </svg>
            Voice
          </button>
          <button id="btnAddNote"
                  class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
                  style="background:var(--tech-green);"
                  onmouseover="this.style.background='var(--tech-green-dk)'"
                  onmouseout="this.style.background='var(--tech-green)'">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Add Note
          </button>
        </div>

        <!-- Requester contact -->
        <div class="mt-6 pt-5" style="border-top:1px solid var(--tech-gray-100);">
          <h4 class="text-sm font-semibold mb-3" style="color:var(--tech-gray-800);">Requester Contact</h4>
          <div class="flex flex-col sm:flex-row gap-3">
            <a id="woRequesterCall" href="tel:<?php echo htmlspecialchars($wo['contact_number'] ?? ''); ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--tech-blue-lt);border:1px solid var(--tech-blue-bd);color:var(--tech-blue);">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.338c0-1.16.99-2.004 2.145-1.89 1.08.11 2.142.28 3.18.509a1.875 1.875 0 011.212 2.4l-.697 2.093a16.5 16.5 0 006.46 6.46l2.092-.697a1.875 1.875 0 012.4 1.213c.23 1.037.4 2.099.51 3.18.113 1.154-.73 2.144-1.89 2.144H18a15.75 15.75 0 01-15.75-15.75v-.662z"/>
              </svg>
              Call <?php echo htmlspecialchars($wo['requester_name'] ?? 'Requester'); ?>
            </a>
            <a id="woRequesterMail" href="mailto:<?php echo htmlspecialchars($wo['email'] ?? ''); ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--tech-green-lt);border:1px solid var(--tech-green-bd);color:var(--tech-green-dk);">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
              </svg>
              Email <?php echo htmlspecialchars($wo['requester_name'] ?? 'Requester'); ?>
            </a>
          </div>
        </div>

        <div id="notesList" class="mt-5 space-y-2"><!-- Rendered by workorder.js --></div>
      </div>
    </div>
  </div>

  <!-- ── Evidence (Documentation) ───────────────────────────── -->
  <div class="p-6 hidden" id="tab-evidence">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

      <!-- Before -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            Before
          </div>
          <span class="tech-card__meta">(<span id="beforeCount">0</span> files)</span>
        </div>
        <div class="tech-card__body">
          <label for="beforeFiles"
                 class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors group mb-3"
                 style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
                 onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
                 onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                 style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Tap to capture / upload</span>
            <input id="beforeFiles" type="file" accept="image/*,video/*" capture="environment" multiple class="sr-only" />
          </label>
          <div class="grid grid-cols-2 gap-2" id="beforeMedia"></div>
        </div>
      </div>

      <!-- After -->
      <div class="tech-card" style="margin-bottom:0;">
        <div class="tech-card__head">
          <div class="tech-card__title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--tech-green);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            After
          </div>
          <span class="tech-card__meta">(<span id="afterCount">0</span> files)</span>
        </div>
        <div class="tech-card__body">
          <label for="afterFiles"
                 class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors group mb-3"
                 style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
                 onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
                 onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                 style="color:var(--tech-gray-400);">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.776 48.776 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
            <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Tap to capture / upload</span>
            <input id="afterFiles" type="file" accept="image/*,video/*" capture="environment" multiple class="sr-only" />
          </label>
          <div class="grid grid-cols-2 gap-2" id="afterMedia"></div>
        </div>
      </div>
    </div>

    <!-- Config backups -->
    <div class="tech-card">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Configuration Backups &amp; Logs
        </div>
        <span class="tech-card__meta">(<span id="configCount">0</span> files)</span>
      </div>
      <div class="tech-card__body">
        <label for="configFiles"
               class="flex items-center gap-3 w-full rounded-lg px-4 py-2.5 cursor-pointer transition-colors mb-3"
               style="border:1px dashed var(--tech-gray-200);background:var(--tech-gray-50);"
               onmouseover="this.style.borderColor='var(--tech-green-mid)';this.style.background='var(--tech-green-lt)'"
               onmouseout="this.style.borderColor='var(--tech-gray-200)';this.style.background='var(--tech-gray-50)'">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
               style="color:var(--tech-gray-400);">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <span class="text-xs font-medium" style="color:var(--tech-gray-500);">Upload config files, logs, backups</span>
          <span class="ml-auto text-xs hidden sm:inline" style="color:var(--tech-gray-400);">
            .json .xml .cfg .log .zip .tar&hellip; &middot; Max 50MB
          </span>
          <input id="configFiles"
                 type="file"
                 accept=".json,.xml,.cfg,.conf,.ini,.txt,.log,.csv,.zip,.tar,.gz,.bak,.img"
                 multiple class="sr-only" />
        </label>
        <div class="grid grid-cols-3 gap-2" id="configMedia"></div>
      </div>
    </div>
  </div>

  <!-- ── Sign-off (Documentation) ────────────────────────────── -->
  <div class="p-6 hidden" id="tab-signoff">

    <!-- Validation blocker -->
    <div id="completeBlocker" class="mb-5 hidden"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- Requester info fields -->
      <div class="tech-card" style="margin-bottom:0;background:var(--tech-gray-50);">
        <div class="tech-card__head" style="background:var(--tech-gray-50);">
          <div class="tech-card__title" style="color:var(--tech-green);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
            Requester Sign-off
          </div>
        </div>
        <div class="tech-card__body space-y-4">
          <div>
            <label class="vf-lbl block mb-1.5" for="signerName">
              Full name <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerName" placeholder="e.g., Juan Dela Cruz" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerId">
              ID number <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerId" placeholder="e.g., 2021-00123" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerEmail">
              Email address <span style="color:var(--tech-red);">*</span>
            </label>
            <input class="fin text-sm w-full" id="signerEmail" type="email" placeholder="e.g., j.delacruz@olfu.edu.ph" />
          </div>
          <div>
            <label class="vf-lbl block mb-1.5" for="signerPosition">Position / Role</label>
            <select class="fin text-sm w-full" id="signerPosition">
              <option value="">Select position…</option>
              <option value="Faculty">Faculty</option>
              <option value="Staff">Staff</option>
              <option value="Department Staff">Department Staff</option>
              <option value="IT Staff">IT Staff</option>
              <option value="IT Manager">IT Manager</option>
              <option value="Admin">Admin</option>
              <option value="Student">Student</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Signature canvas -->
      <div class="tech-card" style="margin-bottom:0;background:var(--tech-gray-50);">
        <div class="tech-card__head" style="background:var(--tech-gray-50);">
          <div class="tech-card__title" style="color:var(--tech-green);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
            </svg>
            Digital Signature
          </div>
        </div>
        <div class="tech-card__body flex flex-col">
          <p class="text-xs mb-3" style="color:var(--tech-gray-400);">
            Sign using your mouse or finger in the box below.
          </p>
          <div class="relative flex-1 min-h-0">
            <canvas id="sigCanvas"
                    class="w-full block rounded-xl"
                    style="height:200px;touch-action:none;cursor:crosshair;
                           background:#fff;border:2px dashed var(--tech-gray-200);">
            </canvas>
            <div id="sigPlaceholder"
                 class="absolute inset-0 flex items-center justify-center pointer-events-none"
                 style="color:var(--tech-gray-300);">
              <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
              </svg>
              <span class="ml-2 text-sm font-medium">Sign here</span>
            </div>
          </div>
          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-1.5 text-sm font-medium" id="sigStatus">
              <span class="w-2 h-2 rounded-full inline-block" style="background:var(--tech-red);"></span>
              <span style="color:var(--tech-gray-500);">Not signed</span>
            </div>
            <div class="flex gap-2">
              <button id="btnClearSig"
                      class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                      style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);color:var(--tech-gray-600);"
                      onmouseover="this.style.background='var(--tech-gray-50)'"
                      onmouseout="this.style.background='var(--tech-surface)'">
                Clear
              </button>
              <button id="btnSaveSig"
                      class="text-xs font-semibold text-white px-3 py-1.5 rounded-lg transition-colors"
                      style="background:var(--tech-green);"
                      onmouseover="this.style.background='var(--tech-green-dk)'"
                      onmouseout="this.style.background='var(--tech-green)'">
                Save signature
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Satisfaction rating -->
    <div class="tech-card mt-6">
      <div class="tech-card__head">
        <div class="tech-card__title" style="color:var(--tech-green);">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
          Service Satisfaction Rating
        </div>
      </div>
      <div class="tech-card__body">
        <p class="text-xs mb-4" style="color:var(--tech-gray-500);">
          How satisfied are you with the service provided? (1–5 stars)
        </p>
        <div class="flex items-center gap-3 mb-4">
          <div class="flex gap-1" id="satisfactionStars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" class="satisfaction-star" data-rating="<?php echo $s; ?>">
              <svg class="w-8 h-8 transition-colors" fill="currentColor" viewBox="0 0 24 24"
                   style="color:var(--tech-gray-200);">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
            </button>
            <?php endfor; ?>
          </div>
          <span class="text-sm font-medium" id="satisfactionText" style="color:var(--tech-gray-500);">Please rate</span>
          <input type="hidden" id="satisfactionRating" name="satisfaction" value="">
        </div>
        <div>
          <label class="vf-lbl block mb-1.5" for="satisfactionFeedback">
            Additional feedback (optional)
          </label>
          <textarea id="satisfactionFeedback" class="fin text-sm w-full resize-none" rows="3"
                    placeholder="Tell us about your experience with this service…"></textarea>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-5 flex justify-end gap-3">
      <button id="btnSaveDraft"
              class="inline-flex items-center gap-1.5 text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
              style="background:var(--tech-surface);border:1px solid var(--tech-gray-200);color:var(--tech-gray-700);"
              onmouseover="this.style.background='var(--tech-gray-50)'"
              onmouseout="this.style.background='var(--tech-surface)'">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Save Draft
      </button>
      <button id="btnComplete"
              class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
              style="background:var(--tech-green);"
              onmouseover="this.style.background='var(--tech-green-dk)'"
              onmouseout="this.style.background='var(--tech-green)'">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Complete Work Order
      </button>
    </div>
  </div>

</div><!-- /main tab body -->

<?php
/* ── JSON payload for workorder.js ────────────────────────── */
$__wo_payload = [
  'id'          => $wo['wo_id'],
  'wo_number'   => $wo['wo_number'],
  'title'       => $wo['ticket_title'],
  'description' => $wo['ticket_description'],
  'status'      => $wo['status'],
  'priority'    => $wo['priority'],
  'location'    => ($wo['building'] ?? '') . ' • ' . ($wo['floor'] ?? '') . ' • ' . ($wo['room'] ?? ''),
  'requester'   => [
    'name'  => $wo['requester_name'],
    'phone' => $wo['contact_number'],
    'email' => $wo['email'],
  ],
  'assigned_to' => [
    'id'   => $wo['assigned_to'],
    'name' => $wo['assigned_to_name'] ?? null,
  ],
  'checklist' => array_map(fn($item) => [
    'id'                => $item['item_id'],
    'text'              => $item['item_text'],
    'required'          => $item['is_mandatory'],
    'requires_photo'    => (bool)($item['requires_photo'] ?? false),
    'is_verifiable'     => (bool)($item['is_verifiable'] ?? false),
    'verification_type' => $item['verification_type'] ?? null,
    'is_done'           => (bool)$item['is_done'],
  ], $checklist),
  'safety' => array_map(fn($s) => [
    'id'        => $s['safety_id'],
    'text'      => $s['safety_text'] ?? $s['check_text'] ?? '',
    'mandatory' => (bool)($s['is_mandatory'] ?? true),
    'is_done'   => (bool)($s['is_done'] ?? false),
  ], $safety_checks ?? []),
  'notes' => array_map(fn($n) => [
    'note_id'    => $n['note_id'],
    'note_text'  => $n['note_text'],
    'created_at' => $n['created_at'],
    'created_by' => $n['created_by'],
    'author'     => $n['author_name'] ?? null,
  ], $notes ?? []),
  'media' => array_map(fn($m) => [
    'media_id'   => $m['media_id'],
    'media_type' => $m['media_type'],
    'file_path'  => $m['file_path'],
    'file_type'  => $m['file_type'],
    'caption'    => $m['caption'],
    'uploaded_at'=> $m['uploaded_at'],
  ], $media ?? []),
  'parts' => array_map(fn($p) => [
    'part_id'     => $p['part_id'],
    'part_number' => $p['part_number'],
    'quantity'    => $p['quantity'],
    'serial_no'   => $p['serial_no'],
    'added_at'    => $p['added_at'],
    'added_by'    => $p['added_by'],
  ], $parts ?? []),
  'time_logs'   => $time_logs ?? [],
  'total_time'  => $total_time ?? 0,
  'signoff' => $signoff ? [
    'signer_name'    => $signoff['signer_name'],
    'signature_path' => $signoff['signature_path'],
    'satisfaction'   => $signoff['satisfaction'],
    'feedback'       => $signoff['feedback'],
    'signed_at'      => $signoff['signed_at'],
  ] : null,
  'evidence_required'  => (bool)($evidence_required ?? false),
  'signature_required' => (bool)($signature_required ?? true),
  'can_edit'           => (bool)($can_edit ?? false),
  'can_claim'          => false,
];
$__wo_json = json_encode($__wo_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($__wo_json === false) {
  tech_dbg('H_WO_JSON', 'modules/technician/view.view.php:wo_json', 'json_encode failed for __WO_DATA__', [
    'wo_id'      => $wo_id,
    'json_error' => json_last_error_msg(),
  ]);
  $__wo_json = json_encode(['id' => $wo_id]);
}
?>

<script>
window.__WO_ID__   = <?php echo json_encode($wo_id); ?>;
window.__WO_DATA__ = <?php echo $__wo_json; ?>;

(function () {
  if (document.querySelector('link[rel="manifest"]')) return;
  const link = document.createElement('link');
  link.rel  = 'manifest';
  link.href = '<?php echo BASE_URL; ?>public/manifest.json';
  document.head.appendChild(link);
})();

window.MRTS = {
  APP_BASE: '<?php echo BASE_URL; ?>',
  USER_ID:  <?php echo json_encode($_SESSION['user_id'] ?? null); ?>
};

/* ── Primary tab switching ───────────────────────────────── */
function switchPrimaryTab(key, btn) {
  document.querySelectorAll('.primary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  if (btn) btn.classList.add('tab-on');

  const isExec = (key === 'primary-execution');
  document.getElementById('execution-secondary-tabs').classList.toggle('hidden', !isExec);
  document.getElementById('documentation-secondary-tabs').classList.toggle('hidden', isExec);

  // Activate the first secondary tab for the chosen primary
  if (isExec) {
    const firstExecBtn = document.querySelector('#execution-secondary-tabs .secondary-tab-btn');
    if (firstExecBtn) switchSecondaryTab(firstExecBtn.dataset.tab, firstExecBtn, 'execution');
  } else {
    const firstDocBtn = document.querySelector('#documentation-secondary-tabs .secondary-tab-btn');
    if (firstDocBtn) switchSecondaryTab(firstDocBtn.dataset.tab, firstDocBtn, 'documentation');
  }
}

/* ── Secondary tab switching ─────────────────────────────── */
function switchSecondaryTab(key, btn, group) {
  // Deactivate all secondary tabs in this group
  const container = document.getElementById(group + '-secondary-tabs');
  if (container) {
    container.querySelectorAll('.secondary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  }
  if (btn) btn.classList.add('tab-on');

  // Hide all tab panes
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));

  // Show target pane
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

/* Legacy alias kept for workorder.js compatibility */
function switchTab(key, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-on'));
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));
  if (btn) btn.classList.add('tab-on');
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

/* ── Start Work ──────────────────────────────────────────── */
function startWork(woId, button) {
  if (!confirm('Start work on this job? This will change the status to "In Progress".')) return;
  button.disabled = true;
  button.textContent = 'Starting…';

  fetch('<?php echo BASE_URL; ?>modules/technician/sync.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=start_work&wo_id=' + woId
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const badge = document.getElementById('woStatusBadge');
      if (badge) {
        badge.className = 'wo-badge badge-in_progress';
        badge.innerHTML = '<span class="bdot"></span>In Progress';
      }
      button.remove();
      const msg = document.createElement('div');
      msg.className = 'text-sm font-medium px-3 py-2 rounded-lg mb-4';
      msg.style.cssText = 'background:var(--tech-green-lt);color:var(--tech-green-dk);border:1px solid var(--tech-green-bd);';
      msg.textContent = 'Work started successfully!';
      badge.parentElement.parentElement.prepend(msg);
      setTimeout(() => msg.remove(), 3000);
    } else {
      alert(data.message || 'Failed to start work');
      button.disabled = false;
      button.textContent = 'Start Work';
    }
  })
  .catch(() => {
    alert('Network error while starting work');
    button.disabled = false;
    button.textContent = 'Start Work';
  });
}

/* ── Satisfaction stars ──────────────────────────────────── */
(function () {
  const stars      = document.querySelectorAll('.satisfaction-star');
  const ratingIn   = document.getElementById('satisfactionRating');
  const ratingText = document.getElementById('satisfactionText');
  const labels     = ['', 'Very Dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very Satisfied'];

  function paintStars(n) {
    stars.forEach((s, i) => {
      s.querySelector('svg').style.color = (i < n) ? '#F59E0B' : 'var(--tech-gray-200)';
    });
  }

  stars.forEach(star => {
    star.addEventListener('click', function () {
      const r = parseInt(this.dataset.rating);
      ratingIn.value = r;
      ratingText.textContent = labels[r];
      paintStars(r);
    });
    star.addEventListener('mouseenter', function () { paintStars(parseInt(this.dataset.rating)); });
  });
  document.getElementById('satisfactionStars')?.addEventListener('mouseleave', () => {
    paintStars(parseInt(ratingIn.value) || 0);
  });
})();
</script>

<script src="<?php echo BASE_URL; ?>public/assets/js/technician/app.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/offline.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/idb-storage.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/signature.js"></script>
<script src="<?php echo BASE_URL; ?>public/assets/js/technician/workorder.js"></script>

<script>
/* ── Parts list renderer override ─────────────────────────── */
(function () {
  function patchPartsRender() {
    const list = document.getElementById('partsList');
    if (!list) return;
    const obs = new MutationObserver(() => {
      obs.disconnect();
      reformatParts();
      obs.observe(list, { childList: true, subtree: true });
    });
    obs.observe(list, { childList: true, subtree: true });
    if (list.children.length) reformatParts();
  }

  function reformatParts() {
    const list = document.getElementById('partsList');
    if (!list) return;
    const raw = Array.from(list.children).filter(el => !el.classList.contains('part-item'));
    if (!raw.length) return;
    raw.forEach(el => {
      const text      = el.innerText || el.textContent || '';
      const nameMatch = text.match(/^(.+?)\s*[×x](\d+)/m);
      const serialMatch = text.match(/Serial[:\s]+(\S+)/i);
      if (!nameMatch) return;
      const name   = nameMatch[1].trim();
      const qty    = nameMatch[2];
      const serial = serialMatch ? serialMatch[1] : '';
      let removeBtn = el.querySelector('button, a') || null;
      if (removeBtn) removeBtn = removeBtn.cloneNode(true);
      el.className = 'part-item';
      el.innerHTML = `
        <div class="part-item__icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877
                 M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766
                 M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.653-4.655"/>
          </svg>
        </div>
        <div class="part-item__info">
          <div class="part-item__name">${name}</div>
          ${serial ? `<div class="part-item__meta">Serial: ${serial}</div>` : ''}
        </div>
        <span class="part-item__qty">×${qty}</span>
        <button class="part-item__remove" title="Remove" data-part-remove>×</button>
      `;
      if (removeBtn) {
        el.querySelector('[data-part-remove]').onclick = () => removeBtn.click();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patchPartsRender);
  } else {
    patchPartsRender();
  }
})();

/* ── Show total time row when entries exist ────────────────── */
(function () {
  const totalRow = document.getElementById('timeTotalRow');
  const logsList = document.getElementById('timeLogsList');
  if (!totalRow || !logsList) return;
  const obs = new MutationObserver(() => {
    totalRow.classList.toggle('hidden', logsList.children.length === 0);
  });
  obs.observe(logsList, { childList: true });
  totalRow.classList.toggle('hidden', logsList.children.length === 0);
})();
</script>
